<?php

namespace App\Filament\Manajer\Widgets;

use App\Models\PendapatanHarian;
use App\Models\PengeluaranHarian;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class FinancialTrendWidget extends ChartWidget
{
    protected static ?string $heading = '📈 Tren Keuangan Bulan Berjalan';

    protected function getData(): array
    {
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;
        $daysInMonth = Carbon::now()->daysInMonth;

        $dailyRevenue = [];
        $dailyExpense = [];
        $labels = [];

        // Get daily data for the current month
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = Carbon::create($currentYear, $currentMonth, $day);
            
            // Revenue for this day
            $revenue = PendapatanHarian::whereDate('tanggal_input', $date)
                ->where('status_validasi', 'disetujui')
                ->sum('nominal');

            // Expense for this day
            $expense = PengeluaranHarian::whereDate('tanggal_input', $date)
                ->where('status_validasi', 'disetujui')
                ->sum('nominal');

            $dailyRevenue[] = $revenue;
            $dailyExpense[] = $expense;
            $labels[] = $day;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Pendapatan',
                    'data' => $dailyRevenue,
                    'borderColor' => '#22c55e', // Green
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                    'fill' => false,
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Pengeluaran',
                    'data' => $dailyExpense,
                    'borderColor' => '#ef4444', // Red
                    'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
                    'fill' => false,
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
                    'borderColor' => 'rgba(99, 102, 241, 0.3)',
                    'borderWidth' => 1,
                ],
            ],
            'scales' => [
                'x' => [
                    'grid' => [
                        'color' => 'rgba(148, 163, 184, 0.1)',
                        'borderColor' => 'rgba(148, 163, 184, 0.2)',
                    ],
                    'ticks' => [
                        'color' => '#94a3b8',
                        'font' => [
                            'family' => 'Inter, ui-sans-serif, system-ui',
                            'size' => 11,
                        ],
                    ],
                    'title' => [
                        'display' => true,
                        'text' => 'Tanggal (1-31)',
                        'color' => '#cbd5e1',
                    ],
                ],
                'y' => [
                    'beginAtZero' => true,
                    'grid' => [
                        'color' => 'rgba(148, 163, 184, 0.1)',
                        'borderColor' => 'rgba(148, 163, 184, 0.2)',
                    ],
                    'ticks' => [
                        'color' => '#94a3b8',
                        'font' => [
                            'family' => 'Inter, ui-sans-serif, system-ui',
                            'size' => 11,
                        ],
                        'callback' => 'function(value) { return "Rp " + value.toLocaleString(); }',
                    ],
                    'title' => [
                        'display' => true,
                        'text' => 'Nominal (Rupiah)',
                        'color' => '#cbd5e1',
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
                    'radius' => 3,
                    'hoverRadius' => 5,
                    'borderWidth' => 2,
                ],
                'line' => [
                    'borderWidth' => 3,
                ],
            ],
        ];
    }

    public static function canView(): bool
    {
        return auth()->check() && auth()->user()->role?->name === 'manajer';
    }
}