<?php

namespace App\Filament\Widgets;

use App\Models\Pendapatan;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class RevenueChart extends ChartWidget
{
    protected static ?string $heading = 'Tren Pendapatan Bulanan';

    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $data = collect(range(1, 12))->map(function ($month) {
            $monthlyRevenue = Pendapatan::query()
                ->whereYear('created_at', now()->year)
                ->whereMonth('created_at', $month)
                ->sum('jumlah');

            return [
                'month' => Carbon::create()->month($month)->format('M'),
                'revenue' => $monthlyRevenue,
            ];
        });

        return [
            'datasets' => [
                [
                    'label' => 'Pendapatan (Rp)',
                    'data' => $data->pluck('revenue')->toArray(),
                    'backgroundColor' => '#3b82f6',
                    'borderColor' => '#1d4ed8',
                    'fill' => true,
                ],
            ],
            'labels' => $data->pluck('month')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}