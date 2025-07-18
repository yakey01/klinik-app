<?php

namespace App\Filament\Bendahara\Widgets;

use App\Models\Tindakan;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ValidationMetricsWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '30s';
    
    protected static bool $isLazy = false;

    protected function getStats(): array
    {
        $stats = $this->getValidationStats();
        
        // Calculate trend for pending validations
        $pendingTrend = $this->getPendingTrend();
        $throughputTrend = $this->getThroughputTrend();
        
        return [
            Stat::make('🕐 Pending Validations', $stats['pending'])
                ->description($pendingTrend > 0 ? '+' . $pendingTrend . ' from yesterday' : $pendingTrend . ' from yesterday')
                ->descriptionIcon($pendingTrend > 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($pendingTrend > 0 ? 'warning' : 'success')
                ->chart([7, 12, 15, 8, $stats['pending']]),
                
            Stat::make('✅ Today\'s Approvals', $stats['today_approved'])
                ->description('Out of ' . $stats['today_total'] . ' processed today')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success')
                ->chart([5, 8, 12, 15, $stats['today_approved']]),
                
            Stat::make('📊 Approval Rate', number_format($stats['approval_rate'], 1) . '%')
                ->description('Last 30 days performance')
                ->descriptionIcon($stats['approval_rate'] > 85 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($stats['approval_rate'] > 85 ? 'success' : 'warning'),
                
            Stat::make('💰 Pending Value', 'Rp ' . number_format($stats['pending_value'] / 1000000, 1) . 'M')
                ->description('Total value awaiting validation')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color($stats['pending_value'] > 10000000 ? 'danger' : 'info'),
                
            Stat::make('⚡ Avg Processing Time', $stats['avg_processing_hours'] . ' hours')
                ->description($throughputTrend . ' vs last week')
                ->descriptionIcon('heroicon-m-clock')
                ->color($stats['avg_processing_hours'] < 24 ? 'success' : 'warning'),
                
            Stat::make('🎯 Daily Target', $stats['daily_completion'] . '%')
                ->description('Target: Process all daily submissions')
                ->descriptionIcon('heroicon-m-flag')
                ->color($stats['daily_completion'] > 90 ? 'success' : 'warning')
                ->chart([75, 80, 85, 92, $stats['daily_completion']]),
        ];
    }
    
    private function getValidationStats(): array
    {
        $baseQuery = Tindakan::whereNotNull('input_by');
        
        $pending = $baseQuery->where('status_validasi', 'pending')->count();
        $todayTotal = $baseQuery->whereDate('tanggal_tindakan', today())->count();
        $todayApproved = $baseQuery->whereDate('tanggal_tindakan', today())
            ->where('status_validasi', 'approved')->count();
        
        // Calculate approval rate for last 30 days
        $last30Days = $baseQuery->where('tanggal_tindakan', '>=', now()->subDays(30));
        $totalProcessed = $last30Days->whereIn('status_validasi', ['approved', 'rejected'])->count();
        $totalApproved = $last30Days->where('status_validasi', 'approved')->count();
        $approvalRate = $totalProcessed > 0 ? ($totalApproved / $totalProcessed) * 100 : 0;
        
        // Pending value calculation
        $pendingValue = $baseQuery->where('status_validasi', 'pending')->sum('tarif');
        
        // Average processing time (SQLite compatible)
        $avgProcessingHours = DB::table('tindakan')
            ->where('status_validasi', '!=', 'pending')
            ->whereNotNull('validated_at')
            ->where('validated_at', '>=', now()->subDays(7))
            ->selectRaw('AVG((julianday(validated_at) - julianday(created_at)) * 24) as avg_hours')
            ->value('avg_hours') ?? 24;
            
        // Daily completion rate
        $dailyCompletion = $todayTotal > 0 ? (($todayTotal - $pending) / $todayTotal) * 100 : 100;
        
        return [
            'pending' => $pending,
            'today_total' => $todayTotal,
            'today_approved' => $todayApproved,
            'approval_rate' => $approvalRate,
            'pending_value' => $pendingValue,
            'avg_processing_hours' => round($avgProcessingHours, 1),
            'daily_completion' => round($dailyCompletion),
        ];
    }
    
    private function getPendingTrend(): int
    {
        $today = Tindakan::whereNotNull('input_by')
            ->where('status_validasi', 'pending')
            ->whereDate('created_at', today())
            ->count();
            
        $yesterday = Tindakan::whereNotNull('input_by')
            ->where('status_validasi', 'pending')
            ->whereDate('created_at', now()->subDay())
            ->count();
            
        return $today - $yesterday;
    }
    
    private function getThroughputTrend(): string
    {
        $thisWeekAvg = DB::table('tindakan')
            ->where('status_validasi', '!=', 'pending')
            ->whereNotNull('validated_at')
            ->where('validated_at', '>=', now()->startOfWeek())
            ->selectRaw('AVG((julianday(validated_at) - julianday(created_at)) * 24) as avg_hours')
            ->value('avg_hours') ?? 24;
            
        $lastWeekAvg = DB::table('tindakan')
            ->where('status_validasi', '!=', 'pending')
            ->whereNotNull('validated_at')
            ->whereBetween('validated_at', [
                now()->subWeek()->startOfWeek(),
                now()->subWeek()->endOfWeek()
            ])
            ->selectRaw('AVG((julianday(validated_at) - julianday(created_at)) * 24) as avg_hours')
            ->value('avg_hours') ?? 24;
            
        $improvement = $lastWeekAvg - $thisWeekAvg;
        
        if ($improvement > 2) {
            return '+' . round($improvement, 1) . 'h faster';
        } elseif ($improvement < -2) {
            return round(abs($improvement), 1) . 'h slower';
        } else {
            return 'Similar to last week';
        }
    }
}