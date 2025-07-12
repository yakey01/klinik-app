<?php

namespace App\Filament\Bendahara\Widgets;

use App\Models\PendapatanHarian;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class PendapatanMingguanWidget extends ChartWidget
{
    protected static ?string $heading = '📈 Grafik Pendapatan Mingguan';

    protected function getData(): array
    {
        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();

        $dailyIncome = [];
        $labels = [];

        // Get income for each day of the week
        for ($date = $startOfWeek->copy(); $date->lte($endOfWeek); $date->addDay()) {
            $income = PendapatanHarian::whereDate('tanggal_input', $date)
                ->where('status_validasi', 'disetujui')
                ->sum('nominal');

            $dailyIncome[] = $income;
            $labels[] = $date->format('d/m');
        }

        return [
            'datasets' => [
                [
                    'label' => 'Pendapatan Harian (Rp)',
                    'data' => $dailyIncome,
                    'borderColor' => 'rgb(34, 197, 94)',
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'callback' => 'function(value) { return "Rp " + value.toLocaleString(); }',
                    ],
                ],
            ],
            'maintainAspectRatio' => false,
        ];
    }
}