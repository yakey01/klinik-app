<?php

namespace App\Filament\Manajer\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Pasien;
use App\Models\Tindakan;
use App\Models\Pegawai;
use App\Models\PermohonanCuti;
use Illuminate\Support\Facades\DB;

class OperationsDashboardWidget extends BaseWidget
{
    protected ?string $heading = '🏥 Operational Analytics';
    
    protected static ?int $sort = 3;
    
    protected int|string|array $columnSpan = 'full';
    
    protected static bool $isLazy = false;
    
    protected function getStats(): array
    {
        $today = today();
        $currentWeek = [now()->startOfWeek(), now()->endOfWeek()];
        $currentMonth = now()->month;
        $currentYear = now()->year;
        $previousMonth = now()->subMonth()->month;
        $previousYear = now()->subMonth()->year;
        
        // Today's metrics
        $todayPatients = Pasien::whereDate('created_at', $today)->count();
        $yesterdayPatients = Pasien::whereDate('created_at', $today->copy()->subDay())->count();
        $patientDailyGrowth = $yesterdayPatients > 0 ? 
            round((($todayPatients - $yesterdayPatients) / $yesterdayPatients) * 100, 1) : 0;
        
        // Weekly metrics
        $weeklyPatients = Pasien::whereBetween('created_at', $currentWeek)->count();
        $previousWeekPatients = Pasien::whereBetween('created_at', [
            now()->subWeek()->startOfWeek(), 
            now()->subWeek()->endOfWeek()
        ])->count();
        $weeklyGrowth = $previousWeekPatients > 0 ? 
            round((($weeklyPatients - $previousWeekPatients) / $previousWeekPatients) * 100, 1) : 0;
        
        // Procedure metrics
        $todayProcedures = Tindakan::whereDate('created_at', $today)->count();
        $weeklyProcedures = Tindakan::whereBetween('created_at', $currentWeek)->count();
        $monthlyProcedures = Tindakan::whereMonth('created_at', $currentMonth)
            ->whereYear('created_at', $currentYear)
            ->count();
        $previousMonthProcedures = Tindakan::whereMonth('created_at', $previousMonth)
            ->whereYear('created_at', $previousYear)
            ->count();
        $procedureGrowth = $previousMonthProcedures > 0 ? 
            round((($monthlyProcedures - $previousMonthProcedures) / $previousMonthProcedures) * 100, 1) : 0;
        
        // Staff metrics
        $activeStaff = Pegawai::where('updated_at', '>=', now()->subDays(7))->count();
        $totalStaff = Pegawai::count();
        $staffUtilization = $totalStaff > 0 ? round(($activeStaff / $totalStaff) * 100, 1) : 0;
        
        // Leave requests
        $pendingLeaves = PermohonanCuti::where('status', 'Menunggu')->count();
        $approvedLeavesToday = PermohonanCuti::where('status', 'Disetujui')
            ->whereDate('updated_at', $today)
            ->count();
        
        // Capacity utilization
        $avgDailyCapacity = 100; // Assumed daily capacity
        $capacityUtilization = min(100, ($todayPatients / $avgDailyCapacity) * 100);
        
        // Patient flow trend (last 7 days)
        $patientTrend = collect();
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $dailyPatients = Pasien::whereDate('created_at', $date)->count();
            $patientTrend->push($dailyPatients);
        }
        
        // Procedure trend (last 7 days)
        $procedureTrend = collect();
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $dailyProcedures = Tindakan::whereDate('created_at', $date)->count();
            $procedureTrend->push($dailyProcedures);
        }
        
        return [
            Stat::make('Today\'s Patient Flow', $todayPatients)
                ->description($patientDailyGrowth >= 0 ? 
                    "+{$patientDailyGrowth}% from yesterday" : 
                    "{$patientDailyGrowth}% from yesterday")
                ->descriptionIcon($patientDailyGrowth >= 0 ? 
                    'heroicon-m-arrow-trending-up' : 
                    'heroicon-m-arrow-trending-down')
                ->chart($patientTrend->toArray())
                ->color($patientDailyGrowth >= 0 ? 'success' : 'warning'),
                
            Stat::make('Weekly Registrations', $weeklyPatients)
                ->description($weeklyGrowth >= 0 ? 
                    "+{$weeklyGrowth}% from last week" : 
                    "{$weeklyGrowth}% from last week")
                ->descriptionIcon($weeklyGrowth >= 0 ? 
                    'heroicon-m-arrow-trending-up' : 
                    'heroicon-m-arrow-trending-down')
                ->color($weeklyGrowth >= 0 ? 'success' : 'warning'),
                
            Stat::make('Monthly Procedures', $monthlyProcedures)
                ->description($procedureGrowth >= 0 ? 
                    "+{$procedureGrowth}% from last month" : 
                    "{$procedureGrowth}% from last month")
                ->descriptionIcon($procedureGrowth >= 0 ? 
                    'heroicon-m-arrow-trending-up' : 
                    'heroicon-m-arrow-trending-down')
                ->chart($procedureTrend->toArray())
                ->color($procedureGrowth >= 0 ? 'success' : 'warning'),
                
            Stat::make('Capacity Utilization', "{$capacityUtilization}%")
                ->description("{$todayPatients}/{$avgDailyCapacity} daily capacity")
                ->descriptionIcon($capacityUtilization > 80 ? 
                    'heroicon-m-exclamation-triangle' : 
                    'heroicon-m-check-circle')
                ->color($capacityUtilization > 90 ? 'danger' : 
                    ($capacityUtilization > 80 ? 'warning' : 'success')),
                
            Stat::make('Staff Productivity', "{$staffUtilization}%")
                ->description("{$activeStaff}/{$totalStaff} staff active this week")
                ->descriptionIcon('heroicon-m-users')
                ->color($staffUtilization >= 80 ? 'success' : 
                    ($staffUtilization >= 60 ? 'warning' : 'danger')),
                
            Stat::make('Leave Management', $pendingLeaves)
                ->description($approvedLeavesToday > 0 ? 
                    "{$approvedLeavesToday} approved today" : 
                    "Pending approvals")
                ->descriptionIcon($pendingLeaves > 5 ? 
                    'heroicon-m-exclamation-triangle' : 
                    'heroicon-m-calendar')
                ->color($pendingLeaves > 10 ? 'danger' : 
                    ($pendingLeaves > 5 ? 'warning' : 'success')),
        ];
    }

    private function calculateAverageWaitTime(): int
    {
        // Enhanced calculation based on actual patient flow
        $todayProcedures = Tindakan::whereDate('created_at', today())->count();
        $todayPatients = Pasien::whereDate('created_at', today())->count();
        
        // Base wait time calculation with realistic factors
        $baseWaitTime = 15; // Base wait time in minutes
        $procedureImpact = $todayProcedures > 0 ? min(30, $todayProcedures * 1.5) : 0;
        $patientVolumeImpact = $todayPatients > 20 ? ($todayPatients - 20) * 0.5 : 0;
        
        $estimatedWaitTime = $baseWaitTime + $procedureImpact + $patientVolumeImpact;
        
        return min(90, max(5, round($estimatedWaitTime))); // Cap between 5-90 minutes
    }
}