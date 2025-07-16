<?php

namespace App\Filament\Manajer\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Pasien;
use App\Models\Pendapatan;
use App\Models\Pegawai;
use App\Models\Tindakan;
use App\Models\JenisTindakan;
use App\Models\PermohonanCuti;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class ExtendedKPIMetricsWidget extends BaseWidget
{
    protected ?string $heading = '📊 Extended KPI Metrics';
    
    protected static ?int $sort = 2;
    
    protected int|string|array $columnSpan = 'full';
    
    protected static bool $isLazy = false;
    
    protected function getStats(): array
    {
        $currentMonth = now()->month;
        $currentYear = now()->year;
        $previousMonth = now()->subMonth()->month;
        $previousYear = now()->subMonth()->year;
        
        return [
            $this->getPatientSatisfactionStat(),
            $this->getRevenuePerProcedureStat(),
            $this->getQualityMetricsStat(),
            $this->getTrainingCompletionStat(),
            $this->getPatientRetentionStat(),
            $this->getResourceUtilizationStat(),
            $this->getStaffEngagementStat(),
            $this->getOperationalEfficiencyStat(),
        ];
    }

    private function getPatientSatisfactionStat(): Stat
    {
        // Simulated patient satisfaction based on return visits and procedure success
        $currentMonth = now()->month;
        $currentYear = now()->year;
        
        // Calculate return patient rate as satisfaction proxy
        $totalPatients = Pasien::whereMonth('created_at', $currentMonth)
            ->whereYear('created_at', $currentYear)
            ->count();
            
        $returningPatients = Pasien::whereMonth('created_at', $currentMonth)
            ->whereYear('created_at', $currentYear)
            ->whereHas('tindakan', function($query) {
                $query->where('created_at', '<', now()->subMonth());
            })
            ->count();
            
        $satisfactionRate = $totalPatients > 0 ? round(($returningPatients / $totalPatients) * 100, 1) : 0;
        
        // Simulate monthly trend
        $trend = collect(range(6, 0))->map(function ($monthsAgo) {
            $date = now()->subMonths($monthsAgo);
            $patients = Pasien::whereMonth('created_at', $date->month)
                ->whereYear('created_at', $date->year)
                ->count();
            $returning = Pasien::whereMonth('created_at', $date->month)
                ->whereYear('created_at', $date->year)
                ->whereHas('tindakan', function($query) use ($date) {
                    $query->where('created_at', '<', $date->subMonth());
                })
                ->count();
            return $patients > 0 ? round(($returning / $patients) * 100, 1) : 0;
        });
        
        return Stat::make('Patient Satisfaction', "{$satisfactionRate}%")
            ->description('Based on return visit rate')
            ->descriptionIcon('heroicon-m-heart')
            ->chart($trend->toArray())
            ->color($satisfactionRate >= 75 ? 'success' : ($satisfactionRate >= 60 ? 'warning' : 'danger'));
    }

    private function getRevenuePerProcedureStat(): Stat
    {
        $currentMonth = now()->month;
        $currentYear = now()->year;
        
        $totalRevenue = Pendapatan::whereMonth('created_at', $currentMonth)
            ->whereYear('created_at', $currentYear)
            ->sum('nominal');
            
        $totalProcedures = Tindakan::whereMonth('created_at', $currentMonth)
            ->whereYear('created_at', $currentYear)
            ->count();
            
        $revenuePerProcedure = $totalProcedures > 0 ? $totalRevenue / $totalProcedures : 0;
        
        // Calculate previous month for comparison
        $previousRevenue = Pendapatan::whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->subMonth()->year)
            ->sum('nominal');
            
        $previousProcedures = Tindakan::whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->subMonth()->year)
            ->count();
            
        $previousRevenuePerProcedure = $previousProcedures > 0 ? $previousRevenue / $previousProcedures : 0;
        
        $change = $previousRevenuePerProcedure > 0 ? 
            round((($revenuePerProcedure - $previousRevenuePerProcedure) / $previousRevenuePerProcedure) * 100, 1) : 0;
        
        return Stat::make('Revenue per Procedure', 'Rp ' . number_format($revenuePerProcedure))
            ->description($change >= 0 ? "+{$change}% from last month" : "{$change}% from last month")
            ->descriptionIcon($change >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
            ->color($change >= 0 ? 'success' : 'danger');
    }

    private function getQualityMetricsStat(): Stat
    {
        $currentMonth = now()->month;
        $currentYear = now()->year;
        
        // Calculate quality score based on procedure completion rate and patient outcomes
        $totalProcedures = Tindakan::whereMonth('created_at', $currentMonth)
            ->whereYear('created_at', $currentYear)
            ->count();
            
        $completedProcedures = Tindakan::whereMonth('created_at', $currentMonth)
            ->whereYear('created_at', $currentYear)
            ->whereNotNull('updated_at') // Assuming updated_at indicates completion
            ->count();
            
        $qualityScore = $totalProcedures > 0 ? round(($completedProcedures / $totalProcedures) * 100, 1) : 0;
        
        // Simulate procedure type diversity as quality indicator
        $procedureTypes = JenisTindakan::whereHas('tindakan', function($query) use ($currentMonth, $currentYear) {
            $query->whereMonth('created_at', $currentMonth)
                  ->whereYear('created_at', $currentYear);
        })->count();
        
        $diversityBonus = min(10, $procedureTypes * 2); // Bonus points for procedure diversity
        $finalQualityScore = min(100, $qualityScore + $diversityBonus);
        
        return Stat::make('Quality Score', "{$finalQualityScore}%")
            ->description("Based on {$procedureTypes} procedure types")
            ->descriptionIcon('heroicon-m-star')
            ->color($finalQualityScore >= 85 ? 'success' : ($finalQualityScore >= 70 ? 'warning' : 'danger'));
    }

    private function getTrainingCompletionStat(): Stat
    {
        // Simulate training completion based on staff activity and procedure diversity
        $activeStaff = Pegawai::where('aktif', true)->count();
        $staffWithProcedures = Pegawai::whereHas('tindakanAsParamedis', function($query) {
            $query->whereMonth('created_at', now()->month);
        })->orWhereHas('tindakanAsNonParamedis', function($query) {
            $query->whereMonth('created_at', now()->month);
        })->count();
        
        $trainingCompletion = $activeStaff > 0 ? round(($staffWithProcedures / $activeStaff) * 100, 1) : 0;
        
        // Simulate trend data
        $trend = collect(range(6, 0))->map(function ($monthsAgo) {
            $date = now()->subMonths($monthsAgo);
            $active = Pegawai::where('aktif', true)->count();
            $withProcedures = Pegawai::whereHas('tindakanAsParamedis', function($query) use ($date) {
                $query->whereMonth('created_at', $date->month)
                      ->whereYear('created_at', $date->year);
            })->orWhereHas('tindakanAsNonParamedis', function($query) use ($date) {
                $query->whereMonth('created_at', $date->month)
                      ->whereYear('created_at', $date->year);
            })->count();
            return $active > 0 ? round(($withProcedures / $active) * 100, 1) : 0;
        });
        
        return Stat::make('Training Completion', "{$trainingCompletion}%")
            ->description('Staff active in procedures')
            ->descriptionIcon('heroicon-m-academic-cap')
            ->chart($trend->toArray())
            ->color($trainingCompletion >= 80 ? 'success' : ($trainingCompletion >= 60 ? 'warning' : 'danger'));
    }

    private function getPatientRetentionStat(): Stat
    {
        $currentMonth = now()->month;
        $currentYear = now()->year;
        
        // Calculate patient retention rate
        $patientsThisMonth = Pasien::whereMonth('created_at', $currentMonth)
            ->whereYear('created_at', $currentYear)
            ->count();
            
        $returningPatients = Pasien::whereMonth('created_at', $currentMonth)
            ->whereYear('created_at', $currentYear)
            ->whereHas('tindakan', function($query) {
                $query->where('created_at', '<', now()->startOfMonth());
            })
            ->count();
            
        $retentionRate = $patientsThisMonth > 0 ? round(($returningPatients / $patientsThisMonth) * 100, 1) : 0;
        
        // Calculate churn rate
        $churnRate = 100 - $retentionRate;
        
        return Stat::make('Patient Retention', "{$retentionRate}%")
            ->description("Churn rate: {$churnRate}%")
            ->descriptionIcon('heroicon-m-users')
            ->color($retentionRate >= 70 ? 'success' : ($retentionRate >= 50 ? 'warning' : 'danger'));
    }

    private function getResourceUtilizationStat(): Stat
    {
        $currentMonth = now()->month;
        $currentYear = now()->year;
        
        $activeStaff = Pegawai::where('aktif', true)->count();
        $workingDays = now()->daysInMonth;
        
        $totalProcedures = Tindakan::whereMonth('created_at', $currentMonth)
            ->whereYear('created_at', $currentYear)
            ->count();
            
        // Calculate realistic capacity based on actual historical performance
        $avgDailyProcedures = $totalProcedures / now()->day;
        $avgProceduresPerStaff = $activeStaff > 0 ? $avgDailyProcedures / $activeStaff : 0;
        $utilization = $avgProceduresPerStaff > 0 ? min(100, round($avgProceduresPerStaff * 5, 1)) : 0;
        
        return Stat::make('Resource Utilization', "{$utilization}%")
            ->description("{$totalProcedures} procedures this month")
            ->descriptionIcon('heroicon-m-cog')
            ->color($utilization >= 70 ? 'success' : ($utilization >= 50 ? 'warning' : 'danger'));
    }

    private function getStaffEngagementStat(): Stat
    {
        $currentMonth = now()->month;
        $currentYear = now()->year;
        
        // Calculate engagement based on leave requests and activity
        $totalStaff = Pegawai::where('aktif', true)->count();
        $leaveRequests = PermohonanCuti::whereMonth('created_at', $currentMonth)
            ->whereYear('created_at', $currentYear)
            ->count();
            
        $activeStaff = Pegawai::where('updated_at', '>=', now()->subDays(7))->count();
        
        $engagementScore = $totalStaff > 0 ? round(($activeStaff / $totalStaff) * 100, 1) : 0;
        
        // Adjust for leave requests (high leave requests might indicate disengagement)
        $leaveImpact = $totalStaff > 0 ? min(10, ($leaveRequests / $totalStaff) * 100) : 0;
        $finalEngagementScore = max(0, $engagementScore - $leaveImpact);
        
        return Stat::make('Staff Engagement', "{$finalEngagementScore}%")
            ->description("{$leaveRequests} leave requests this month")
            ->descriptionIcon('heroicon-m-face-smile')
            ->color($finalEngagementScore >= 80 ? 'success' : ($finalEngagementScore >= 60 ? 'warning' : 'danger'));
    }

    private function getOperationalEfficiencyStat(): Stat
    {
        $currentMonth = now()->month;
        $currentYear = now()->year;
        
        // Calculate efficiency based on procedures per staff and revenue
        $totalProcedures = Tindakan::whereMonth('created_at', $currentMonth)
            ->whereYear('created_at', $currentYear)
            ->count();
            
        $totalRevenue = Pendapatan::whereMonth('created_at', $currentMonth)
            ->whereYear('created_at', $currentYear)
            ->sum('nominal');
            
        $activeStaff = Pegawai::where('aktif', true)->count();
        
        $proceduresPerStaff = $activeStaff > 0 ? round($totalProcedures / $activeStaff, 1) : 0;
        $revenuePerStaff = $activeStaff > 0 ? $totalRevenue / $activeStaff : 0;
        
        // Calculate efficiency based on historical average
        $avgProceduresPerStaff = Pegawai::where('aktif', true)
            ->withCount(['tindakanAsParamedis', 'tindakanAsNonParamedis'])
            ->get()
            ->avg(function($pegawai) {
                return $pegawai->tindakan_as_paramedis_count + $pegawai->tindakan_as_non_paramedis_count;
            });
        
        $efficiency = $avgProceduresPerStaff > 0 ? min(100, ($proceduresPerStaff / $avgProceduresPerStaff) * 100) : 0;
        
        return Stat::make('Operational Efficiency', number_format($efficiency, 1) . '%')
            ->description("Rp " . number_format($revenuePerStaff) . " per staff")
            ->descriptionIcon('heroicon-m-chart-bar')
            ->color($efficiency >= 80 ? 'success' : ($efficiency >= 60 ? 'warning' : 'danger'));
    }
}