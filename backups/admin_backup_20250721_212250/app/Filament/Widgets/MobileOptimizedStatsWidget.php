<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\User;
use App\Models\Pasien;
use App\Models\Tindakan;
use App\Models\Pendapatan;

class MobileOptimizedStatsWidget extends BaseWidget
{
    protected static ?int $sort = 5;
    
    // protected static ?string $pollingInterval = null; // DISABLED - was causing refresh loops
    
    protected int | string | array $columnSpan = [
        'sm' => 1,
        'md' => 2,
        'lg' => 3,
        'xl' => 4,
        '2xl' => 6,
    ];

    protected function getStats(): array
    {
        return [
            Stat::make('Today\'s Procedures', Tindakan::whereDate('created_at', today())->count())
                ->description('Medical procedures today')
                ->descriptionIcon('heroicon-o-clipboard-document-check')
                ->color('success')
                ->chart([7, 2, 10, 3, 15, 4, 17])
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                    'wire:click' => '$dispatch("open-procedures-modal")',
                ]),
                
            Stat::make('Active Users', User::where('is_active', true)->count())
                ->description('Currently active')
                ->descriptionIcon('heroicon-o-users')
                ->color('primary')
                ->chart([15, 20, 18, 25, 22, 30, 28])
                ->extraAttributes([
                    'class' => 'cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800',
                ]),
                
            Stat::make('Today\'s Revenue', 'Rp ' . number_format(Pendapatan::whereDate('created_at', today())->sum('jumlah'), 0, ',', '.'))
                ->description('Income today')
                ->descriptionIcon('heroicon-o-banknotes')
                ->color('success')
                ->chart([100, 150, 200, 180, 220, 250, 300])
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                ]),
                
            Stat::make('New Patients', Pasien::whereDate('created_at', today())->count())
                ->description('Registered today')
                ->descriptionIcon('heroicon-o-user-plus')
                ->color('warning')
                ->chart([2, 1, 3, 2, 4, 3, 5])
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                ]),
        ];
    }
    
    protected function getColumns(): int
    {
        return 2; // Mobile-first: 2 columns on mobile, responsive on larger screens
    }
    
    public static function canView(): bool
    {
        return auth()->user()?->hasRole(['admin', 'manajer', 'bendahara']) ?? false;
    }
}