<?php

namespace App\Filament\Manajer\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Tindakan;
use App\Models\Pegawai;
use App\Models\Dokter;
use App\Models\Pasien;
use Illuminate\Support\Facades\DB;

class RoleEfficiencyWidget extends BaseWidget
{
    protected static ?int $sort = 5;
    
    protected int|string|array $columnSpan = 'full';
    
    protected static bool $isLazy = false;
    
    protected function getStats(): array
    {
        $currentMonth = now()->month;
        $currentYear = now()->year;
        
        // Individual role efficiency metrics
        $dokterCount = Dokter::where('aktif', true)->count();
        $paramedisCount = Pegawai::where('jenis_pegawai', 'Paramedis')->where('aktif', true)->count();
        $nonParamedisCount = Pegawai::where('jenis_pegawai', 'Non-Paramedis')->where('aktif', true)->count();
        
        // Procedures per role
        $dokterProcs = Tindakan::whereNotNull('dokter_id')
            ->whereMonth('created_at', $currentMonth)
            ->whereYear('created_at', $currentYear)
            ->count();
            
        $paramedisProcs = Tindakan::whereNotNull('paramedis_id')
            ->whereMonth('created_at', $currentMonth)
            ->whereYear('created_at', $currentYear)
            ->count();
            
        $nonParamedisProcs = Tindakan::whereNotNull('non_paramedis_id')
            ->whereMonth('created_at', $currentMonth)
            ->whereYear('created_at', $currentYear)
            ->count();
        
        // Calculate efficiency rates
        $dokterEfficiency = $dokterCount > 0 ? round($dokterProcs / $dokterCount, 1) : 0;
        $paramedisEfficiency = $paramedisCount > 0 ? round($paramedisProcs / $paramedisCount, 1) : 0;
        $nonParamedisEfficiency = $nonParamedisCount > 0 ? round($nonParamedisProcs / $nonParamedisCount, 1) : 0;
        
        // Revenue efficiency (revenue per procedure)
        $dokterRevenue = Tindakan::whereNotNull('dokter_id')
            ->whereMonth('created_at', $currentMonth)
            ->whereYear('created_at', $currentYear)
            ->sum('jasa_dokter');
            
        $paramedisRevenue = Tindakan::whereNotNull('paramedis_id')
            ->whereMonth('created_at', $currentMonth)
            ->whereYear('created_at', $currentYear)
            ->sum('jasa_paramedis');
            
        $nonParamedisRevenue = Tindakan::whereNotNull('non_paramedis_id')
            ->whereMonth('created_at', $currentMonth)
            ->whereYear('created_at', $currentYear)
            ->sum('jasa_non_paramedis');
        
        $dokterRevenuePerProc = $dokterProcs > 0 ? $dokterRevenue / $dokterProcs : 0;
        $paramedisRevenuePerProc = $paramedisProcs > 0 ? $paramedisRevenue / $paramedisProcs : 0;
        $nonParamedisRevenuePerProc = $nonParamedisProcs > 0 ? $nonParamedisRevenue / $nonParamedisProcs : 0;
        
        // Department efficiency targets
        $dokterTarget = 15; // procedures per doctor per month
        $paramedisTarget = 12; // procedures per paramedis per month
        $nonParamedisTarget = 6; // procedures per non-paramedis per month
        
        $dokterTargetRate = $dokterTarget > 0 ? round(($dokterEfficiency / $dokterTarget) * 100, 1) : 0;
        $paramedisTargetRate = $paramedisTarget > 0 ? round(($paramedisEfficiency / $paramedisTarget) * 100, 1) : 0;
        $nonParamedisTargetRate = $nonParamedisTarget > 0 ? round(($nonParamedisEfficiency / $nonParamedisTarget) * 100, 1) : 0;
        
        // Overall department efficiency
        $totalActual = $dokterEfficiency + $paramedisEfficiency + $nonParamedisEfficiency;
        $totalTarget = $dokterTarget + $paramedisTarget + $nonParamedisTarget;
        $overallEfficiency = $totalTarget > 0 ? round(($totalActual / $totalTarget) * 100, 1) : 0;
        
        // Efficiency trend
        $efficiencyTrend = collect();
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $dailyProcs = Tindakan::whereDate('created_at', $date)->count();
            $totalStaff = $dokterCount + $paramedisCount + $nonParamedisCount;
            $efficiencyTrend->push($totalStaff > 0 ? round($dailyProcs / $totalStaff, 1) : 0);
        }
        
        return [
            Stat::make('👨‍⚕️ Doctor Efficiency', $dokterEfficiency . '/' . $dokterTarget . ' target')
                ->description("🎯 {$dokterTargetRate}% of target | 💰 Rp " . number_format($dokterRevenuePerProc) . "/procedure")
                ->descriptionIcon($dokterTargetRate >= 100 ? 'heroicon-m-check-circle' : 'heroicon-m-clock')
                ->color($dokterTargetRate >= 100 ? 'success' : ($dokterTargetRate >= 75 ? 'warning' : 'danger')),
                
            Stat::make('🏥 Paramedis Efficiency', $paramedisEfficiency . '/' . $paramedisTarget . ' target')
                ->description("🎯 {$paramedisTargetRate}% of target | 💰 Rp " . number_format($paramedisRevenuePerProc) . "/procedure")
                ->descriptionIcon($paramedisTargetRate >= 100 ? 'heroicon-m-check-circle' : 'heroicon-m-clock')
                ->color($paramedisTargetRate >= 100 ? 'success' : ($paramedisTargetRate >= 75 ? 'warning' : 'danger')),
                
            Stat::make('📋 Non-Paramedis Efficiency', $nonParamedisEfficiency . '/' . $nonParamedisTarget . ' target')
                ->description("🎯 {$nonParamedisTargetRate}% of target | 💰 Rp " . number_format($nonParamedisRevenuePerProc) . "/procedure")
                ->descriptionIcon($nonParamedisTargetRate >= 100 ? 'heroicon-m-check-circle' : 'heroicon-m-clock')
                ->color($nonParamedisTargetRate >= 100 ? 'success' : ($nonParamedisTargetRate >= 75 ? 'warning' : 'danger')),
                
            Stat::make('🏆 Overall Department Efficiency', $overallEfficiency . '%')
                ->description("📊 Combined efficiency across all roles | 📈 Trend: 7-day average")
                ->descriptionIcon('heroicon-m-chart-bar-square')
                ->chart($efficiencyTrend->toArray())
                ->color($overallEfficiency >= 85 ? 'success' : ($overallEfficiency >= 70 ? 'warning' : 'danger')),
        ];
    }
}