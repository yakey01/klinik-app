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
                    'borderColor' => '#22c55e', // Emerald 500
                    'backgroundColor' => 'rgba(34, 197, 94, 0.15)', // Enhanced visibility
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
                    'labels' => [
                        'color' => '#cbd5e1', // Light text for dark theme
                        'font' => [
                            'family' => 'Inter, ui-sans-serif, system-ui',
                            'size' => 12,
                        ],
                    ],
                ],
                'tooltip' => [
                    'backgroundColor' => 'rgba(30, 41, 59, 0.95)',
                    'titleColor' => '#f1f5f9',
                    'bodyColor' => '#cbd5e1',
                    'borderColor' => 'rgba(34, 197, 94, 0.3)',
                    'borderWidth' => 1,
                ],
            ],
            'scales' => [
                'x' => [
                    'beginAtZero' => true,
                    'grid' => [
                        'color' => 'rgba(148, 163, 184, 0.1)', // Subtle grid lines
                        'borderColor' => 'rgba(148, 163, 184, 0.2)',
                    ],
                    'ticks' => [
                        'color' => '#94a3b8', // Muted text color
                        'font' => [
                            'family' => 'Inter, ui-sans-serif, system-ui',
                            'size' => 11,
                        ],
                    ],
                ],
                'y' => [
                    'beginAtZero' => true,
                    'grid' => [
                        'color' => 'rgba(148, 163, 184, 0.1)', // Subtle grid lines
                        'borderColor' => 'rgba(148, 163, 184, 0.2)',
                    ],
                    'ticks' => [
                        'color' => '#94a3b8', // Muted text color
                        'font' => [
                            'family' => 'Inter, ui-sans-serif, system-ui',
                            'size' => 11,
                        ],
                        'callback' => 'function(value) { return "Rp " + value.toLocaleString(); }',
                    ],
                ],
            ],
            'maintainAspectRatio' => false,
            'responsive' => true,
            'interaction' => [
                'intersect' => false,
                'mode' => 'index',
            ],
            'elements' => [
                'point' => [
                    'radius' => 4,
                    'hoverRadius' => 6,
                    'backgroundColor' => '#22c55e',
                    'borderColor' => '#16a34a',
                    'borderWidth' => 2,
                ],
                'line' => [
                    'borderWidth' => 3,
                ],
            ],
        ];
    }
}