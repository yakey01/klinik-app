<?php

namespace App\Filament\Widgets;

use App\Models\AuditLog;
use App\Models\UserSession;
use App\Models\TwoFactorAuth;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class SecurityOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 3;
    
    protected static ?string $pollingInterval = '30s';
    
    public static function canView(): bool
    {
        return Auth::user()?->hasRole(['super-admin', 'admin']) ?? false;
    }
    
    protected function getStats(): array
    {
        $now = now();
        $last24Hours = $now->copy()->subHours(24);
        
        // Security events in last 24 hours
        $securityEvents = AuditLog::whereIn('action', [
            'login_failed', 'security_event', 'suspicious_activity', 'account_locked'
        ])->where('created_at', '>=', $last24Hours)->count();
        
        // Failed login attempts
        $failedLogins = AuditLog::where('action', 'login_failed')
            ->where('created_at', '>=', $last24Hours)
            ->count();
        
        // Active sessions
        $activeSessions = UserSession::where('is_active', true)
            ->where('expires_at', '>', $now)
            ->count();
        
        // 2FA adoption rate
        $totalUsers = User::count();
        $usersWithTwoFactor = TwoFactorAuth::where('enabled', true)->count();
        $twoFactorPercentage = $totalUsers > 0 ? round(($usersWithTwoFactor / $totalUsers) * 100) : 0;
        
        return [
            Stat::make('Security Events', $securityEvents)
                ->description('Security events in 24h')
                ->descriptionIcon('heroicon-m-shield-exclamation')
                ->color($securityEvents > 10 ? 'danger' : ($securityEvents > 5 ? 'warning' : 'success'))
                ->chart($this->getSecurityEventsChart()),
                
            Stat::make('Failed Logins', $failedLogins)
                ->description('Failed attempts in 24h')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color($failedLogins > 20 ? 'danger' : ($failedLogins > 10 ? 'warning' : 'success'))
                ->chart($this->getFailedLoginsChart()),
                
            Stat::make('Active Sessions', $activeSessions)
                ->description('Current active sessions')
                ->descriptionIcon('heroicon-m-computer-desktop')
                ->color($activeSessions > 100 ? 'warning' : 'success'),
                
            Stat::make('2FA Adoption', $twoFactorPercentage . '%')
                ->description("{$usersWithTwoFactor} of {$totalUsers} users")
                ->descriptionIcon('heroicon-m-device-phone-mobile')
                ->color($twoFactorPercentage < 50 ? 'danger' : ($twoFactorPercentage < 80 ? 'warning' : 'success')),
        ];
    }
    
    private function getSecurityEventsChart(): array
    {
        $events = AuditLog::whereIn('action', [
            'login_failed', 'security_event', 'suspicious_activity', 'account_locked'
        ])
        ->where('created_at', '>=', now()->subDays(7))
        ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
        ->groupBy('date')
        ->orderBy('date')
        ->pluck('count', 'date')
        ->toArray();
        
        $chartData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $chartData[] = $events[$date] ?? 0;
        }
        
        return $chartData;
    }
    
    private function getFailedLoginsChart(): array
    {
        $events = AuditLog::where('action', 'login_failed')
            ->where('created_at', '>=', now()->subDays(7))
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date')
            ->toArray();
        
        $chartData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $chartData[] = $events[$date] ?? 0;
        }
        
        return $chartData;
    }
}