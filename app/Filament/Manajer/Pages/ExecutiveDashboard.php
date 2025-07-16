<?php

namespace App\Filament\Manajer\Pages;

use Filament\Pages\Dashboard;
use Filament\Actions;
use Filament\Support\Enums\MaxWidth;

class ExecutiveDashboard extends Dashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';

    protected static ?string $slug = 'dashboard';
    
    protected static ?string $navigationLabel = 'Executive Dashboard';
    
    protected static ?string $navigationGroup = '📊 Dashboard & Analytics';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Executive Dashboard';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('manajer') ?? false;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('refresh')
                ->label('Refresh Data')
                ->icon('heroicon-m-arrow-path')
                ->action(function () {
                    $this->dispatch('refresh-widgets');
                    $this->notify('success', 'Dashboard data refreshed successfully');
                }),
            Actions\Action::make('export')
                ->label('Export Report')
                ->icon('heroicon-m-document-arrow-down')
                ->color('success')
                ->action(function () {
                    $this->notify('success', 'Executive report export initiated');
                }),
        ];
    }

    public function getMaxContentWidth(): MaxWidth
    {
        return MaxWidth::Full;
    }

    public function getWidgets(): array
    {
        return [
            \App\Filament\Manajer\Widgets\ExecutiveKPIWidget::class,
            \App\Filament\Manajer\Widgets\FinancialOverviewWidget::class,
            \App\Filament\Manajer\Widgets\OperationsDashboardWidget::class,
            \App\Filament\Manajer\Widgets\StrategicInsightsWidget::class,
            \App\Filament\Manajer\Widgets\TeamPerformanceWidget::class,
            \App\Filament\Manajer\Widgets\ApprovalQueueWidget::class,
        ];
    }

    public function getColumns(): int
    {
        return 2;
    }
}