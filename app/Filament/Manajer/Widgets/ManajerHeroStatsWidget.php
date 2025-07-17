<?php

namespace App\Filament\Manajer\Widgets;

use Filament\Widgets\Widget;
use App\Models\PendapatanHarian;
use App\Models\PengeluaranHarian;
use App\Models\JumlahPasienHarian;
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
        $currentRevenue = PendapatanHarian::whereMonth('tanggal_input', $currentMonth->month)
            ->whereYear('tanggal_input', $currentMonth->year)
            ->sum('nominal');
            
        $previousRevenue = PendapatanHarian::whereMonth('tanggal_input', $previousMonth->month)
            ->whereYear('tanggal_input', $previousMonth->year)
            ->sum('nominal');
            
        $revenueGrowth = $previousRevenue > 0 
            ? (($currentRevenue - $previousRevenue) / $previousRevenue) * 100 
            : 0;

        // Patient Count with Growth
        $currentPatients = JumlahPasienHarian::whereMonth('tanggal', $currentMonth->month)
            ->whereYear('tanggal', $currentMonth->year)
            ->sum(DB::raw('jumlah_pasien_umum + jumlah_pasien_bpjs'));
            
        $previousPatients = JumlahPasienHarian::whereMonth('tanggal', $previousMonth->month)
            ->whereYear('tanggal', $previousMonth->year)
            ->sum(DB::raw('jumlah_pasien_umum + jumlah_pasien_bpjs'));
            
        $patientGrowth = $previousPatients > 0 
            ? (($currentPatients - $previousPatients) / $previousPatients) * 100 
            : 0;

        // Procedures Count with Growth
        $currentProcedures = Tindakan::whereMonth('tanggal_tindakan', $currentMonth->month)
            ->whereYear('tanggal_tindakan', $currentMonth->year)
            ->count();
            
        $previousProcedures = Tindakan::whereMonth('tanggal_tindakan', $previousMonth->month)
            ->whereYear('tanggal_tindakan', $previousMonth->year)
            ->count();
            
        $procedureGrowth = $previousProcedures > 0 
            ? (($currentProcedures - $previousProcedures) / $previousProcedures) * 100 
            : 0;

        // Staff Performance Overview
        $activeStaff = Pegawai::where('aktif', true)->count();
        $staffEfficiency = $this->calculateStaffEfficiency();

        // Today's Quick Stats
        $todayRevenue = PendapatanHarian::whereDate('tanggal_input', Carbon::today())->sum('nominal');
        $todayPatients = JumlahPasienHarian::whereDate('tanggal', Carbon::today())->sum(DB::raw('jumlah_pasien_umum + jumlah_pasien_bpjs'));
        $todayProcedures = Tindakan::whereDate('tanggal_tindakan', Carbon::today())->count();

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
        $monthlyProcedures = Tindakan::whereMonth('tanggal_tindakan', Carbon::now()->month)
            ->whereYear('tanggal_tindakan', Carbon::now()->year)
            ->count();
        
        if ($activeStaff === 0) return 0;
        
        $proceduresPerStaff = $monthlyProcedures / $activeStaff;
        
        // Assume target is 30 procedures per staff per month
        $targetProceduresPerStaff = 30;
        
        return min(($proceduresPerStaff / $targetProceduresPerStaff) * 100, 100);
    }

    private function getRevenueTrend(): array
    {
        return PendapatanHarian::selectRaw('DATE(tanggal_input) as date, SUM(nominal) as total')
            ->whereDate('tanggal_input', '>=', Carbon::now()->subDays(7))
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('total', 'date')
            ->toArray();
    }

    private function getPatientTrend(): array
    {
        return JumlahPasienHarian::selectRaw('DATE(tanggal) as date, SUM(jumlah_pasien_umum + jumlah_pasien_bpjs) as total')
            ->whereDate('tanggal', '>=', Carbon::now()->subDays(7))
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('total', 'date')
            ->toArray();
    }

    private function getProcedureTrend(): array
    {
        return Tindakan::selectRaw('DATE(tanggal_tindakan) as date, COUNT(*) as total')
            ->whereDate('tanggal_tindakan', '>=', Carbon::now()->subDays(7))
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
        $todayRevenue = PendapatanHarian::whereDate('tanggal_input', Carbon::today())->sum('nominal');
        $avgDailyRevenue = PendapatanHarian::whereDate('tanggal_input', '>=', Carbon::now()->subDays(30))
            ->selectRaw('AVG(daily_revenue) as avg_revenue')
            ->fromSub(function ($query) {
                $query->selectRaw('DATE(tanggal_input) as date, SUM(nominal) as daily_revenue')
                    ->from('pendapatan_harians')
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