<?php

namespace App\Filament\Petugas\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Actions\Action;
use Filament\Support\Enums\ActionSize;

class PetugasDashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';
    
    protected static ?string $title = 'Dashboard Petugas';
    
    protected static ?string $navigationLabel = 'Dashboard';
    
    protected static ?int $navigationSort = 1;
    
    protected static ?string $navigationGroup = '🏠 Dashboard';
    
    public function getWidgets(): array
    {
        return [
            \App\Filament\Petugas\Widgets\PetugasDashboardSummaryWidget::class,
            \App\Filament\Petugas\Widgets\NotificationWidget::class,
            \App\Filament\Petugas\Widgets\QuickActionsWidget::class,
        ];
    }
    
    public function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label('Refresh Data')
                ->icon('heroicon-o-arrow-path')
                ->color('primary')
                ->size(ActionSize::Small)
                ->action(fn () => redirect()->to(request()->url())),
                
            Action::make('add_patient')
                ->label('Tambah Pasien')
                ->icon('heroicon-o-user-plus')
                ->color('success')
                ->size(ActionSize::Small)
                ->url(route('filament.petugas.resources.pasiens.create')),
        ];
    }
}