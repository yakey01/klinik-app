<?php

namespace App\Filament\Manajer\Widgets;

use App\Models\Tindakan;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class PatientDistributionWidget extends ChartWidget
{
    protected static ?string $heading = '📊 Distribusi Pasien per Hari';

    protected function getData(): array
    {
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;
        $daysInMonth = Carbon::now()->daysInMonth;

        $pasienUmumDaily = [];
        $pasienBpjsDaily = [];
        $pasienGigiDaily = [];
        $labels = [];

        // Get daily patient distribution for the current month
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = Carbon::create($currentYear, $currentMonth, $day);
            
            // Count patients by category for this day
            $pasienUmum = Tindakan::whereDate('created_at', $date)
                ->whereHas('jenisTindakan', function ($query) {
                    $query->where('kategori', 'umum');
                })
                ->distinct('pasien_id')
                ->count('pasien_id');

            $pasienBpjs = Tindakan::whereDate('created_at', $date)
                ->whereHas('jenisTindakan', function ($query) {
                    $query->where('kategori', 'bpjs');
                })
                ->distinct('pasien_id')
                ->count('pasien_id');

            $pasienGigi = Tindakan::whereDate('created_at', $date)
                ->whereHas('jenisTindakan', function ($query) {
                    $query->where('kategori', 'gigi');
                })
                ->distinct('pasien_id')
                ->count('pasien_id');

            $pasienUmumDaily[] = $pasienUmum;
            $pasienBpjsDaily[] = $pasienBpjs;
            $pasienGigiDaily[] = $pasienGigi;
            $labels[] = $day;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Pasien Umum',
                    'data' => $pasienUmumDaily,
                    'backgroundColor' => '#3b82f6', // Blue
                    'borderColor' => '#2563eb',
                    'borderWidth' => 1,
                ],
                [
                    'label' => 'Pasien BPJS',
                    'data' => $pasienBpjsDaily,
                    'backgroundColor' => '#22c55e', // Green
                    'borderColor' => '#16a34a',
                    'borderWidth' => 1,
                ],
                [
                    'label' => 'Pasien Gigi',
                    'data' => $pasienGigiDaily,
                    'backgroundColor' => '#f59e0b', // Orange
                    'borderColor' => '#d97706',
                    'borderWidth' => 1,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
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
                    'stacked' => true,
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
                        'text' => 'Tanggal',
                        'color' => '#cbd5e1',
                    ],
                ],
                'y' => [
                    'stacked' => true,
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
                        'stepSize' => 1,
                    ],
                    'title' => [
                        'display' => true,
                        'text' => 'Jumlah Pasien',
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
        ];
    }

    public static function canView(): bool
    {
        return auth()->check() && auth()->user()->role?->name === 'manajer';
    }
}