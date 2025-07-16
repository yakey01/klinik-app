<?php

namespace App\Filament\Manajer\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Pegawai;
use App\Models\Tindakan;
use App\Models\Pendapatan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class AdvancedKPIAnalyticsWidget extends ChartWidget
{
    protected static ?string $heading = '📊 Advanced Staff Performance Analytics';
    
    protected static ?int $sort = 1;
    
    protected int|string|array $columnSpan = 'full';
    
    protected static bool $isLazy = false;
    
    protected static ?string $maxHeight = '400px';
    
    public ?string $filter = 'performance_trend';
    
    protected function getFilters(): ?array
    {
        return [
            'performance_trend' => 'Performance Trend (6 Months)',
            'comparative_analysis' => 'Staff Comparison (This Month)',
            'predictive_indicators' => 'Predictive Performance Indicators',
            'department_breakdown' => 'Department Performance Breakdown',
        ];
    }

    protected function getData(): array
    {
        return match ($this->filter) {
            'performance_trend' => $this->getPerformanceTrendData(),
            'comparative_analysis' => $this->getComparativeAnalysisData(),
            'predictive_indicators' => $this->getPredictiveIndicatorsData(),
            'department_breakdown' => $this->getDepartmentBreakdownData(),
            default => $this->getPerformanceTrendData(),
        };
    }

    protected function getType(): string
    {
        return match ($this->filter) {
            'performance_trend' => 'line',
            'comparative_analysis' => 'bar',
            'predictive_indicators' => 'line',
            'department_breakdown' => 'doughnut',
            default => 'line',
        };
    }

    private function getPerformanceTrendData(): array
    {
        $months = collect(range(5, 0))->map(function ($monthsAgo) {
            return now()->subMonths($monthsAgo);
        });

        $topPerformers = Pegawai::select('id', 'nama_lengkap')
            ->whereRaw('(SELECT COUNT(*) FROM tindakan WHERE strftime("%m", tindakan.created_at) = ? AND (tindakan.paramedis_id = pegawais.id OR tindakan.non_paramedis_id = pegawais.id) AND tindakan.deleted_at IS NULL) >= 30', [str_pad(now()->month, 2, '0', STR_PAD_LEFT)])
            ->limit(5)
            ->get();

        $datasets = $topPerformers->map(function ($staff) use ($months) {
            $performanceData = $months->map(function ($month) use ($staff) {
                return Tindakan::where(function($query) use ($staff) {
                    $query->where('paramedis_id', $staff->id)
                          ->orWhere('non_paramedis_id', $staff->id);
                })
                ->whereMonth('created_at', $month->month)
                ->whereYear('created_at', $month->year)
                ->count();
            });

            return [
                'label' => $staff->nama_lengkap,
                'data' => $performanceData->toArray(),
                'borderColor' => $this->getRandomColor(),
                'backgroundColor' => $this->getRandomColor(0.1),
                'tension' => 0.4,
                'fill' => false,
            ];
        });

        return [
            'datasets' => $datasets->toArray(),
            'labels' => $months->map(fn($month) => $month->format('M Y'))->toArray(),
        ];
    }

    private function getComparativeAnalysisData(): array
    {
        $currentMonth = now()->month;
        $currentYear = now()->year;

        $staffPerformance = Pegawai::select('id', 'nama_lengkap', 'jenis_pegawai')
            ->get()
            ->map(function ($staff) use ($currentMonth, $currentYear) {
                $procedures = Tindakan::where(function($query) use ($staff) {
                    $query->where('paramedis_id', $staff->id)
                          ->orWhere('non_paramedis_id', $staff->id);
                })
                ->whereMonth('created_at', $currentMonth)
                ->whereYear('created_at', $currentYear)
                ->count();

                $revenue = Pendapatan::whereHas('tindakan', function($query) use ($staff) {
                    $query->where('paramedis_id', $staff->id)
                          ->orWhere('non_paramedis_id', $staff->id);
                })
                ->whereMonth('created_at', $currentMonth)
                ->whereYear('created_at', $currentYear)
                ->sum('nominal');

                return [
                    'name' => $staff->nama_lengkap,
                    'procedures' => $procedures,
                    'revenue' => $revenue / 1000000, // Convert to millions
                    'type' => $staff->jenis_pegawai,
                ];
            })
            ->sortByDesc('procedures')
            ->take(10);

        return [
            'datasets' => [
                [
                    'label' => 'Procedures',
                    'data' => $staffPerformance->pluck('procedures')->toArray(),
                    'backgroundColor' => 'rgba(59, 130, 246, 0.8)',
                    'borderColor' => '#3B82F6',
                    'borderWidth' => 2,
                    'yAxisID' => 'y',
                ],
                [
                    'label' => 'Revenue (M)',
                    'data' => $staffPerformance->pluck('revenue')->toArray(),
                    'backgroundColor' => 'rgba(16, 185, 129, 0.8)',
                    'borderColor' => '#10B981',
                    'borderWidth' => 2,
                    'yAxisID' => 'y1',
                    'type' => 'line',
                ],
            ],
            'labels' => $staffPerformance->pluck('name')->toArray(),
        ];
    }

    private function getPredictiveIndicatorsData(): array
    {
        $last6Months = collect(range(5, 0))->map(function ($monthsAgo) {
            return now()->subMonths($monthsAgo);
        });

        $next3Months = collect(range(1, 3))->map(function ($monthsAhead) {
            return now()->addMonths($monthsAhead);
        });

        $allMonths = $last6Months->concat($next3Months);

        // Historical data
        $historicalData = $last6Months->map(function ($month) {
            return Tindakan::whereMonth('created_at', $month->month)
                ->whereYear('created_at', $month->year)
                ->count();
        });

        // Simple trend prediction (linear regression)
        $trend = $this->calculateTrend($historicalData->toArray());
        $lastValue = $historicalData->last();

        $predictedData = $next3Months->map(function ($month, $index) use ($trend, $lastValue) {
            return max(0, $lastValue + ($trend * ($index + 1)));
        });

        return [
            'datasets' => [
                [
                    'label' => 'Historical Performance',
                    'data' => array_pad($historicalData->toArray(), 9, null),
                    'borderColor' => '#3B82F6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'tension' => 0.4,
                    'fill' => true,
                ],
                [
                    'label' => 'Predicted Performance',
                    'data' => array_pad([], 6, null) + $predictedData->toArray(),
                    'borderColor' => '#EF4444',
                    'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
                    'borderDash' => [5, 5],
                    'tension' => 0.4,
                    'fill' => false,
                ],
            ],
            'labels' => $allMonths->map(fn($month) => $month->format('M Y'))->toArray(),
        ];
    }

    private function getDepartmentBreakdownData(): array
    {
        $currentMonth = now()->month;
        $currentYear = now()->year;

        $departmentData = DB::table('pegawais')
            ->select('jenis_pegawai')
            ->selectRaw('COUNT(*) as staff_count')
            ->selectRaw('AVG(
                (SELECT COUNT(*) FROM tindakan 
                 WHERE strftime("%m", tindakan.created_at) = ? 
                 AND (tindakan.paramedis_id = pegawais.id OR tindakan.non_paramedis_id = pegawais.id) 
                 AND tindakan.deleted_at IS NULL)
            ) as avg_procedures', [str_pad($currentMonth, 2, '0', STR_PAD_LEFT)])
            ->groupBy('jenis_pegawai')
            ->get();

        return [
            'datasets' => [
                [
                    'data' => $departmentData->pluck('avg_procedures')->toArray(),
                    'backgroundColor' => [
                        'rgba(59, 130, 246, 0.8)',
                        'rgba(16, 185, 129, 0.8)',
                        'rgba(245, 158, 11, 0.8)',
                        'rgba(139, 92, 246, 0.8)',
                    ],
                    'borderColor' => [
                        '#3B82F6',
                        '#10B981',
                        '#F59E0B',
                        '#8B5CF6',
                    ],
                    'borderWidth' => 2,
                ],
            ],
            'labels' => $departmentData->pluck('jenis_pegawai')->toArray(),
        ];
    }

    private function calculateTrend(array $data): float
    {
        $n = count($data);
        if ($n < 2) return 0;

        $sumX = array_sum(range(1, $n));
        $sumY = array_sum($data);
        $sumXY = array_sum(array_map(fn($x, $y) => $x * $y, range(1, $n), $data));
        $sumX2 = array_sum(array_map(fn($x) => $x * $x, range(1, $n)));

        return ($n * $sumXY - $sumX * $sumY) / ($n * $sumX2 - $sumX * $sumX);
    }

    private function getRandomColor(float $opacity = 1): string
    {
        $colors = [
            'rgb(59, 130, 246)',
            'rgb(16, 185, 129)',
            'rgb(245, 158, 11)',
            'rgb(139, 92, 246)',
            'rgb(239, 68, 68)',
            'rgb(6, 182, 212)',
            'rgb(236, 72, 153)',
            'rgb(34, 197, 94)',
        ];

        $color = $colors[array_rand($colors)];
        
        if ($opacity < 1) {
            return str_replace('rgb', 'rgba', str_replace(')', ", $opacity)", $color));
        }

        return $color;
    }

    protected function getOptions(): array
    {
        $baseOptions = [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => [
                    'position' => 'top',
                    'labels' => [
                        'usePointStyle' => true,
                        'padding' => 20,
                    ],
                ],
                'tooltip' => [
                    'mode' => 'index',
                    'intersect' => false,
                ],
            ],
        ];

        return match ($this->filter) {
            'comparative_analysis' => array_merge($baseOptions, [
                'scales' => [
                    'x' => [
                        'grid' => ['display' => false],
                    ],
                    'y' => [
                        'type' => 'linear',
                        'display' => true,
                        'position' => 'left',
                        'title' => ['display' => true, 'text' => 'Procedures'],
                    ],
                    'y1' => [
                        'type' => 'linear',
                        'display' => true,
                        'position' => 'right',
                        'title' => ['display' => true, 'text' => 'Revenue (M)'],
                        'grid' => ['drawOnChartArea' => false],
                    ],
                ],
            ]),
            'department_breakdown' => array_merge($baseOptions, [
                'plugins' => array_merge($baseOptions['plugins'], [
                    'tooltip' => [
                        'callbacks' => [
                            'label' => 'function(context) {
                                return context.label + ": " + context.parsed.toFixed(1) + " avg procedures";
                            }',
                        ],
                    ],
                ]),
            ]),
            default => array_merge($baseOptions, [
                'scales' => [
                    'x' => [
                        'grid' => ['display' => false],
                    ],
                    'y' => [
                        'beginAtZero' => true,
                        'title' => ['display' => true, 'text' => 'Procedures'],
                    ],
                ],
            ]),
        };
    }
}