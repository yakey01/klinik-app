<?php

namespace App\Filament\Manajer\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Pendapatan;
use App\Models\Pasien;
use App\Models\Tindakan;
use Illuminate\Support\Facades\DB;

class FinancialOverviewWidget extends ChartWidget
{
    protected static ?string $heading = '💰 Financial Performance Analytics';
    
    protected static ?int $sort = 2;
    
    protected int|string|array $columnSpan = 'full';
    
    protected static bool $isLazy = false;

    protected function getData(): array
    {
        $currentYear = now()->year;
        $previousYear = $currentYear - 1;
        
        // Monthly revenue data for current year
        $monthlyRevenue = collect(range(1, 12))->map(function ($month) use ($currentYear) {
            return Pendapatan::whereMonth('created_at', $month)
                ->whereYear('created_at', $currentYear)
                ->sum('nominal');
        });
        
        // Monthly revenue data for previous year
        $previousYearRevenue = collect(range(1, 12))->map(function ($month) use ($previousYear) {
            return Pendapatan::whereMonth('created_at', $month)
                ->whereYear('created_at', $previousYear)
                ->sum('nominal');
        });
        
        // Monthly patient count
        $monthlyPatients = collect(range(1, 12))->map(function ($month) use ($currentYear) {
            return Pasien::whereMonth('created_at', $month)
                ->whereYear('created_at', $currentYear)
                ->count();
        });
        
        // Monthly procedure count
        $monthlyProcedures = collect(range(1, 12))->map(function ($month) use ($currentYear) {
            return Tindakan::whereMonth('created_at', $month)
                ->whereYear('created_at', $currentYear)
                ->count();
        });
        
        $monthLabels = [
            'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
            'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'
        ];

        return [
            'datasets' => [
                [
                    'label' => 'Revenue ' . $currentYear,
                    'data' => $monthlyRevenue->map(fn($value) => $value / 1000000)->toArray(), // Convert to millions
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'borderColor' => '#10B981',
                    'borderWidth' => 3,
                    'fill' => true,
                    'tension' => 0.4,
                    'pointBackgroundColor' => '#10B981',
                    'pointBorderColor' => '#ffffff',
                    'pointBorderWidth' => 2,
                    'pointRadius' => 5,
                    'pointHoverRadius' => 8,
                    'yAxisID' => 'y'
                ],
                [
                    'label' => 'Revenue ' . $previousYear,
                    'data' => $previousYearRevenue->map(fn($value) => $value / 1000000)->toArray(),
                    'backgroundColor' => 'rgba(107, 114, 128, 0.1)',
                    'borderColor' => '#6B7280',
                    'borderWidth' => 2,
                    'fill' => false,
                    'tension' => 0.4,
                    'borderDash' => [5, 5],
                    'pointBackgroundColor' => '#6B7280',
                    'pointBorderColor' => '#ffffff',
                    'pointBorderWidth' => 2,
                    'pointRadius' => 4,
                    'yAxisID' => 'y'
                ],
                [
                    'label' => 'Patient Count',
                    'data' => $monthlyPatients->toArray(),
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'borderColor' => '#3B82F6',
                    'borderWidth' => 2,
                    'fill' => true,
                    'tension' => 0.4,
                    'pointBackgroundColor' => '#3B82F6',
                    'pointBorderColor' => '#ffffff',
                    'pointBorderWidth' => 2,
                    'pointRadius' => 4,
                    'yAxisID' => 'y1'
                ],
                [
                    'label' => 'Procedures',
                    'data' => $monthlyProcedures->toArray(),
                    'backgroundColor' => 'rgba(139, 92, 246, 0.1)',
                    'borderColor' => '#8B5CF6',
                    'borderWidth' => 2,
                    'fill' => false,
                    'tension' => 0.4,
                    'pointBackgroundColor' => '#8B5CF6',
                    'pointBorderColor' => '#ffffff',
                    'pointBorderWidth' => 2,
                    'pointRadius' => 4,
                    'yAxisID' => 'y1'
                ]
            ],
            'labels' => $monthLabels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
    
    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => [
                    'position' => 'top',
                    'labels' => [
                        'usePointStyle' => true,
                        'padding' => 20,
                        'font' => [
                            'size' => 12,
                            'weight' => '600'
                        ]
                    ]
                ],
                'tooltip' => [
                    'mode' => 'index',
                    'intersect' => false,
                    'backgroundColor' => 'rgba(0, 0, 0, 0.8)',
                    'titleColor' => '#ffffff',
                    'bodyColor' => '#ffffff',
                    'borderColor' => 'rgba(255, 255, 255, 0.1)',
                    'borderWidth' => 1,
                    'cornerRadius' => 8,
                    'displayColors' => true,
                    'callbacks' => [
                        'label' => 'function(context) {
                            let label = context.dataset.label || "";
                            if (label) {
                                label += ": ";
                            }
                            if (context.dataset.yAxisID === "y") {
                                label += "Rp " + new Intl.NumberFormat("id-ID").format(context.parsed.y * 1000000);
                            } else {
                                label += new Intl.NumberFormat("id-ID").format(context.parsed.y);
                            }
                            return label;
                        }'
                    ]
                ]
            ],
            'scales' => [
                'x' => [
                    'grid' => [
                        'display' => false
                    ],
                    'ticks' => [
                        'font' => [
                            'size' => 11,
                            'weight' => '500'
                        ],
                        'color' => '#6B7280'
                    ]
                ],
                'y' => [
                    'type' => 'linear',
                    'display' => true,
                    'position' => 'left',
                    'title' => [
                        'display' => true,
                        'text' => 'Revenue (Millions)',
                        'font' => [
                            'size' => 12,
                            'weight' => '600'
                        ],
                        'color' => '#10B981'
                    ],
                    'grid' => [
                        'color' => 'rgba(0, 0, 0, 0.05)',
                        'drawBorder' => false
                    ],
                    'ticks' => [
                        'font' => [
                            'size' => 11,
                            'weight' => '500'
                        ],
                        'color' => '#6B7280',
                        'callback' => 'function(value) { return "Rp " + value + "M"; }'
                    ]
                ],
                'y1' => [
                    'type' => 'linear',
                    'display' => true,
                    'position' => 'right',
                    'title' => [
                        'display' => true,
                        'text' => 'Count',
                        'font' => [
                            'size' => 12,
                            'weight' => '600'
                        ],
                        'color' => '#3B82F6'
                    ],
                    'grid' => [
                        'drawOnChartArea' => false,
                        'drawBorder' => false
                    ],
                    'ticks' => [
                        'font' => [
                            'size' => 11,
                            'weight' => '500'
                        ],
                        'color' => '#6B7280'
                    ]
                ]
            ],
            'interaction' => [
                'mode' => 'nearest',
                'axis' => 'x',
                'intersect' => false
            ],
            'elements' => [
                'point' => [
                    'hoverRadius' => 8,
                    'hoverBorderWidth' => 3
                ]
            ],
            'animation' => [
                'duration' => 1000,
                'easing' => 'easeInOutQuart'
            ]
        ];
    }
}