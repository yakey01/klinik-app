<?php

namespace App\Filament\Resources\TelegramSettingResource\Widgets;

use App\Models\TelegramSetting;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TelegramStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $totalRoles = TelegramSetting::count();
        $activeRoles = TelegramSetting::where('is_active', true)->count();
        $totalNotificationTypes = TelegramSetting::where('is_active', true)
            ->get()
            ->sum(function ($setting) {
                return count($setting->notification_types ?? []);
            });

        return [
            Stat::make('Total Role Terkonfigurasi', $totalRoles)
                ->description('Role dengan pengaturan telegram')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('primary'),

            Stat::make('Role Aktif', $activeRoles)
                ->description('Role yang menerima notifikasi')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Total Notifikasi Aktif', $totalNotificationTypes)
                ->description('Jenis notifikasi yang dikonfigurasi')
                ->descriptionIcon('heroicon-m-bell')
                ->color('info'),
        ];
    }
}