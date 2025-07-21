<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;
use App\Models\Tindakan;
use App\Models\Pendapatan;

class ResponsiveAnalyticsWidget extends ChartWidget
{
    protected static ?string $heading = 'Weekly Analytics';
    
    protected static ?int $sort = 6;
    
    // protected static ?string $pollingInterval = null; // DISABLED - emergency polling removal
    
    protected int | string | array $columnSpan = [
        'sm' => 2,
        'md' => 3,
        'lg' => 4,
        'xl' => 6,
        '2xl' => 8,
    ];
    
    protected static ?string $maxHeight = '400px';

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getData(): array
    {
        // Get procedure types distribution
        $procedureTypes = DB::table('tindakan')
            ->join('jenis_tindakan', 'tindakan.jenis_tindakan_id', '=', 'jenis_tindakan.id')
            ->select('jenis_tindakan.nama', DB::raw('COUNT(*) as count'))
            ->where('tindakan.created_at', '>=', now()->subDays(7))
            ->groupBy('jenis_tindakan.nama')
            ->orderBy('count', 'desc')
            ->limit(8)
            ->get();

        $colors = [
            'rgba(59, 130, 246, 0.8)',   // Blue
            'rgba(34, 197, 94, 0.8)',    // Green
            'rgba(251, 146, 60, 0.8)',   // Orange
            'rgba(239, 68, 68, 0.8)',    // Red
            'rgba(168, 85, 247, 0.8)',   // Purple
            'rgba(236, 72, 153, 0.8)',   // Pink
            'rgba(14, 165, 233, 0.8)',   // Light Blue
            'rgba(161, 161, 170, 0.8)',  // Gray
        ];

        return [
            'datasets' => [
                [
                    'data' => $procedureTypes->pluck('count')->toArray(),
                    'backgroundColor' => array_slice($colors, 0, $procedureTypes->count()),
                    'borderColor' => array_map(fn($color) => str_replace('0.8', '1', $color), array_slice($colors, 0, $procedureTypes->count())),
                    'borderWidth' => 2,
                ],
            ],
            'labels' => $procedureTypes->pluck('nama')->toArray(),
        ];
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                    'labels' => [
                        'usePointStyle' => true,
                        'padding' => 20,
                        'font' => [
                            'size' => 12,
                        ],
                    ],
                ],
                'tooltip' => [
                    'enabled' => true,
                    'callbacks' => [
                        'label' => 'function(context) { return context.label + ": " + context.parsed + " procedures"; }',
                    ],
                ],
            ],
            'cutout' => '60%',
        ];
    }
    
    public static function canView(): bool
    {
        return auth()->user()?->hasRole(['admin', 'manajer', 'bendahara']) ?? false;
    }
}