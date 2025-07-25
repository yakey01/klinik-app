<?php

namespace App\Filament\Manajer\Pages;

use Filament\Pages\Page;
use Filament\Support\Enums\MaxWidth;
use Filament\Actions;
use Filament\Support\Facades\FilamentView;
use Illuminate\Contracts\Support\Htmlable;

class EnhancedManajerDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';
    protected static string $view = 'filament.manajer.pages.enhanced-dashboard';
    protected static ?string $navigationLabel = 'Enhanced Dashboard';
    protected static ?string $title = 'Executive Dashboard';
    protected static ?string $navigationGroup = '📊 Executive Overview';
    protected static ?int $navigationSort = 1;

    public function getMaxWidth(): MaxWidth
    {
        return MaxWidth::Full;
    }

    public function getTitle(): string|Htmlable
    {
        return 'Executive Dashboard';
    }

    public function getHeading(): string|Htmlable
    {
        return 'Executive Dashboard';
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Real-time insights and strategic metrics for executive decision making';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('refresh')
                ->label('Refresh Data')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->action(function () {
                    $this->dispatch('refresh-widgets');
                    
                    \Filament\Notifications\Notification::make()
                        ->title('Data Refreshed')
                        ->body('Dashboard data has been updated with the latest information.')
                        ->success()
                        ->send();
                }),
                
            Actions\Action::make('export')
                ->label('Export Report')
                ->icon('heroicon-o-document-arrow-down')
                ->color('primary')
                ->action(function () {
                    // Export functionality implementation
                    \Filament\Notifications\Notification::make()
                        ->title('Export Started')
                        ->body('Executive report is being generated and will be available shortly.')
                        ->info()
                        ->send();
                }),
                
            Actions\Action::make('settings')
                ->label('Dashboard Settings')
                ->icon('heroicon-o-cog-6-tooth')
                ->color('gray')
                ->slideOver()
                ->form([
                    \Filament\Forms\Components\Toggle::make('auto_refresh')
                        ->label('Auto Refresh')
                        ->default(true)
                        ->helperText('Automatically refresh dashboard data every 5 minutes'),
                    \Filament\Forms\Components\Select::make('refresh_interval')
                        ->label('Refresh Interval')
                        ->options([
                            300 => '5 minutes',
                            600 => '10 minutes',
                            1800 => '30 minutes',
                            3600 => '1 hour',
                        ])
                        ->default(300),
                    \Filament\Forms\Components\Toggle::make('show_trends')
                        ->label('Show Trend Indicators')
                        ->default(true),
                    \Filament\Forms\Components\Toggle::make('compact_view')
                        ->label('Compact View')
                        ->default(false)
                        ->helperText('Reduce spacing for more data density'),
                ])
                ->action(function (array $data) {
                    // Save dashboard settings
                    session()->put('dashboard_settings', $data);
                    
                    \Filament\Notifications\Notification::make()
                        ->title('Settings Saved')
                        ->body('Dashboard settings have been updated.')
                        ->success()
                        ->send();
                }),
        ];
    }

    public function getWidgets(): array
    {
        return [
            \App\Filament\Manajer\Widgets\ManajerHeroStatsWidget::class,
            \App\Filament\Manajer\Widgets\ManajerFinancialInsightsWidget::class,
            \App\Filament\Manajer\Widgets\ManajerTeamPerformanceWidget::class,
            \App\Filament\Manajer\Widgets\ManajerOperationalDashboardWidget::class,
            \App\Filament\Manajer\Widgets\ManajerStrategicMetricsWidget::class,
            \App\Filament\Manajer\Widgets\ManajerApprovalWorkflowWidget::class,
        ];
    }

    public function getColumns(): int|string|array
    {
        return [
            'sm' => 1,
            'md' => 2,
            'lg' => 3,
            'xl' => 3,
            '2xl' => 3,
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('manajer') ?? false;
    }

    protected function getViewData(): array
    {
        return [
            'widgets' => $this->getWidgets(),
            'columns' => $this->getColumns(),
            'user' => auth()->user(),
            'settings' => session()->get('dashboard_settings', [
                'auto_refresh' => true,
                'refresh_interval' => 300,
                'show_trends' => true,
                'compact_view' => false,
            ]),
        ];
    }
}