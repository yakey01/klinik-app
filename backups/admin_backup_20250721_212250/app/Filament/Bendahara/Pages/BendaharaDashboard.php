<?php

namespace App\Filament\Bendahara\Pages;

use App\Models\Pendapatan;
use App\Models\Pengeluaran;
use App\Models\Jaspel;
use App\Models\User;
use App\Models\Tindakan;
use Filament\Pages\Page;
use Filament\Support\Colors\Color;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Filament\Actions\Action;
use Filament\Support\Enums\ActionSize;

class BendaharaDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-pie';
    
    protected static string $view = 'filament.bendahara.pages.world-class-dashboard';
    
    protected static ?string $title = '💰 Bendahara Dashboard';
    
    protected static ?string $navigationLabel = 'Dashboard';
    
    protected static ?int $navigationSort = 1;
    
    protected static ?string $navigationGroup = 'Dashboard';

    public function mount(): void
    {
        // Initialize world-class treasury dashboard
    }
    
    public function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label('Refresh')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->size(ActionSize::Small)
                ->action(fn () => redirect()->to(request()->url())),
        ];
    }

    // Core Financial Metrics - World Class Treasury Analytics
    public function getFinancialSummary(): array
    {
        return Cache::remember('bendahara_financial_summary', now()->addMinutes(5), function () {
            $currentMonth = now();
            $lastMonth = now()->subMonth();
            
            // Current month data
            $currentPendapatan = Pendapatan::whereMonth('tanggal', $currentMonth->month)
                ->whereYear('tanggal', $currentMonth->year)
                ->where('status_validasi', 'disetujui')
                ->sum('nominal');
                
            $currentPengeluaran = Pengeluaran::whereMonth('tanggal', $currentMonth->month)
                ->whereYear('tanggal', $currentMonth->year)
                ->sum('nominal');
                
            $currentJaspel = Jaspel::whereMonth('tanggal', $currentMonth->month)
                ->whereYear('tanggal', $currentMonth->year)
                ->sum('nominal');

            // Last month for comparison
            $lastPendapatan = Pendapatan::whereMonth('tanggal', $lastMonth->month)
                ->whereYear('tanggal', $lastMonth->year)
                ->where('status_validasi', 'disetujui')
                ->sum('nominal');
                
            $lastPengeluaran = Pengeluaran::whereMonth('tanggal', $lastMonth->month)
                ->whereYear('tanggal', $lastMonth->year)
                ->sum('nominal');
                
            $lastJaspel = Jaspel::whereMonth('tanggal', $lastMonth->month)
                ->whereYear('tanggal', $lastMonth->year)
                ->sum('nominal');

            return [
                'current' => [
                    'revenue' => $currentPendapatan,
                    'expenses' => $currentPengeluaran,
                    'jaspel' => $currentJaspel,
                    'net_income' => $currentPendapatan - $currentPengeluaran - $currentJaspel,
                ],
                'previous' => [
                    'revenue' => $lastPendapatan,
                    'expenses' => $lastPengeluaran,
                    'jaspel' => $lastJaspel,
                    'net_income' => $lastPendapatan - $lastPengeluaran - $lastJaspel,
                ],
                'growth' => [
                    'revenue' => $this->calculateGrowth($currentPendapatan, $lastPendapatan),
                    'expenses' => $this->calculateGrowth($currentPengeluaran, $lastPengeluaran),
                    'jaspel' => $this->calculateGrowth($currentJaspel, $lastJaspel),
                    'net_income' => $this->calculateGrowth(
                        $currentPendapatan - $currentPengeluaran - $currentJaspel,
                        $lastPendapatan - $lastPengeluaran - $lastJaspel
                    ),
                ],
            ];
        });
    }

    // Validation Performance Metrics
    public function getValidationMetrics(): array
    {
        return Cache::remember('bendahara_validation_metrics', now()->addMinutes(3), function () {
            $pending = [
                'pendapatan' => Pendapatan::where('status_validasi', 'pending')->count(),
                'pengeluaran' => Pengeluaran::where('status_validasi', 'pending')->count(),
                'jaspel' => Jaspel::where('status_validasi', 'pending')->count(),
            ];
            
            $approved = [
                'pendapatan' => Pendapatan::where('status_validasi', 'disetujui')->count(),
                'pengeluaran' => Pengeluaran::where('status_validasi', 'disetujui')->count(),
                'jaspel' => Jaspel::where('status_validasi', 'disetujui')->count(),
            ];

            $total = [
                'pendapatan' => Pendapatan::count(),
                'pengeluaran' => Pengeluaran::count(),
                'jaspel' => Jaspel::count(),
            ];

            return [
                'pending' => $pending,
                'approved' => $approved,
                'total' => $total,
                'approval_rate' => [
                    'pendapatan' => $total['pendapatan'] > 0 ? round(($approved['pendapatan'] / $total['pendapatan']) * 100, 1) : 0,
                    'pengeluaran' => $total['pengeluaran'] > 0 ? round(($approved['pengeluaran'] / $total['pengeluaran']) * 100, 1) : 0,
                    'jaspel' => $total['jaspel'] > 0 ? round(($approved['jaspel'] / $total['jaspel']) * 100, 1) : 0,
                ],
                'total_pending' => array_sum($pending),
                'total_approved' => array_sum($approved),
            ];
        });
    }

    // Monthly Trend Analysis for Charts
    public function getMonthlyTrends(): array
    {
        return Cache::remember('bendahara_monthly_trends', now()->addMinutes(10), function () {
            $trends = [];
            $labels = [];
            
            for ($i = 5; $i >= 0; $i--) {
                $date = now()->subMonths($i);
                $labels[] = $date->format('M Y');
                
                $monthlyPendapatan = Pendapatan::whereMonth('tanggal', $date->month)
                    ->whereYear('tanggal', $date->year)
                    ->where('status_validasi', 'disetujui')
                    ->sum('nominal');
                    
                $monthlyPengeluaran = Pengeluaran::whereMonth('tanggal', $date->month)
                    ->whereYear('tanggal', $date->year)
                    ->sum('nominal');
                    
                $monthlyJaspel = Jaspel::whereMonth('tanggal', $date->month)
                    ->whereYear('tanggal', $date->year)
                    ->sum('nominal');
                
                $trends['revenue'][] = $monthlyPendapatan;
                $trends['expenses'][] = $monthlyPengeluaran;
                $trends['jaspel'][] = $monthlyJaspel;
                $trends['net_income'][] = $monthlyPendapatan - $monthlyPengeluaran - $monthlyJaspel;
            }
            
            return [
                'labels' => $labels,
                'data' => $trends,
            ];
        });
    }

    // Recent Financial Activities
    public function getRecentActivities(): array
    {
        return Cache::remember('bendahara_recent_activities', now()->addMinutes(2), function () {
            $activities = collect();
            
            // Recent pendapatan
            $recentPendapatan = Pendapatan::with(['inputBy'])
                ->latest('updated_at')
                ->limit(3)
                ->get()
                ->map(function ($item) {
                    return [
                        'type' => 'revenue',
                        'title' => $item->nama_pendapatan,
                        'amount' => $item->nominal,
                        'status' => $item->status_validasi,
                        'date' => $item->updated_at,
                        'user' => $item->inputBy->name ?? 'System',
                    ];
                });
            
            // Recent pengeluaran
            $recentPengeluaran = Pengeluaran::with(['inputBy'])
                ->latest('updated_at')
                ->limit(3)
                ->get()
                ->map(function ($item) {
                    return [
                        'type' => 'expense',
                        'title' => $item->nama_pengeluaran,
                        'amount' => $item->nominal,
                        'status' => $item->status_validasi ?? 'approved',
                        'date' => $item->updated_at,
                        'user' => $item->inputBy->name ?? 'System',
                    ];
                });
            
            return $activities->merge($recentPendapatan)
                ->merge($recentPengeluaran)
                ->sortByDesc('date')
                ->take(6)
                ->values()
                ->toArray();
        });
    }

    private function calculateGrowth($current, $previous): float
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }
        
        return round((($current - $previous) / $previous) * 100, 1);
    }
    
    private function exportReport(): void
    {
        // TODO: Implement export functionality
        $this->notify('success', 'Export functionality will be implemented soon');
    }
    
    protected function notify(string $type, string $message): void
    {
        session()->flash('filament.notification', [
            'type' => $type,
            'message' => $message,
        ]);
    }
}