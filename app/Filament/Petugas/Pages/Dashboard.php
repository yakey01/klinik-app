<?php

namespace App\Filament\Petugas\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';
    
    protected static ?string $navigationGroup = '🏠 Dashboard';
    
    protected static ?string $title = 'Dashboard Petugas';
    
    protected static ?int $navigationSort = 1;

    public function getWidgets(): array
    {
        return [
            \App\Filament\Petugas\Widgets\NotificationWidget::class,
            \App\Filament\Petugas\Widgets\PetugasStatsWidget::class,
            \App\Filament\Petugas\Widgets\QuickActionsWidget::class,
        ];
    }
}