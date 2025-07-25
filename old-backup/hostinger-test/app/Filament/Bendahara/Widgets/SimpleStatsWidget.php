<?php

namespace App\Filament\Bendahara\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SimpleStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Pendapatan', 'Rp 0')
                ->description('Bulan ini')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),
                
            Stat::make('Total Pengeluaran', 'Rp 0')
                ->description('Bulan ini')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('danger'),
                
            Stat::make('Status', 'Aktif')
                ->description('Dashboard bendahara')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
        ];
    }
}