<?php

namespace App\Filament\Widgets;

use App\Models\UserDevice;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DeviceManagementStatsWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '30s';
    
    protected function getStats(): array
    {
        $totalDevices = UserDevice::count();
        $activeDevices = UserDevice::where('is_active', true)->count();
        $verifiedDevices = UserDevice::whereNotNull('verified_at')->count();
        
        return [
            Stat::make('Total Devices', $totalDevices)
                ->description('All registered devices')
                ->descriptionIcon('heroicon-m-device-phone-mobile')
                ->color('primary'),
                
            Stat::make('Active Devices', $activeDevices)
                ->description($activeDevices > 0 ? round(($activeDevices / max($totalDevices, 1)) * 100, 1) . '% of total' : 'No active devices')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
                
            Stat::make('Verified Devices', $verifiedDevices)
                ->description($verifiedDevices > 0 ? round(($verifiedDevices / max($totalDevices, 1)) * 100, 1) . '% verified' : 'No verified devices')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color($verifiedDevices > ($totalDevices * 0.8) ? 'success' : 'warning'),
        ];
    }
}