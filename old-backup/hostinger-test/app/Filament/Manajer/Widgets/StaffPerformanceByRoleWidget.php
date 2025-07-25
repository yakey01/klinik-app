<?php

namespace App\Filament\Manajer\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Pegawai;
use App\Models\Dokter;
use App\Models\Tindakan;
use Illuminate\Support\Facades\DB;

class StaffPerformanceByRoleWidget extends BaseWidget
{
    protected static ?int $sort = 2;
    
    protected int|string|array $columnSpan = 'full';
    
    protected static bool $isLazy = false;
    
    protected function getStats(): array
    {
        $currentMonth = now()->month;
        $currentYear = now()->year;
        
        // Paramedis Performance
        $paramedisCount = Pegawai::where('jenis_pegawai', 'Paramedis')->where('aktif', true)->count();
        $paramedisProcs = Tindakan::whereNotNull('paramedis_id')
            ->whereMonth('created_at', $currentMonth)
            ->whereYear('created_at', $currentYear)
            ->count();
        $paramedisRevenue = Tindakan::whereNotNull('paramedis_id')
            ->whereMonth('created_at', $currentMonth)
            ->whereYear('created_at', $currentYear)
            ->sum('jasa_paramedis');
        
        // Non-Paramedis Performance
        $nonParamedisCount = Pegawai::where('jenis_pegawai', 'Non-Paramedis')->where('aktif', true)->count();
        $nonParamedisProcs = Tindakan::whereNotNull('non_paramedis_id')
            ->whereMonth('created_at', $currentMonth)
            ->whereYear('created_at', $currentYear)
            ->count();
        $nonParamedisRevenue = Tindakan::whereNotNull('non_paramedis_id')
            ->whereMonth('created_at', $currentMonth)
            ->whereYear('created_at', $currentYear)
            ->sum('jasa_non_paramedis');
        
        // Dokter Performance
        $dokterCount = Dokter::where('aktif', true)->count();
        $dokterProcs = Tindakan::whereNotNull('dokter_id')
            ->whereMonth('created_at', $currentMonth)
            ->whereYear('created_at', $currentYear)
            ->count();
        $dokterRevenue = Tindakan::whereNotNull('dokter_id')
            ->whereMonth('created_at', $currentMonth)
            ->whereYear('created_at', $currentYear)
            ->sum('jasa_dokter');
        
        // Calculate efficiency rates
        $paramedisEfficiency = $paramedisCount > 0 ? round($paramedisProcs / $paramedisCount, 1) : 0;
        $nonParamedisEfficiency = $nonParamedisCount > 0 ? round($nonParamedisProcs / $nonParamedisCount, 1) : 0;
        $dokterEfficiency = $dokterCount > 0 ? round($dokterProcs / $dokterCount, 1) : 0;
        
        // Trend data for charts
        $paramedisChart = collect();
        $dokterChart = collect();
        $nonParamedisChart = collect();
        
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            
            $paramedisChart->push(
                Tindakan::whereNotNull('paramedis_id')
                    ->whereDate('created_at', $date)
                    ->count()
            );
            
            $dokterChart->push(
                Tindakan::whereNotNull('dokter_id')
                    ->whereDate('created_at', $date)
                    ->count()
            );
            
            $nonParamedisChart->push(
                Tindakan::whereNotNull('non_paramedis_id')
                    ->whereDate('created_at', $date)
                    ->count()
            );
        }
        
        return [
            Stat::make('👨‍⚕️ Dokter Performance', $dokterProcs . ' procedures')
                ->description("🏥 {$dokterCount} active doctors | ⚡ {$dokterEfficiency} avg/doctor")
                ->descriptionIcon('heroicon-m-user-circle')
                ->chart($dokterChart->toArray())
                ->color($dokterEfficiency >= 10 ? 'success' : ($dokterEfficiency >= 5 ? 'warning' : 'danger')),
                
            Stat::make('🏥 Paramedis Performance', $paramedisProcs . ' procedures')
                ->description("👥 {$paramedisCount} active staff | ⚡ {$paramedisEfficiency} avg/staff")
                ->descriptionIcon('heroicon-m-user-group')
                ->chart($paramedisChart->toArray())
                ->color($paramedisEfficiency >= 8 ? 'success' : ($paramedisEfficiency >= 4 ? 'warning' : 'danger')),
                
            Stat::make('📋 Non-Paramedis Performance', $nonParamedisProcs . ' procedures')
                ->description("🏢 {$nonParamedisCount} active staff | ⚡ {$nonParamedisEfficiency} avg/staff")
                ->descriptionIcon('heroicon-m-building-office')
                ->chart($nonParamedisChart->toArray())
                ->color($nonParamedisEfficiency >= 3 ? 'success' : ($nonParamedisEfficiency >= 1 ? 'warning' : 'danger')),
                
            Stat::make('💰 Total Revenue Contribution', 'Rp ' . number_format($dokterRevenue + $paramedisRevenue + $nonParamedisRevenue))
                ->description("👨‍⚕️ Rp " . number_format($dokterRevenue) . " | 🏥 Rp " . number_format($paramedisRevenue) . " | 📋 Rp " . number_format($nonParamedisRevenue))
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('info'),
        ];
    }
}