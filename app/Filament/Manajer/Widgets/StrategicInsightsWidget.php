<?php

namespace App\Filament\Manajer\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Pendapatan;
use App\Models\Pengeluaran;
use App\Models\Pasien;
use App\Models\Tindakan;
use App\Models\Pegawai;
use App\Models\PermohonanCuti;
use Illuminate\Support\Facades\DB;

class StrategicInsightsWidget extends BaseWidget
{
    protected ?string $heading = '📊 Strategic Business Insights';
    
    protected static ?int $sort = 6;
    
    protected int|string|array $columnSpan = 'full';
    
    protected static bool $isLazy = false;
    
    protected function getStats(): array
    {
        $currentMonth = now()->month;
        $currentYear = now()->year;
        $previousMonth = now()->subMonth()->month;
        $previousYear = now()->subMonth()->year;
        
        // Financial metrics
        $monthlyIncome = Pendapatan::whereMonth('created_at', $currentMonth)
            ->whereYear('created_at', $currentYear)
            ->sum('nominal');
        $monthlyExpense = Pengeluaran::whereMonth('created_at', $currentMonth)
            ->whereYear('created_at', $currentYear)
            ->sum('nominal');
        $profit = $monthlyIncome - $monthlyExpense;
        
        $previousMonthIncome = Pendapatan::whereMonth('created_at', $previousMonth)
            ->whereYear('created_at', $previousYear)
            ->sum('nominal');
        $previousMonthExpense = Pengeluaran::whereMonth('created_at', $previousMonth)
            ->whereYear('created_at', $previousYear)
            ->sum('nominal');
        $previousProfit = $previousMonthIncome - $previousMonthExpense;
        
        $profitGrowth = $previousProfit != 0 ? 
            round((($profit - $previousProfit) / abs($previousProfit)) * 100, 1) : 0;
        
        // Patient satisfaction and retention
        $currentPatients = Pasien::whereMonth('created_at', $currentMonth)
            ->whereYear('created_at', $currentYear)
            ->count();
        $returningPatients = Pasien::whereMonth('created_at', $currentMonth)
            ->whereYear('created_at', $currentYear)
            ->where('created_at', '>', now()->subYear())
            ->count();
        $retentionRate = $currentPatients > 0 ? round(($returningPatients / $currentPatients) * 100, 1) : 0;
        
        // Operational efficiency
        $totalProcedures = Tindakan::whereMonth('created_at', $currentMonth)
            ->whereYear('created_at', $currentYear)
            ->count();
        $revenuePerProcedure = $totalProcedures > 0 ? $monthlyIncome / $totalProcedures : 0;
        
        // Staff productivity
        $activeStaff = Pegawai::where('updated_at', '>=', now()->subDays(30))->count();
        $totalStaff = Pegawai::count();
        $revenuePerStaff = $activeStaff > 0 ? $monthlyIncome / $activeStaff : 0;
        
        // Growth indicators
        $quarterlyRevenue = Pendapatan::whereBetween('created_at', [
            now()->subMonths(3),
            now()
        ])->sum('nominal');
        $previousQuarterRevenue = Pendapatan::whereBetween('created_at', [
            now()->subMonths(6),
            now()->subMonths(3)
        ])->sum('nominal');
        $quarterlyGrowth = $previousQuarterRevenue > 0 ? 
            round((($quarterlyRevenue - $previousQuarterRevenue) / $previousQuarterRevenue) * 100, 1) : 0;
        
        // Market position indicators
        $avgProcedureValue = $totalProcedures > 0 ? $monthlyIncome / $totalProcedures : 0;
        $costPerAcquisition = $currentPatients > 0 ? $monthlyExpense / $currentPatients : 0;
        
        // Capacity utilization - calculate based on actual staff productivity
        $avgDailyProcedures = $totalProcedures / now()->day; // Average procedures per day this month
        $avgProceduresPerStaff = $totalStaff > 0 ? $avgDailyProcedures / $totalStaff : 0;
        $capacityUtilization = $avgProceduresPerStaff > 0 ? min(100, round($avgProceduresPerStaff * 10, 1)) : 0; // Scale to percentage
        
        // Employee satisfaction proxy
        $pendingLeaves = PermohonanCuti::where('status', 'Menunggu')->count();
        $approvedLeaves = PermohonanCuti::where('status', 'Disetujui')
            ->whereMonth('approved_at', $currentMonth)
            ->count();
        $leaveApprovalRate = ($pendingLeaves + $approvedLeaves) > 0 ? 
            round(($approvedLeaves / ($pendingLeaves + $approvedLeaves)) * 100, 1) : 100;
        
        return [
            Stat::make('Monthly Profit', 'Rp ' . number_format($profit))
                ->description($profitGrowth >= 0 ? 
                    "+{$profitGrowth}% from last month" : 
                    "{$profitGrowth}% from last month")
                ->descriptionIcon($profit > 0 ? 
                    'heroicon-m-arrow-trending-up' : 
                    'heroicon-m-arrow-trending-down')
                ->color($profit > 0 ? 'success' : 'danger'),
                
            Stat::make('Profit Margin', $monthlyIncome > 0 ? round(($profit / $monthlyIncome) * 100, 1) . '%' : '0%')
                ->description('Current month efficiency')
                ->descriptionIcon('heroicon-m-calculator')
                ->color($monthlyIncome > 0 && ($profit / $monthlyIncome) > 0.2 ? 'success' : 
                    ($monthlyIncome > 0 && ($profit / $monthlyIncome) > 0.1 ? 'warning' : 'danger')),
                
            Stat::make('Patient Retention', "{$retentionRate}%")
                ->description("{$returningPatients} of {$currentPatients} returning")
                ->descriptionIcon($retentionRate > 70 ? 
                    'heroicon-m-heart' : 
                    'heroicon-m-arrow-trending-down')
                ->color($retentionRate > 70 ? 'success' : 
                    ($retentionRate > 50 ? 'warning' : 'danger')),
                
            Stat::make('Quarterly Growth', "{$quarterlyGrowth}%")
                ->description('Revenue growth trend')
                ->descriptionIcon($quarterlyGrowth >= 0 ? 
                    'heroicon-m-arrow-trending-up' : 
                    'heroicon-m-arrow-trending-down')
                ->color($quarterlyGrowth > 10 ? 'success' : 
                    ($quarterlyGrowth > 0 ? 'warning' : 'danger')),
                
            Stat::make('Revenue per Staff', 'Rp ' . number_format($revenuePerStaff))
                ->description('Monthly staff productivity')
                ->descriptionIcon('heroicon-m-users')
                ->color($revenuePerStaff > 5000000 ? 'success' : 
                    ($revenuePerStaff > 3000000 ? 'warning' : 'danger')),
                
            Stat::make('Capacity Utilization', "{$capacityUtilization}%")
                ->description('Operational efficiency')
                ->descriptionIcon($capacityUtilization > 80 ? 
                    'heroicon-m-bolt' : 
                    'heroicon-m-chart-bar')
                ->color($capacityUtilization > 80 ? 'success' : 
                    ($capacityUtilization > 60 ? 'warning' : 'danger')),
                
            Stat::make('Cost per Patient', 'Rp ' . number_format($costPerAcquisition))
                ->description('Patient acquisition cost')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color($costPerAcquisition < 100000 ? 'success' : 
                    ($costPerAcquisition < 200000 ? 'warning' : 'danger')),
                
            Stat::make('Employee Satisfaction', "{$leaveApprovalRate}%")
                ->description('Leave approval rate')
                ->descriptionIcon($leaveApprovalRate > 80 ? 
                    'heroicon-m-face-smile' : 
                    'heroicon-m-face-frown')
                ->color($leaveApprovalRate > 80 ? 'success' : 
                    ($leaveApprovalRate > 60 ? 'warning' : 'danger')),
        ];
    }
}