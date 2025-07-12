<?php

namespace App\Filament\Dokter\Widgets;

use App\Models\Jaspel;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class JaspelComparisonWidget extends ChartWidget
{
    protected static ?string $heading = '💰 Perbandingan JASPEL (Bulan Ini vs Bulan Lalu)';
    
    protected static ?int $sort = 2;
    
    protected int | string | array $columnSpan = [
        'md' => 2,
        'xl' => 1,
    ];
    
    protected static ?string $maxHeight = '300px';

    protected function getData(): array
    {
        $userId = Auth::id();
        
        // Current month
        $currentMonthStart = Carbon::now()->startOfMonth();
        $currentMonthEnd = Carbon::now()->endOfMonth();
        
        // Previous month
        $previousMonthStart = Carbon::now()->subMonth()->startOfMonth();
        $previousMonthEnd = Carbon::now()->subMonth()->endOfMonth();
        
        // Get JASPEL data for current month
        $currentMonthJaspel = Jaspel::where('user_id', $userId)
            ->whereBetween('created_at', [$currentMonthStart, $currentMonthEnd])
            ->sum('total_jaspel');
            
        // Get JASPEL data for previous month
        $previousMonthJaspel = Jaspel::where('user_id', $userId)
            ->whereBetween('created_at', [$previousMonthStart, $previousMonthEnd])
            ->sum('total_jaspel');

        return [
            'datasets' => [
                [
                    'label' => 'JASPEL (Rp)',
                    'data' => [$previousMonthJaspel, $currentMonthJaspel],
                    'backgroundColor' => [
                        'rgba(156, 163, 175, 0.8)', // Gray for previous month
                        'rgba(34, 197, 94, 0.8)',   // Green for current month
                    ],
                    'borderColor' => [
                        'rgb(156, 163, 175)',
                        'rgb(34, 197, 94)',
                    ],
                    'borderWidth' => 2,
                ],
            ],
            'labels' => [
                Carbon::now()->subMonth()->format('M Y'),
                Carbon::now()->format('M Y'),
            ],
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
                    'position' => 'top',
                ],
                'tooltip' => [
                    'callbacks' => [
                        'label' => 'function(context) { return "Rp " + new Intl.NumberFormat("id-ID").format(context.parsed.y); }',
                    ],
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'callback' => 'function(value) { return "Rp " + new Intl.NumberFormat("id-ID").format(value); }',
                    ],
                    'grid' => [
                        'color' => 'rgba(156, 163, 175, 0.1)',
                    ],
                ],
                'x' => [
                    'grid' => [
                        'color' => 'rgba(156, 163, 175, 0.1)',
                    ],
                ],
            ],
            'maintainAspectRatio' => false,
            'responsive' => true,
        ];
    }
}