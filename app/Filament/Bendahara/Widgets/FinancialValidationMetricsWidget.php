<?php

namespace App\Filament\Bendahara\Widgets;

use App\Models\Pendapatan;
use App\Models\Pengeluaran;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class FinancialValidationMetricsWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '30s';
    
    protected static bool $isLazy = false;

    protected function getStats(): array
    {
        $stats = $this->getFinancialStats();
        
        // Calculate trends
        $pendingTrend = $this->getPendingTrend();
        $cashFlowTrend = $this->getCashFlowTrend();
        $validationEfficiency = $this->getValidationEfficiency();
        
        return [
            Stat::make('🕐 Total Pending Validations', $stats['combined']['total_pending'])
                ->description($pendingTrend > 0 ? '+' . $pendingTrend . ' from yesterday' : $pendingTrend . ' from yesterday')
                ->descriptionIcon($pendingTrend > 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($pendingTrend > 0 ? 'warning' : 'success')
                ->chart([12, 15, 8, $stats['combined']['total_pending']]),
                
            Stat::make('💰 Income Pending', $stats['pendapatan']['pending'])
                ->description('Rp ' . number_format($stats['pendapatan']['pending_value'] / 1000000, 1) . 'M value')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color($stats['pendapatan']['pending'] > 10 ? 'warning' : 'success')
                ->chart([5, 8, 12, $stats['pendapatan']['pending']]),
                
            Stat::make('💸 Expenses Pending', $stats['pengeluaran']['pending'])
                ->description('Rp ' . number_format($stats['pengeluaran']['pending_value'] / 1000000, 1) . 'M value')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color($stats['pengeluaran']['pending'] > 10 ? 'warning' : 'success')
                ->chart([8, 6, 10, $stats['pengeluaran']['pending']]),
                
            Stat::make('📊 Net Cash Flow', 'Rp ' . number_format($stats['combined']['net_cash_flow'] / 1000000, 1) . 'M')
                ->description($cashFlowTrend)
                ->descriptionIcon($stats['combined']['net_cash_flow'] > 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($stats['combined']['net_cash_flow'] > 0 ? 'success' : 'danger'),
                
            Stat::make('⚡ Validation Efficiency', $validationEfficiency['rate'] . '%')
                ->description('Avg processing: ' . $validationEfficiency['avg_hours'] . ' hours')
                ->descriptionIcon('heroicon-m-clock')
                ->color($validationEfficiency['rate'] > 80 ? 'success' : 'warning')
                ->chart([75, 82, 88, $validationEfficiency['rate']]),
                
            Stat::make('🎯 Today\'s Progress', $stats['combined']['today_completion'] . '%')
                ->description($stats['combined']['total_today'] . ' transactions processed today')
                ->descriptionIcon('heroicon-m-flag')
                ->color($stats['combined']['today_completion'] > 85 ? 'success' : 'warning')
                ->chart([70, 75, 85, $stats['combined']['today_completion']]),
        ];
    }
    
    private function getFinancialStats(): array
    {
        // Pendapatan stats with validation status
        $pendapatanQuery = Pendapatan::whereNotNull('input_by');
        $pendapatanStats = [
            'total' => $pendapatanQuery->count(),
            'pending' => $pendapatanQuery->where('status_validasi', 'pending')->count(),
            'approved' => $pendapatanQuery->where('status_validasi', 'disetujui')->count(),
            'rejected' => $pendapatanQuery->where('status_validasi', 'ditolak')->count(),
            'revision' => $pendapatanQuery->where('status_validasi', 'need_revision')->count(),
            'today' => $pendapatanQuery->whereDate('tanggal', today())->count(),
            'today_approved' => $pendapatanQuery->whereDate('tanggal', today())
                ->where('status_validasi', 'disetujui')->count(),
            'high_value' => $pendapatanQuery->where('nominal', '>', 5000000)->count(),
            'total_value' => $pendapatanQuery->where('status_validasi', 'disetujui')->sum('nominal'),
            'pending_value' => $pendapatanQuery->where('status_validasi', 'pending')->sum('nominal'),
        ];

        // Pengeluaran stats with validation status
        $pengeluaranQuery = Pengeluaran::whereNotNull('input_by');
        $pengeluaranStats = [
            'total' => $pengeluaranQuery->count(),
            'pending' => $pengeluaranQuery->where('status_validasi', 'pending')->count(),
            'approved' => $pengeluaranQuery->where('status_validasi', 'disetujui')->count(),
            'rejected' => $pengeluaranQuery->where('status_validasi', 'ditolak')->count(),
            'revision' => $pengeluaranQuery->where('status_validasi', 'need_revision')->count(),
            'today' => $pengeluaranQuery->whereDate('tanggal', today())->count(),
            'today_approved' => $pengeluaranQuery->whereDate('tanggal', today())
                ->where('status_validasi', 'disetujui')->count(),
            'high_value' => $pengeluaranQuery->where('nominal', '>', 5000000)->count(),
            'total_value' => $pengeluaranQuery->where('status_validasi', 'disetujui')->sum('nominal'),
            'pending_value' => $pengeluaranQuery->where('status_validasi', 'pending')->sum('nominal'),
        ];

        // Combined calculations
        $totalPending = $pendapatanStats['pending'] + $pengeluaranStats['pending'];
        $totalToday = $pendapatanStats['today'] + $pengeluaranStats['today'];
        $totalTodayApproved = $pendapatanStats['today_approved'] + $pengeluaranStats['today_approved'];
        $netCashFlow = $pendapatanStats['total_value'] - $pengeluaranStats['total_value'];
        $todayCompletion = $totalToday > 0 ? round(($totalTodayApproved / $totalToday) * 100) : 100;

        $combined = [
            'total_pending' => $totalPending,
            'total_today' => $totalToday,
            'today_completion' => $todayCompletion,
            'net_cash_flow' => $netCashFlow,
            'total_high_value' => $pendapatanStats['high_value'] + $pengeluaranStats['high_value'],
        ];

        return [
            'pendapatan' => $pendapatanStats,
            'pengeluaran' => $pengeluaranStats,
            'combined' => $combined,
        ];
    }
    
    private function getPendingTrend(): int
    {
        // Today's pending vs yesterday's pending
        $todayPendingPendapatan = Pendapatan::where('status_validasi', 'pending')
            ->whereDate('created_at', today())
            ->count();
        $todayPendingPengeluaran = Pengeluaran::where('status_validasi', 'pending')
            ->whereDate('created_at', today())
            ->count();
        $todayTotal = $todayPendingPendapatan + $todayPendingPengeluaran;
            
        $yesterdayPendingPendapatan = Pendapatan::where('status_validasi', 'pending')
            ->whereDate('created_at', now()->subDay())
            ->count();
        $yesterdayPendingPengeluaran = Pengeluaran::where('status_validasi', 'pending')
            ->whereDate('created_at', now()->subDay())
            ->count();
        $yesterdayTotal = $yesterdayPendingPendapatan + $yesterdayPendingPengeluaran;
            
        return $todayTotal - $yesterdayTotal;
    }
    
    private function getCashFlowTrend(): string
    {
        // This week vs last week cash flow comparison
        $thisWeekIncome = Pendapatan::where('status_validasi', 'disetujui')
            ->whereBetween('validasi_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->sum('nominal');
            
        $thisWeekExpenses = Pengeluaran::where('status_validasi', 'disetujui')
            ->whereBetween('validasi_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->sum('nominal');
            
        $thisWeekNet = $thisWeekIncome - $thisWeekExpenses;
        
        $lastWeekIncome = Pendapatan::where('status_validasi', 'disetujui')
            ->whereBetween('validasi_at', [
                now()->subWeek()->startOfWeek(),
                now()->subWeek()->endOfWeek()
            ])
            ->sum('nominal');
            
        $lastWeekExpenses = Pengeluaran::where('status_validasi', 'disetujui')
            ->whereBetween('validasi_at', [
                now()->subWeek()->startOfWeek(),
                now()->subWeek()->endOfWeek()
            ])
            ->sum('nominal');
            
        $lastWeekNet = $lastWeekIncome - $lastWeekExpenses;
        
        $improvement = $thisWeekNet - $lastWeekNet;
        
        if ($improvement > 1000000) {
            return '+Rp' . number_format($improvement / 1000000, 1) . 'M vs last week';
        } elseif ($improvement < -1000000) {
            return '-Rp' . number_format(abs($improvement) / 1000000, 1) . 'M vs last week';
        } else {
            return 'Similar to last week';
        }
    }
    
    private function getValidationEfficiency(): array
    {
        // Calculate validation rates and processing times
        $totalPendapatan = Pendapatan::whereNotNull('input_by')->count();
        $totalPengeluaran = Pengeluaran::whereNotNull('input_by')->count();
        $totalTransactions = $totalPendapatan + $totalPengeluaran;
        
        $processedPendapatan = Pendapatan::whereIn('status_validasi', ['disetujui', 'ditolak'])->count();
        $processedPengeluaran = Pengeluaran::whereIn('status_validasi', ['disetujui', 'ditolak'])->count();
        $totalProcessed = $processedPendapatan + $processedPengeluaran;
        
        $validationRate = $totalTransactions > 0 ? round(($totalProcessed / $totalTransactions) * 100) : 0;
        
        // Average processing time using SQLite-compatible syntax
        $avgPendapatanHours = DB::table('pendapatan')
            ->where('status_validasi', '!=', 'pending')
            ->whereNotNull('validasi_at')
            ->where('validasi_at', '>=', now()->subDays(7))
            ->selectRaw('AVG((julianday(validasi_at) - julianday(created_at)) * 24) as avg_hours')
            ->value('avg_hours') ?? 24;
            
        $avgPengeluaranHours = DB::table('pengeluaran')
            ->where('status_validasi', '!=', 'pending')
            ->whereNotNull('validasi_at')
            ->where('validasi_at', '>=', now()->subDays(7))
            ->selectRaw('AVG((julianday(validasi_at) - julianday(created_at)) * 24) as avg_hours')
            ->value('avg_hours') ?? 24;
            
        $avgProcessingHours = round(($avgPendapatanHours + $avgPengeluaranHours) / 2, 1);
        
        return [
            'rate' => $validationRate,
            'avg_hours' => $avgProcessingHours,
        ];
    }
}