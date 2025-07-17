<?php

namespace App\Filament\Manajer\Widgets;

use Filament\Widgets\Widget;
use App\Models\Pendapatan;
use App\Models\Pasien;
use App\Models\Tindakan;
use App\Models\Pegawai;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ManajerHeroStatsWidget extends Widget
{
    protected static string $view = 'filament.manajer.widgets.hero-stats-widget';
    
    protected int | string | array $columnSpan = 'full';
    
    protected static ?int $sort = 1;

    public function getViewData(): array
    {
        $currentMonth = Carbon::now();
        $previousMonth = Carbon::now()->subMonth();
        
        // Total Revenue with Growth
        $currentRevenue = Pendapatan::whereMonth('created_at', $currentMonth->month)
            ->whereYear('created_at', $currentMonth->year)
            ->sum('jumlah');
            
        $previousRevenue = Pendapatan::whereMonth('created_at', $previousMonth->month)
            ->whereYear('created_at', $previousMonth->year)
            ->sum('jumlah');
            
        $revenueGrowth = $previousRevenue > 0 
            ? (($currentRevenue - $previousRevenue) / $previousRevenue) * 100 
            : 0;

        // Patient Count with Growth
        $currentPatients = Pasien::whereMonth('created_at', $currentMonth->month)
            ->whereYear('created_at', $currentMonth->year)
            ->count();
            
        $previousPatients = Pasien::whereMonth('created_at', $previousMonth->month)
            ->whereYear('created_at', $previousMonth->year)
            ->count();
            
        $patientGrowth = $previousPatients > 0 
            ? (($currentPatients - $previousPatients) / $previousPatients) * 100 
            : 0;

        // Procedures Count with Growth
        $currentProcedures = Tindakan::whereMonth('created_at', $currentMonth->month)
            ->whereYear('created_at', $currentMonth->year)
            ->count();
            
        $previousProcedures = Tindakan::whereMonth('created_at', $previousMonth->month)
            ->whereYear('created_at', $previousMonth->year)
            ->count();
            
        $procedureGrowth = $previousProcedures > 0 
            ? (($currentProcedures - $previousProcedures) / $previousProcedures) * 100 
            : 0;

        // Staff Performance Overview
        $activeStaff = Pegawai::where('aktif', true)->count();
        $staffEfficiency = $this->calculateStaffEfficiency();

        // Today's Quick Stats
        $todayRevenue = Pendapatan::whereDate('created_at', Carbon::today())->sum('jumlah');
        $todayPatients = Pasien::whereDate('created_at', Carbon::today())->count();
        $todayProcedures = Tindakan::whereDate('created_at', Carbon::today())->count();

        return [
            'stats' => [
                'revenue' => [
                    'current' => $currentRevenue,
                    'previous' => $previousRevenue,
                    'growth' => round($revenueGrowth, 1),
                    'today' => $todayRevenue,
                    'formatted' => 'Rp ' . number_format($currentRevenue, 0, ',', '.'),
                    'icon' => 'heroicon-o-banknotes',
                    'color' => $revenueGrowth >= 0 ? 'success' : 'danger',
                ],
                'patients' => [
                    'current' => $currentPatients,
                    'previous' => $previousPatients,
                    'growth' => round($patientGrowth, 1),
                    'today' => $todayPatients,
                    'formatted' => number_format($currentPatients),
                    'icon' => 'heroicon-o-users',
                    'color' => $patientGrowth >= 0 ? 'success' : 'danger',
                ],
                'procedures' => [
                    'current' => $currentProcedures,
                    'previous' => $previousProcedures,
                    'growth' => round($procedureGrowth, 1),
                    'today' => $todayProcedures,
                    'formatted' => number_format($currentProcedures),
                    'icon' => 'heroicon-o-clipboard-document-check',
                    'color' => $procedureGrowth >= 0 ? 'success' : 'danger',
                ],
                'staff' => [
                    'active' => $activeStaff,
                    'efficiency' => round($staffEfficiency, 1),
                    'formatted' => number_format($activeStaff),
                    'icon' => 'heroicon-o-user-group',
                    'color' => $staffEfficiency >= 75 ? 'success' : ($staffEfficiency >= 50 ? 'warning' : 'danger'),
                ],
            ],
            'trends' => [
                'revenue_trend' => $this->getRevenueTrend(),
                'patient_trend' => $this->getPatientTrend(),
                'procedure_trend' => $this->getProcedureTrend(),
            ],
            'quick_actions' => [
                'pending_approvals' => $this->getPendingApprovals(),
                'critical_alerts' => $this->getCriticalAlerts(),
            ],
        ];
    }

    private function calculateStaffEfficiency(): float
    {
        // Calculate staff efficiency based on procedures per staff member
        $activeStaff = Pegawai::where('aktif', true)->count();
        $monthlyProcedures = Tindakan::whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->count();
        
        if ($activeStaff === 0) return 0;
        
        $proceduresPerStaff = $monthlyProcedures / $activeStaff;
        
        // Assume target is 30 procedures per staff per month
        $targetProceduresPerStaff = 30;
        
        return min(($proceduresPerStaff / $targetProceduresPerStaff) * 100, 100);
    }

    private function getRevenueTrend(): array
    {
        return Pendapatan::selectRaw('DATE(created_at) as date, SUM(jumlah) as total')
            ->whereDate('created_at', '>=', Carbon::now()->subDays(7))
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('total', 'date')
            ->toArray();
    }

    private function getPatientTrend(): array
    {
        return Pasien::selectRaw('DATE(created_at) as date, COUNT(*) as total')
            ->whereDate('created_at', '>=', Carbon::now()->subDays(7))
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('total', 'date')
            ->toArray();
    }

    private function getProcedureTrend(): array
    {
        return Tindakan::selectRaw('DATE(created_at) as date, COUNT(*) as total')
            ->whereDate('created_at', '>=', Carbon::now()->subDays(7))
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('total', 'date')
            ->toArray();
    }

    private function getPendingApprovals(): int
    {
        // Count pending approvals (placeholder - adjust based on your approval system)
        return Tindakan::where('validation_status', 'pending')->count();
    }

    private function getCriticalAlerts(): array
    {
        $alerts = [];
        
        // Low revenue alert
        $todayRevenue = Pendapatan::whereDate('created_at', Carbon::today())->sum('jumlah');
        $avgDailyRevenue = Pendapatan::whereDate('created_at', '>=', Carbon::now()->subDays(30))
            ->selectRaw('AVG(daily_revenue) as avg_revenue')
            ->fromSub(function ($query) {
                $query->selectRaw('DATE(created_at) as date, SUM(jumlah) as daily_revenue')
                    ->from('pendapatans')
                    ->groupBy('date');
            }, 'daily_revenues')
            ->value('avg_revenue') ?? 0;
        
        if ($todayRevenue < $avgDailyRevenue * 0.7) {
            $alerts[] = [
                'type' => 'warning',
                'message' => 'Revenue today is 30% below average',
                'action' => 'Review daily operations',
            ];
        }
        
        // Staff efficiency alert
        $staffEfficiency = $this->calculateStaffEfficiency();
        if ($staffEfficiency < 60) {
            $alerts[] = [
                'type' => 'danger',
                'message' => 'Staff efficiency is below 60%',
                'action' => 'Review staff performance',
            ];
        }
        
        return $alerts;
    }

    public static function canView(): bool
    {
        return auth()->user()?->hasRole('manajer') ?? false;
    }
}