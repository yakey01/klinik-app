<?php

namespace App\Filament\Bendahara\Widgets;

use App\Models\PengeluaranHarian;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class PengeluaranMingguanWidget extends ChartWidget
{
    protected static ?string $heading = '📉 Grafik Pengeluaran Mingguan';

    protected function getData(): array
    {
        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();

        $dailyExpense = [];
        $labels = [];

        // Get expense for each day of the week
        for ($date = $startOfWeek->copy(); $date->lte($endOfWeek); $date->addDay()) {
            $expense = PengeluaranHarian::whereDate('tanggal_input', $date)
                ->where('status_validasi', 'disetujui')
                ->sum('nominal');

            $dailyExpense[] = $expense;
            $labels[] = $date->format('d/m');
        }

        return [
            'datasets' => [
                [
                    'label' => 'Pengeluaran Harian (Rp)',
                    'data' => $dailyExpense,
                    'borderColor' => '#ef4444', // Red 500
                    'backgroundColor' => 'rgba(239, 68, 68, 0.15)', // Enhanced visibility
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
                    'borderColor' => 'rgba(239, 68, 68, 0.3)',
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
                    'backgroundColor' => '#ef4444',
                    'borderColor' => '#dc2626',
                    'borderWidth' => 2,
                ],
                'line' => [
                    'borderWidth' => 3,
                ],
            ],
        ];
    }
}