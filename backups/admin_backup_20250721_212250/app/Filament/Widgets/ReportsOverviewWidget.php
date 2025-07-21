<?php

namespace App\Filament\Widgets;

use App\Models\Report;
use App\Models\ReportExecution;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class ReportsOverviewWidget extends BaseWidget
{
    // protected static ?string $pollingInterval = null; // DISABLED - emergency polling removal

    protected function getStats(): array
    {
        $userId = Auth::id();
        
        // Get report statistics
        $totalReports = Report::where('user_id', $userId)->count();
        $activeReports = Report::where('user_id', $userId)
            ->where('status', Report::STATUS_ACTIVE)
            ->count();
        $publicReports = Report::where('user_id', $userId)
            ->where('is_public', true)
            ->count();
        
        // Get execution statistics
        $totalExecutions = ReportExecution::whereHas('report', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })->count();
        
        $executionsToday = ReportExecution::whereHas('report', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })->whereDate('created_at', today())->count();
        
        $avgExecutionTime = ReportExecution::whereHas('report', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })
        ->where('status', 'completed')
        ->avg('execution_time');
        
        return [
            Stat::make('Total Reports', $totalReports)
                ->description('Reports created by you')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('success'),
                
            Stat::make('Active Reports', $activeReports)
                ->description('Currently active reports')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('primary'),
                
            Stat::make('Public Reports', $publicReports)
                ->description('Reports shared publicly')
                ->descriptionIcon('heroicon-m-eye')
                ->color('info'),
                
            Stat::make('Total Executions', $totalExecutions)
                ->description('All time executions')
                ->descriptionIcon('heroicon-m-play')
                ->color('warning'),
                
            Stat::make('Executions Today', $executionsToday)
                ->description('Reports run today')
                ->descriptionIcon('heroicon-m-clock')
                ->color('success'),
                
            Stat::make('Avg Execution Time', $avgExecutionTime ? number_format($avgExecutionTime) . 'ms' : 'N/A')
                ->description('Average execution time')
                ->descriptionIcon('heroicon-m-bolt')
                ->color('secondary'),
        ];
    }
}