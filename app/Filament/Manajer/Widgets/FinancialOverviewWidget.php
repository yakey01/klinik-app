<?php

namespace App\Filament\Manajer\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Pendapatan;

class FinancialOverviewWidget extends ChartWidget
{
    protected static ?string $heading = 'Financial Overview';

    protected function getData(): array
    {
        $months = collect(range(1, 12))->map(function ($month) {
            return [
                'month' => date('M', mktime(0, 0, 0, $month, 1)),
                'revenue' => Pendapatan::whereMonth('created_at', $month)
                    ->whereYear('created_at', now()->year)
                    ->sum('nominal')
            ];
        });

        return [
            'datasets' => [
                [
                    'label' => 'Revenue',
                    'data' => $months->pluck('revenue')->toArray(),
                    'backgroundColor' => '#10B981',
                    'borderColor' => '#059669',
                ],
            ],
            'labels' => $months->pluck('month')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}