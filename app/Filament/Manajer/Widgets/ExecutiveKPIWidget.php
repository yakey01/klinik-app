<?php

namespace App\Filament\Manajer\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Pasien;
use App\Models\Pendapatan;
use App\Models\Pegawai;
use App\Models\PermohonanCuti;
use App\Models\Tindakan;
use Illuminate\Support\Facades\DB;

class ExecutiveKPIWidget extends BaseWidget
{
    protected static ?int $sort = 1;
    
    protected int|string|array $columnSpan = 'full';
    
    protected static bool $isLazy = false;
    
    protected function getStats(): array
    {
        $currentMonth = now()->month;
        $currentYear = now()->year;
        $previousMonth = now()->subMonth()->month;
        $previousYear = now()->subMonth()->year;
        
        // Patient metrics
        $currentPatients = Pasien::whereMonth('created_at', $currentMonth)
            ->whereYear('created_at', $currentYear)
            ->count();
        $previousPatients = Pasien::whereMonth('created_at', $previousMonth)
            ->whereYear('created_at', $previousYear)
            ->count();
        $patientGrowth = $previousPatients > 0 ? 
            round((($currentPatients - $previousPatients) / $previousPatients) * 100, 1) : 0;
        
        // Revenue metrics
        $currentRevenue = Pendapatan::whereMonth('created_at', $currentMonth)
            ->whereYear('created_at', $currentYear)
            ->sum('nominal');
        $previousRevenue = Pendapatan::whereMonth('created_at', $previousMonth)
            ->whereYear('created_at', $previousYear)
            ->sum('nominal');
        $revenueGrowth = $previousRevenue > 0 ? 
            round((($currentRevenue - $previousRevenue) / $previousRevenue) * 100, 1) : 0;
        
        // Procedures metrics
        $currentProcedures = Tindakan::whereMonth('created_at', $currentMonth)
            ->whereYear('created_at', $currentYear)
            ->count();
        $previousProcedures = Tindakan::whereMonth('created_at', $previousMonth)
            ->whereYear('created_at', $previousYear)
            ->count();
        $procedureGrowth = $previousProcedures > 0 ? 
            round((($currentProcedures - $previousProcedures) / $previousProcedures) * 100, 1) : 0;
        
        // Staff productivity
        $activeStaff = Pegawai::where('updated_at', '>=', now()->subDays(30))->count();
        $totalStaff = Pegawai::count();
        $staffUtilization = $totalStaff > 0 ? round(($activeStaff / $totalStaff) * 100, 1) : 0;
        
        // Approval metrics
        $pendingApprovals = PermohonanCuti::where('status', 'Menunggu')->count();
        $overdueApprovals = PermohonanCuti::where('status', 'Menunggu')
            ->where('created_at', '<=', now()->subDays(7))
            ->count();
        
        // Revenue trend data for chart
        $revenueTrend = collect();
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $dailyRevenue = Pendapatan::whereDate('created_at', $date)->sum('nominal');
            $revenueTrend->push($dailyRevenue / 1000000); // Convert to millions
        }
        
        // Patient trend data for chart
        $patientTrend = collect();
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $dailyPatients = Pasien::whereDate('created_at', $date)->count();
            $patientTrend->push($dailyPatients);
        }
        
        return [
            Stat::make('Monthly Revenue', 'Rp ' . number_format($currentRevenue))
                ->description($revenueGrowth >= 0 ? 
                    "{$revenueGrowth}% increase from last month" : 
                    "{$revenueGrowth}% decrease from last month")
                ->descriptionIcon($revenueGrowth >= 0 ? 
                    'heroicon-m-arrow-trending-up' : 
                    'heroicon-m-arrow-trending-down')
                ->chart($revenueTrend->toArray())
                ->color($revenueGrowth >= 0 ? 'success' : 'danger'),
                
            Stat::make('New Patients', $currentPatients)
                ->description($patientGrowth >= 0 ? 
                    "{$patientGrowth}% increase from last month" : 
                    "{$patientGrowth}% decrease from last month")
                ->descriptionIcon($patientGrowth >= 0 ? 
                    'heroicon-m-arrow-trending-up' : 
                    'heroicon-m-arrow-trending-down')
                ->chart($patientTrend->toArray())
                ->color($patientGrowth >= 0 ? 'success' : 'danger'),
                
            Stat::make('Monthly Procedures', $currentProcedures)
                ->description($procedureGrowth >= 0 ? 
                    "{$procedureGrowth}% increase from last month" : 
                    "{$procedureGrowth}% decrease from last month")
                ->descriptionIcon($procedureGrowth >= 0 ? 
                    'heroicon-m-arrow-trending-up' : 
                    'heroicon-m-arrow-trending-down')
                ->color($procedureGrowth >= 0 ? 'success' : 'danger'),
                
            Stat::make('Staff Utilization', "{$staffUtilization}%")
                ->description("{$activeStaff} of {$totalStaff} staff active")
                ->descriptionIcon('heroicon-m-users')
                ->color($staffUtilization >= 80 ? 'success' : ($staffUtilization >= 60 ? 'warning' : 'danger')),
                
            Stat::make('Pending Approvals', $pendingApprovals)
                ->description($overdueApprovals > 0 ? 
                    "{$overdueApprovals} overdue (>7 days)" : 
                    "All within timeline")
                ->descriptionIcon($overdueApprovals > 0 ? 
                    'heroicon-m-exclamation-triangle' : 
                    'heroicon-m-check-circle')
                ->color($overdueApprovals > 0 ? 'danger' : 'success'),
                
            Stat::make('Avg Revenue per Patient', 
                $currentPatients > 0 ? 
                    'Rp ' . number_format($currentRevenue / $currentPatients) : 
                    'Rp 0')
                ->description('This month\'s efficiency')
                ->descriptionIcon('heroicon-m-calculator')
                ->color('info'),
        ];
    }
}