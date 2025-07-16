<?php

namespace App\Filament\Manajer\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Tindakan;
use App\Models\Pegawai;
use App\Models\Dokter;
use App\Models\Pasien;
use Illuminate\Support\Facades\DB;

class ProductivityMetricsWidget extends BaseWidget
{
    protected static ?int $sort = 4;
    
    protected int|string|array $columnSpan = 'full';
    
    protected static bool $isLazy = false;
    
    protected function getStats(): array
    {
        $currentMonth = now()->month;
        $currentYear = now()->year;
        
        // Get working days in current month (excluding weekends)
        $currentDate = now();
        $daysInMonth = $currentDate->daysInMonth;
        $weekendDays = 0;
        
        // Count weekends in current month
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = $currentDate->copy()->day($day);
            if ($date->isWeekend()) {
                $weekendDays++;
            }
        }
        
        $workingDays = $daysInMonth - $weekendDays;
        
        // Daily productivity metrics
        $dailyDokterProcs = Tindakan::whereNotNull('dokter_id')
            ->whereMonth('created_at', $currentMonth)
            ->whereYear('created_at', $currentYear)
            ->count() / max($workingDays, 1);
            
        $dailyParamedisProcs = Tindakan::whereNotNull('paramedis_id')
            ->whereMonth('created_at', $currentMonth)
            ->whereYear('created_at', $currentYear)
            ->count() / max($workingDays, 1);
            
        $dailyNonParamedisProcs = Tindakan::whereNotNull('non_paramedis_id')
            ->whereMonth('created_at', $currentMonth)
            ->whereYear('created_at', $currentYear)
            ->count() / max($workingDays, 1);
        
        // Patient load distribution
        $totalPatients = Pasien::whereMonth('created_at', $currentMonth)
            ->whereYear('created_at', $currentYear)
            ->count();
            
        $totalProcedures = Tindakan::whereMonth('created_at', $currentMonth)
            ->whereYear('created_at', $currentYear)
            ->count();
        
        $avgProceduresPerPatient = $totalPatients > 0 ? round($totalProcedures / $totalPatients, 1) : 0;
        
        // Role-based workload analysis
        $dokterWorkload = Tindakan::whereNotNull('dokter_id')
            ->whereMonth('created_at', $currentMonth)
            ->whereYear('created_at', $currentYear)
            ->selectRaw('COUNT(*) as procedures, dokter_id')
            ->groupBy('dokter_id')
            ->get();
        
        $paramedisWorkload = Tindakan::whereNotNull('paramedis_id')
            ->whereMonth('created_at', $currentMonth)
            ->whereYear('created_at', $currentYear)
            ->selectRaw('COUNT(*) as procedures, paramedis_id')
            ->groupBy('paramedis_id')
            ->get();
        
        // Calculate workload distribution
        $dokterWorkloadBalance = $dokterWorkload->isNotEmpty() ? 
            round($dokterWorkload->min('procedures') / max($dokterWorkload->max('procedures'), 1) * 100, 1) : 0;
        $paramedisWorkloadBalance = $paramedisWorkload->isNotEmpty() ? 
            round($paramedisWorkload->min('procedures') / max($paramedisWorkload->max('procedures'), 1) * 100, 1) : 0;
        
        // Top performers
        $topDokter = $dokterWorkload->sortByDesc('procedures')->first();
        $topParamedis = $paramedisWorkload->sortByDesc('procedures')->first();
        
        $topDokterName = $topDokter ? 
            Dokter::find($topDokter->dokter_id)?->nama_lengkap ?? 'Unknown' : 'None';
        $topParamedisName = $topParamedis ? 
            Pegawai::find($topParamedis->paramedis_id)?->nama_lengkap ?? 'Unknown' : 'None';
        
        // Weekly trend
        $weeklyTrend = collect();
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $weeklyTrend->push(
                Tindakan::whereDate('created_at', $date)->count()
            );
        }
        
        return [
            Stat::make('📊 Daily Productivity', round($dailyDokterProcs + $dailyParamedisProcs + $dailyNonParamedisProcs, 1) . ' procedures/day')
                ->description("👨‍⚕️ " . round($dailyDokterProcs, 1) . " | 🏥 " . round($dailyParamedisProcs, 1) . " | 📋 " . round($dailyNonParamedisProcs, 1) . " per day")
                ->descriptionIcon('heroicon-m-chart-bar')
                ->chart($weeklyTrend->toArray())
                ->color('info'),
                
            Stat::make('🎯 Patient Load Efficiency', $avgProceduresPerPatient . ' procedures/patient')
                ->description("📈 {$totalProcedures} procedures for {$totalPatients} patients this month")
                ->descriptionIcon('heroicon-m-users')
                ->color($avgProceduresPerPatient >= 2 ? 'success' : ($avgProceduresPerPatient >= 1 ? 'warning' : 'danger')),
                
            Stat::make('⚖️ Workload Balance', $dokterWorkloadBalance . '%')
                ->description("👨‍⚕️ Doctor balance: {$dokterWorkloadBalance}% | 🏥 Paramedis balance: {$paramedisWorkloadBalance}%")
                ->descriptionIcon('heroicon-m-scale')
                ->color($dokterWorkloadBalance >= 70 ? 'success' : ($dokterWorkloadBalance >= 50 ? 'warning' : 'danger')),
                
            Stat::make('🏆 Top Performers', 'Monthly Leaders')
                ->description("👨‍⚕️ Best Doctor: {$topDokterName} | 🏥 Best Paramedis: {$topParamedisName}")
                ->descriptionIcon('heroicon-m-trophy')
                ->color('success'),
        ];
    }
}