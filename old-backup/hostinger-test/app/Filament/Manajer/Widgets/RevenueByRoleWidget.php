<?php

namespace App\Filament\Manajer\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Tindakan;
use App\Models\Pendapatan;
use App\Models\Pegawai;
use App\Models\Dokter;
use Illuminate\Support\Facades\DB;

class RevenueByRoleWidget extends BaseWidget
{
    protected static ?int $sort = 3;
    
    protected int|string|array $columnSpan = 'full';
    
    protected static bool $isLazy = false;
    
    protected function getStats(): array
    {
        $currentMonth = now()->month;
        $currentYear = now()->year;
        $previousMonth = now()->subMonth()->month;
        $previousYear = now()->subMonth()->year;
        
        // Current month revenue by role
        $currentDokterRevenue = Tindakan::whereNotNull('dokter_id')
            ->whereMonth('created_at', $currentMonth)
            ->whereYear('created_at', $currentYear)
            ->sum('jasa_dokter');
            
        $currentParamedisRevenue = Tindakan::whereNotNull('paramedis_id')
            ->whereMonth('created_at', $currentMonth)
            ->whereYear('created_at', $currentYear)
            ->sum('jasa_paramedis');
            
        $currentNonParamedisRevenue = Tindakan::whereNotNull('non_paramedis_id')
            ->whereMonth('created_at', $currentMonth)
            ->whereYear('created_at', $currentYear)
            ->sum('jasa_non_paramedis');
        
        // Previous month revenue by role
        $previousDokterRevenue = Tindakan::whereNotNull('dokter_id')
            ->whereMonth('created_at', $previousMonth)
            ->whereYear('created_at', $previousYear)
            ->sum('jasa_dokter');
            
        $previousParamedisRevenue = Tindakan::whereNotNull('paramedis_id')
            ->whereMonth('created_at', $previousMonth)
            ->whereYear('created_at', $previousYear)
            ->sum('jasa_paramedis');
            
        $previousNonParamedisRevenue = Tindakan::whereNotNull('non_paramedis_id')
            ->whereMonth('created_at', $previousMonth)
            ->whereYear('created_at', $previousYear)
            ->sum('jasa_non_paramedis');
        
        // Calculate growth rates
        $dokterGrowth = $previousDokterRevenue > 0 ? 
            round((($currentDokterRevenue - $previousDokterRevenue) / $previousDokterRevenue) * 100, 1) : 0;
        $paramedisGrowth = $previousParamedisRevenue > 0 ? 
            round((($currentParamedisRevenue - $previousParamedisRevenue) / $previousParamedisRevenue) * 100, 1) : 0;
        $nonParamedisGrowth = $previousNonParamedisRevenue > 0 ? 
            round((($currentNonParamedisRevenue - $previousNonParamedisRevenue) / $previousNonParamedisRevenue) * 100, 1) : 0;
        
        // Calculate total revenue
        $totalRevenue = $currentDokterRevenue + $currentParamedisRevenue + $currentNonParamedisRevenue;
        
        // Calculate average revenue per person
        $dokterCount = Dokter::where('aktif', true)->count();
        $paramedisCount = Pegawai::where('jenis_pegawai', 'Paramedis')->where('aktif', true)->count();
        $nonParamedisCount = Pegawai::where('jenis_pegawai', 'Non-Paramedis')->where('aktif', true)->count();
        
        $avgDokterRevenue = $dokterCount > 0 ? $currentDokterRevenue / $dokterCount : 0;
        $avgParamedisRevenue = $paramedisCount > 0 ? $currentParamedisRevenue / $paramedisCount : 0;
        $avgNonParamedisRevenue = $nonParamedisCount > 0 ? $currentNonParamedisRevenue / $nonParamedisCount : 0;
        
        // Revenue trend charts
        $dokterChart = collect();
        $paramedisChart = collect();
        $nonParamedisChart = collect();
        
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            
            $dokterChart->push(
                Tindakan::whereNotNull('dokter_id')
                    ->whereDate('created_at', $date)
                    ->sum('jasa_dokter') / 1000000 // Convert to millions
            );
            
            $paramedisChart->push(
                Tindakan::whereNotNull('paramedis_id')
                    ->whereDate('created_at', $date)
                    ->sum('jasa_paramedis') / 1000000
            );
            
            $nonParamedisChart->push(
                Tindakan::whereNotNull('non_paramedis_id')
                    ->whereDate('created_at', $date)
                    ->sum('jasa_non_paramedis') / 1000000
            );
        }
        
        return [
            Stat::make('👨‍⚕️ Dokter Revenue', 'Rp ' . number_format($currentDokterRevenue))
                ->description($dokterGrowth >= 0 ? 
                    "↗️ {$dokterGrowth}% from last month | Avg: Rp " . number_format($avgDokterRevenue) . "/doctor" : 
                    "↘️ {$dokterGrowth}% from last month | Avg: Rp " . number_format($avgDokterRevenue) . "/doctor")
                ->descriptionIcon($dokterGrowth >= 0 ? 
                    'heroicon-m-arrow-trending-up' : 
                    'heroicon-m-arrow-trending-down')
                ->chart($dokterChart->toArray())
                ->color($dokterGrowth >= 0 ? 'success' : 'danger'),
                
            Stat::make('🏥 Paramedis Revenue', 'Rp ' . number_format($currentParamedisRevenue))
                ->description($paramedisGrowth >= 0 ? 
                    "↗️ {$paramedisGrowth}% from last month | Avg: Rp " . number_format($avgParamedisRevenue) . "/staff" : 
                    "↘️ {$paramedisGrowth}% from last month | Avg: Rp " . number_format($avgParamedisRevenue) . "/staff")
                ->descriptionIcon($paramedisGrowth >= 0 ? 
                    'heroicon-m-arrow-trending-up' : 
                    'heroicon-m-arrow-trending-down')
                ->chart($paramedisChart->toArray())
                ->color($paramedisGrowth >= 0 ? 'success' : 'danger'),
                
            Stat::make('📋 Non-Paramedis Revenue', 'Rp ' . number_format($currentNonParamedisRevenue))
                ->description($nonParamedisGrowth >= 0 ? 
                    "↗️ {$nonParamedisGrowth}% from last month | Avg: Rp " . number_format($avgNonParamedisRevenue) . "/staff" : 
                    "↘️ {$nonParamedisGrowth}% from last month | Avg: Rp " . number_format($avgNonParamedisRevenue) . "/staff")
                ->descriptionIcon($nonParamedisGrowth >= 0 ? 
                    'heroicon-m-arrow-trending-up' : 
                    'heroicon-m-arrow-trending-down')
                ->chart($nonParamedisChart->toArray())
                ->color($nonParamedisGrowth >= 0 ? 'success' : 'danger'),
                
            Stat::make('💰 Total Monthly Revenue', 'Rp ' . number_format($totalRevenue))
                ->description("Distribution: " . 
                    ($totalRevenue > 0 ? round(($currentDokterRevenue / $totalRevenue) * 100, 1) : 0) . "% Dokter | " .
                    ($totalRevenue > 0 ? round(($currentParamedisRevenue / $totalRevenue) * 100, 1) : 0) . "% Paramedis | " .
                    ($totalRevenue > 0 ? round(($currentNonParamedisRevenue / $totalRevenue) * 100, 1) : 0) . "% Non-Paramedis")
                ->descriptionIcon('heroicon-m-chart-pie')
                ->color('info'),
        ];
    }
}