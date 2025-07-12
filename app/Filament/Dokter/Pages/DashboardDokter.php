<?php

namespace App\Filament\Dokter\Pages;

use Filament\Pages\Dashboard;

class DashboardDokter extends Dashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';
    
    protected static string $view = 'filament-panels::pages.dashboard';
    
    protected static ?string $title = 'Dashboard Dokter';
    
    protected static ?string $navigationLabel = 'Dashboard';
    
    public function getWidgets(): array
    {
        return [
            \App\Filament\Dokter\Widgets\TindakanPerHariWidget::class,
            \App\Filament\Dokter\Widgets\JaspelComparisonWidget::class,
            \App\Filament\Dokter\Widgets\PresensiStatsWidget::class,
        ];
    }
    
    public function getColumns(): int | string | array
    {
        return [
            'md' => 2,
            'xl' => 3,
        ];
    }
}