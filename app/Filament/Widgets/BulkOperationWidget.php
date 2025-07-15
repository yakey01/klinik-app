<?php

namespace App\Filament\Widgets;

use App\Models\BulkOperation;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class BulkOperationWidget extends BaseWidget
{
    protected static ?int $sort = 4;
    
    protected static ?string $pollingInterval = '30s';
    
    public static function canView(): bool
    {
        return Auth::user()?->hasRole(['super-admin', 'admin']) ?? false;
    }
    
    protected function getStats(): array
    {
        $activeOperations = BulkOperation::active()->count();
        $completedToday = BulkOperation::where('status', BulkOperation::STATUS_COMPLETED)
            ->whereDate('completed_at', today())
            ->count();
        $failedToday = BulkOperation::where('status', BulkOperation::STATUS_FAILED)
            ->whereDate('completed_at', today())
            ->count();
        $totalOperations = BulkOperation::count();
        
        // Get operation type breakdown for last 7 days
        $recentOperations = BulkOperation::where('created_at', '>=', now()->subDays(7))
            ->selectRaw('operation_type, count(*) as count')
            ->groupBy('operation_type')
            ->pluck('count', 'operation_type')
            ->toArray();
        
        return [
            Stat::make('Active Operations', $activeOperations)
                ->description('Currently running or queued')
                ->descriptionIcon('heroicon-m-arrow-path')
                ->color($activeOperations > 5 ? 'warning' : 'success')
                ->chart($this->getActiveOperationsChart()),
                
            Stat::make('Completed Today', $completedToday)
                ->description('Successfully completed today')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
                
            Stat::make('Failed Today', $failedToday)
                ->description('Failed operations today')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color($failedToday > 0 ? 'danger' : 'success'),
                
            Stat::make('Total Operations', $totalOperations)
                ->description('All time operations')
                ->descriptionIcon('heroicon-m-squares-plus')
                ->color('primary'),
        ];
    }
    
    private function getActiveOperationsChart(): array
    {
        $operations = BulkOperation::active()
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('created_at', '>=', now()->subDays(7))
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date')
            ->toArray();
        
        $chartData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $chartData[] = $operations[$date] ?? 0;
        }
        
        return $chartData;
    }
}