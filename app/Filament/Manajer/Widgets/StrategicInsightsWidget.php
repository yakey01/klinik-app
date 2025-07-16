<?php

namespace App\Filament\Manajer\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Pendapatan;
use App\Models\Pengeluaran;

class StrategicInsightsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $monthlyIncome = Pendapatan::whereMonth('created_at', now()->month)->sum('nominal');
        $monthlyExpense = Pengeluaran::whereMonth('created_at', now()->month)->sum('nominal');
        $profit = $monthlyIncome - $monthlyExpense;
        
        return [
            Stat::make('Monthly Profit', 'Rp ' . number_format($profit))
                ->description($profit > 0 ? 'Profitable this month' : 'Loss this month')
                ->descriptionIcon($profit > 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($profit > 0 ? 'success' : 'danger'),
                
            Stat::make('Profit Margin', $monthlyIncome > 0 ? round(($profit / $monthlyIncome) * 100, 1) . '%' : '0%')
                ->description('This month\'s margin')
                ->descriptionIcon('heroicon-m-calculator')
                ->color('info'),
        ];
    }
}