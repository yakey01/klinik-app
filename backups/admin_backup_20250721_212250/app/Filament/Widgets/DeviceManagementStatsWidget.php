<?php

namespace App\Filament\Widgets;

use App\Models\UserDevice;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DeviceManagementStatsWidget extends BaseWidget
{
    // protected static ?string $pollingInterval = null; // DISABLED - emergency polling removal
    
    protected function getStats(): array
    {
        // Get basic device statistics
        $totalDevices = UserDevice::count();
        $activeDevices = UserDevice::where('is_active', true)->count();
        $verifiedDevices = UserDevice::whereNotNull('verified_at')->count();
        
        // Get users with multiple devices
        $usersWithMultiple = UserDevice::select('user_id')
            ->where('is_active', true)
            ->groupBy('user_id')
            ->havingRaw('COUNT(*) > 1')
            ->count();
            
        // Get device type breakdown
        $mobileDevices = UserDevice::where('device_type', 'mobile')->where('is_active', true)->count();
        $tabletDevices = UserDevice::where('device_type', 'tablet')->where('is_active', true)->count();
        $webDevices = UserDevice::where('device_type', 'web')->where('is_active', true)->count();
        
        // Get platform breakdown
        $iosDevices = UserDevice::where('platform', 'iOS')->where('is_active', true)->count();
        $androidDevices = UserDevice::where('platform', 'Android')->where('is_active', true)->count();
        
        // Calculate trends (compared to last 30 days)
        $newDevicesThisMonth = UserDevice::where('created_at', '>=', now()->subDays(30))->count();
        $revokedThisMonth = UserDevice::where('status', 'revoked')->where('updated_at', '>=', now()->subDays(30))->count();

        return [
            Stat::make('Total Devices', $totalDevices)
                ->description('All registered devices')
                ->descriptionIcon('heroicon-m-device-phone-mobile')
                ->color('primary')
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                ]),
                
            Stat::make('Active Devices', $activeDevices)
                ->description($activeDevices > 0 ? round(($activeDevices / $totalDevices) * 100, 1) . '% of total' : 'No active devices')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success')
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                ]),
                
            Stat::make('Users with Multiple Devices', $usersWithMultiple)
                ->description($usersWithMultiple > 0 ? '🚨 Requires attention' : '✅ All users have single device')
                ->descriptionIcon($usersWithMultiple > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-shield-check')
                ->color($usersWithMultiple > 0 ? 'danger' : 'success')
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                ]),
                
            Stat::make('Verified Devices', $verifiedDevices)
                ->description($verifiedDevices > 0 ? round(($verifiedDevices / $totalDevices) * 100, 1) . '% verified by admin' : 'No verified devices')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color($verifiedDevices > ($totalDevices * 0.8) ? 'success' : 'warning')
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                ]),
                
            Stat::make('Platform Distribution', '')
                ->description("📱 iOS: {$iosDevices} | 🤖 Android: {$androidDevices} | 🌐 Web: {$webDevices}")
                ->descriptionIcon('heroicon-m-device-tablet')
                ->color('info')
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                ]),
                
            Stat::make('Device Types', '')
                ->description("📱 Mobile: {$mobileDevices} | 📟 Tablet: {$tabletDevices} | 💻 Web: {$webDevices}")
                ->descriptionIcon('heroicon-m-computer-desktop')
                ->color('gray')
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                ]),
        ];
    }
    
    protected function getColumns(): int
    {
        return 3;
    }
}