<?php

namespace App\Filament\Widgets;

use App\Models\AuditLog;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class RecentActivityWidget extends BaseWidget
{
    protected static ?int $sort = 1;
    
    // protected static ?string $pollingInterval = null; // DISABLED - was causing refresh loops
    
    public static function canView(): bool
    {
        return Auth::user()?->hasRole(['super-admin', 'admin']) ?? false;
    }
    
    protected function getStats(): array
    {
        $last24Hours = AuditLog::recent(24)->count();
        $lastWeek = AuditLog::where('created_at', '>=', now()->subWeek())->count();
        $highRiskToday = AuditLog::highRisk()->whereDate('created_at', today())->count();
        $totalUsers = AuditLog::distinct('user_id')->whereNotNull('user_id')->count();
        
        return [
            Stat::make('Activity (24h)', $last24Hours)
                ->description('Actions in last 24 hours')
                ->descriptionIcon('heroicon-m-clock')
                ->color($last24Hours > 50 ? 'warning' : 'success'),
                
            Stat::make('Weekly Activity', $lastWeek)
                ->description('Actions in last 7 days')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color($lastWeek > 200 ? 'warning' : 'success'),
                
            Stat::make('High Risk Today', $highRiskToday)
                ->description('Critical actions today')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($highRiskToday > 0 ? 'danger' : 'success'),
                
            Stat::make('Active Users', $totalUsers)
                ->description('Users with logged activity')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),
        ];
    }
}