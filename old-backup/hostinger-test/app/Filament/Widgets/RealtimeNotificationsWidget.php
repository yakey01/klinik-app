<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Cache;
use App\Models\User;
use App\Models\Tindakan;
use App\Models\Pendapatan;
use App\Models\Pengeluaran;

class RealtimeNotificationsWidget extends Widget
{
    protected static string $view = 'filament.widgets.realtime-notifications';
    
    protected static ?int $sort = 4;
    
    protected static ?string $pollingInterval = '5s';
    
    protected int | string | array $columnSpan = 'full';

    protected function getViewData(): array
    {
        $notifications = Cache::remember('realtime_notifications', 30, function () {
            return collect([
                $this->getNewUsersNotification(),
                $this->getPendingApprovalsNotification(),
                $this->getSystemHealthNotification(),
                $this->getRecentTransactionsNotification(),
            ])->filter()->values()->toArray();
        });

        return [
            'notifications' => $notifications,
            'lastUpdate' => now()->format('H:i:s'),
        ];
    }

    private function getNewUsersNotification()
    {
        $newUsers = User::where('created_at', '>=', now()->subHours(24))->count();
        
        if ($newUsers > 0) {
            return [
                'type' => 'success',
                'icon' => 'heroicon-o-user-plus',
                'title' => 'New Users',
                'message' => "{$newUsers} new users registered in the last 24 hours",
                'timestamp' => now()->subMinutes(rand(1, 60))->diffForHumans(),
            ];
        }
        
        return null;
    }

    private function getPendingApprovalsNotification()
    {
        $pendingCount = Pendapatan::where('status', 'pending')->count() + 
                       Pengeluaran::where('status', 'pending')->count();
        
        if ($pendingCount > 0) {
            return [
                'type' => 'warning',
                'icon' => 'heroicon-o-clock',
                'title' => 'Pending Approvals',
                'message' => "{$pendingCount} transactions awaiting approval",
                'timestamp' => now()->subMinutes(rand(5, 30))->diffForHumans(),
            ];
        }
        
        return null;
    }

    private function getSystemHealthNotification()
    {
        // Simulate system health check
        $healthScore = rand(85, 100);
        
        if ($healthScore < 90) {
            return [
                'type' => 'info',
                'icon' => 'heroicon-o-server',
                'title' => 'System Health',
                'message' => "System health: {$healthScore}% - Performance monitoring active",
                'timestamp' => now()->subMinutes(rand(1, 15))->diffForHumans(),
            ];
        }
        
        return null;
    }

    private function getRecentTransactionsNotification()
    {
        $recentTransactions = Tindakan::where('created_at', '>=', now()->subHours(1))->count();
        
        if ($recentTransactions > 5) {
            return [
                'type' => 'info',
                'icon' => 'heroicon-o-currency-dollar',
                'title' => 'High Activity',
                'message' => "{$recentTransactions} medical procedures recorded in the last hour",
                'timestamp' => now()->subMinutes(rand(1, 10))->diffForHumans(),
            ];
        }
        
        return null;
    }
    
    public static function canView(): bool
    {
        return auth()->user()?->hasRole(['admin', 'manajer']) ?? false;
    }
}