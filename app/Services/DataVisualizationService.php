<?php

namespace App\Services;

use App\Models\Pendapatan;
use App\Models\Pengeluaran;
use App\Models\Tindakan;
use App\Models\JumlahPasienHarian;
use App\Services\BendaharaStatsService;
use App\Services\LocalizationService;
use App\Helpers\CurrencyHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

class DataVisualizationService
{
    protected BendaharaStatsService $statsService;
    protected LocalizationService $localizationService;
    
    protected array $chartTypes = [
        'line' => 'Line Chart',
        'bar' => 'Bar Chart', 
        'doughnut' => 'Doughnut Chart',
        'pie' => 'Pie Chart',
        'area' => 'Area Chart',
        'scatter' => 'Scatter Chart',
        'radar' => 'Radar Chart',
        'bubble' => 'Bubble Chart',
    ];

    protected array $colorSchemes = [
        'primary' => ['#3B82F6', '#1D4ED8', '#1E40AF', '#1E3A8A'],
        'success' => ['#10B981', '#059669', '#047857', '#065F46'],
        'warning' => ['#F59E0B', '#D97706', '#B45309', '#92400E'],
        'danger' => ['#EF4444', '#DC2626', '#B91C1C', '#991B1B'],
        'info' => ['#06B6D4', '#0891B2', '#0E7490', '#155E75'],
        'gradient' => ['#667EEA', '#764BA2', '#F093FB', '#F5576C'],
        'financial' => ['#10B981', '#EF4444', '#F59E0B', '#3B82F6'],
    ];

    public function __construct(
        BendaharaStatsService $statsService,
        LocalizationService $localizationService
    ) {
        $this->statsService = $statsService;
        $this->localizationService = $localizationService;
    }

    /**
     * Generate comprehensive financial dashboard charts
     */
    public function generateFinancialDashboard(array $options = []): array
    {
        $period = $options['period'] ?? 'monthly';
        $months = $options['months'] ?? 12;
        
        try {
            return [
                'revenue_trend' => $this->generateRevenueTrendChart($period, $months),
                'expense_breakdown' => $this->generateExpenseBreakdownChart($period, $months),
                'cash_flow_analysis' => $this->generateCashFlowChart($period, $months),
                'profit_margin_trend' => $this->generateProfitMarginChart($period, $months),
                'patient_revenue_correlation' => $this->generatePatientRevenueCorrelation($period, $months),
                'service_performance' => $this->generateServicePerformanceChart($period, $months),
                'budget_utilization' => $this->generateBudgetUtilizationChart($period, $months),
                'seasonal_analysis' => $this->generateSeasonalAnalysisChart($months),
                'kpi_dashboard' => $this->generateKPIDashboard($period, $months),
                'comparative_analysis' => $this->generateComparativeAnalysis($period, $months),
            ];
        } catch (Exception $e) {
            Log::error('DataVisualizationService: Failed to generate dashboard', [
                'error' => $e->getMessage(),
                'options' => $options,
            ]);
            
            return ['error' => 'Failed to generate dashboard charts'];
        }
    }

    /**
     * Generate revenue trend chart
     */
    public function generateRevenueTrendChart(string $period = 'monthly', int $months = 12): array
    {
        $data = $this->getRevenueData($period, $months);
        
        return [
            'type' => 'line',
            'title' => $this->localizationService->trans('bendahara.revenue_trend'),
            'data' => [
                'labels' => array_keys($data),
                'datasets' => [
                    [
                        'label' => $this->localizationService->trans('bendahara.revenue'),
                        'data' => array_values($data),
                        'borderColor' => $this->colorSchemes['success'][0],
                        'backgroundColor' => $this->hexToRgba($this->colorSchemes['success'][0], 0.1),
                        'borderWidth' => 3,
                        'fill' => true,
                        'tension' => 0.4,
                        'pointBackgroundColor' => $this->colorSchemes['success'][0],
                        'pointBorderColor' => '#fff',
                        'pointBorderWidth' => 2,
                        'pointRadius' => 6,
                        'pointHoverRadius' => 8,
                    ],
                ],
            ],
            'options' => $this->getChartOptions('currency'),
            'insights' => $this->generateRevenueInsights($data),
        ];
    }

    /**
     * Generate expense breakdown chart
     */
    public function generateExpenseBreakdownChart(string $period = 'monthly', int $months = 12): array
    {
        $data = $this->getExpenseBreakdownData($period, $months);
        
        return [
            'type' => 'doughnut',
            'title' => $this->localizationService->trans('bendahara.expense_breakdown'),
            'data' => [
                'labels' => array_keys($data),
                'datasets' => [
                    [
                        'data' => array_values($data),
                        'backgroundColor' => array_slice($this->colorSchemes['financial'], 0, count($data)),
                        'borderColor' => '#fff',
                        'borderWidth' => 2,
                        'hoverOffset' => 10,
                    ],
                ],
            ],
            'options' => array_merge($this->getChartOptions('currency'), [
                'plugins' => [
                    'legend' => [
                        'position' => 'right',
                        'labels' => [
                            'usePointStyle' => true,
                            'padding' => 20,
                        ],
                    ],
                ],
                'cutout' => '60%',
            ]),
            'insights' => $this->generateExpenseInsights($data),
        ];
    }

    /**
     * Generate cash flow chart
     */
    public function generateCashFlowChart(string $period = 'monthly', int $months = 12): array
    {
        $revenueData = $this->getRevenueData($period, $months);
        $expenseData = $this->getExpenseData($period, $months);
        $netFlow = [];
        
        foreach ($revenueData as $period => $revenue) {
            $expense = $expenseData[$period] ?? 0;
            $netFlow[$period] = $revenue - $expense;
        }
        
        return [
            'type' => 'bar',
            'title' => $this->localizationService->trans('bendahara.cash_flow_analysis'),
            'data' => [
                'labels' => array_keys($revenueData),
                'datasets' => [
                    [
                        'label' => $this->localizationService->trans('bendahara.revenue'),
                        'data' => array_values($revenueData),
                        'backgroundColor' => $this->colorSchemes['success'][0],
                        'borderColor' => $this->colorSchemes['success'][1],
                        'borderWidth' => 1,
                    ],
                    [
                        'label' => $this->localizationService->trans('bendahara.expense'),
                        'data' => array_values($expenseData),
                        'backgroundColor' => $this->colorSchemes['danger'][0],
                        'borderColor' => $this->colorSchemes['danger'][1],
                        'borderWidth' => 1,
                    ],
                    [
                        'label' => $this->localizationService->trans('bendahara.net_income'),
                        'data' => array_values($netFlow),
                        'type' => 'line',
                        'borderColor' => $this->colorSchemes['primary'][0],
                        'backgroundColor' => $this->hexToRgba($this->colorSchemes['primary'][0], 0.1),
                        'borderWidth' => 3,
                        'fill' => false,
                        'tension' => 0.4,
                        'yAxisID' => 'y1',
                    ],
                ],
            ],
            'options' => array_merge($this->getChartOptions('currency'), [
                'scales' => [
                    'y' => [
                        'type' => 'linear',
                        'display' => true,
                        'position' => 'left',
                    ],
                    'y1' => [
                        'type' => 'linear',
                        'display' => true,
                        'position' => 'right',
                        'grid' => [
                            'drawOnChartArea' => false,
                        ],
                    ],
                ],
            ]),
            'insights' => $this->generateCashFlowInsights($netFlow),
        ];
    }

    /**
     * Generate profit margin trend chart
     */
    public function generateProfitMarginChart(string $period = 'monthly', int $months = 12): array
    {
        $revenueData = $this->getRevenueData($period, $months);
        $expenseData = $this->getExpenseData($period, $months);
        $marginData = [];
        
        foreach ($revenueData as $period => $revenue) {
            $expense = $expenseData[$period] ?? 0;
            $margin = $revenue > 0 ? (($revenue - $expense) / $revenue) * 100 : 0;
            $marginData[$period] = round($margin, 2);
        }
        
        return [
            'type' => 'area',
            'title' => $this->localizationService->trans('bendahara.profit_margin_trend'),
            'data' => [
                'labels' => array_keys($marginData),
                'datasets' => [
                    [
                        'label' => 'Profit Margin (%)',
                        'data' => array_values($marginData),
                        'borderColor' => $this->colorSchemes['info'][0],
                        'backgroundColor' => $this->hexToRgba($this->colorSchemes['info'][0], 0.2),
                        'borderWidth' => 2,
                        'fill' => true,
                        'tension' => 0.4,
                    ],
                ],
            ],
            'options' => $this->getChartOptions('percentage'),
            'insights' => $this->generateMarginInsights($marginData),
        ];
    }

    /**
     * Generate patient-revenue correlation chart
     */
    public function generatePatientRevenueCorrelation(string $period = 'monthly', int $months = 12): array
    {
        $patientData = $this->getPatientData($period, $months);
        $revenueData = $this->getRevenueData($period, $months);
        $correlationData = [];
        
        foreach ($patientData as $period => $patients) {
            $revenue = $revenueData[$period] ?? 0;
            $correlationData[] = [
                'x' => $patients,
                'y' => $revenue / 1000000, // Convert to millions
                'label' => $period,
            ];
        }
        
        return [
            'type' => 'scatter',
            'title' => $this->localizationService->trans('bendahara.patient_revenue_correlation'),
            'data' => [
                'datasets' => [
                    [
                        'label' => 'Patient vs Revenue',
                        'data' => $correlationData,
                        'backgroundColor' => $this->colorSchemes['primary'][0],
                        'borderColor' => $this->colorSchemes['primary'][1],
                        'borderWidth' => 1,
                        'pointRadius' => 8,
                        'pointHoverRadius' => 10,
                    ],
                ],
            ],
            'options' => array_merge($this->getChartOptions('mixed'), [
                'scales' => [
                    'x' => [
                        'title' => [
                            'display' => true,
                            'text' => 'Number of Patients',
                        ],
                    ],
                    'y' => [
                        'title' => [
                            'display' => true,
                            'text' => 'Revenue (Millions)',
                        ],
                    ],
                ],
            ]),
            'insights' => $this->generateCorrelationInsights($correlationData),
        ];
    }

    /**
     * Generate service performance chart
     */
    public function generateServicePerformanceChart(string $period = 'monthly', int $months = 12): array
    {
        $serviceData = $this->getServicePerformanceData($period, $months);
        
        return [
            'type' => 'radar',
            'title' => $this->localizationService->trans('bendahara.service_performance'),
            'data' => [
                'labels' => array_keys($serviceData['current']),
                'datasets' => [
                    [
                        'label' => 'Current Period',
                        'data' => array_values($serviceData['current']),
                        'borderColor' => $this->colorSchemes['primary'][0],
                        'backgroundColor' => $this->hexToRgba($this->colorSchemes['primary'][0], 0.2),
                        'borderWidth' => 2,
                        'pointBackgroundColor' => $this->colorSchemes['primary'][0],
                    ],
                    [
                        'label' => 'Previous Period',
                        'data' => array_values($serviceData['previous']),
                        'borderColor' => $this->colorSchemes['warning'][0],
                        'backgroundColor' => $this->hexToRgba($this->colorSchemes['warning'][0], 0.2),
                        'borderWidth' => 2,
                        'pointBackgroundColor' => $this->colorSchemes['warning'][0],
                        'borderDash' => [5, 5],
                    ],
                ],
            ],
            'options' => array_merge($this->getChartOptions('number'), [
                'scales' => [
                    'r' => [
                        'beginAtZero' => true,
                        'max' => 100,
                    ],
                ],
            ]),
            'insights' => $this->generateServiceInsights($serviceData),
        ];
    }

    /**
     * Generate budget utilization chart
     */
    public function generateBudgetUtilizationChart(string $period = 'monthly', int $months = 12): array
    {
        $budgetData = $this->getBudgetUtilizationData($period, $months);
        
        return [
            'type' => 'bar',
            'title' => $this->localizationService->trans('bendahara.budget_utilization'),
            'data' => [
                'labels' => array_keys($budgetData['categories']),
                'datasets' => [
                    [
                        'label' => 'Budget',
                        'data' => array_values($budgetData['budget']),
                        'backgroundColor' => $this->hexToRgba($this->colorSchemes['info'][0], 0.3),
                        'borderColor' => $this->colorSchemes['info'][0],
                        'borderWidth' => 1,
                    ],
                    [
                        'label' => 'Actual',
                        'data' => array_values($budgetData['actual']),
                        'backgroundColor' => array_map(function($budget, $actual) {
                            $utilization = $budget > 0 ? ($actual / $budget) * 100 : 0;
                            if ($utilization > 100) return $this->colorSchemes['danger'][0];
                            if ($utilization > 80) return $this->colorSchemes['warning'][0];
                            return $this->colorSchemes['success'][0];
                        }, $budgetData['budget'], $budgetData['actual']),
                        'borderWidth' => 1,
                    ],
                ],
            ],
            'options' => $this->getChartOptions('currency'),
            'insights' => $this->generateBudgetInsights($budgetData),
        ];
    }

    /**
     * Generate seasonal analysis chart
     */
    public function generateSeasonalAnalysisChart(int $months = 24): array
    {
        $seasonalData = $this->getSeasonalData($months);
        
        return [
            'type' => 'line',
            'title' => $this->localizationService->trans('bendahara.seasonal_analysis'),
            'data' => [
                'labels' => array_keys($seasonalData['months']),
                'datasets' => [
                    [
                        'label' => 'Revenue Trend',
                        'data' => array_values($seasonalData['revenue']),
                        'borderColor' => $this->colorSchemes['success'][0],
                        'backgroundColor' => $this->hexToRgba($this->colorSchemes['success'][0], 0.1),
                        'borderWidth' => 2,
                        'fill' => true,
                        'tension' => 0.4,
                    ],
                    [
                        'label' => 'Patient Volume',
                        'data' => array_values($seasonalData['patients']),
                        'borderColor' => $this->colorSchemes['primary'][0],
                        'backgroundColor' => $this->hexToRgba($this->colorSchemes['primary'][0], 0.1),
                        'borderWidth' => 2,
                        'fill' => false,
                        'tension' => 0.4,
                        'yAxisID' => 'y1',
                    ],
                ],
            ],
            'options' => array_merge($this->getChartOptions('mixed'), [
                'scales' => [
                    'y' => [
                        'type' => 'linear',
                        'display' => true,
                        'position' => 'left',
                        'title' => ['display' => true, 'text' => 'Revenue'],
                    ],
                    'y1' => [
                        'type' => 'linear',
                        'display' => true,
                        'position' => 'right',
                        'title' => ['display' => true, 'text' => 'Patients'],
                        'grid' => ['drawOnChartArea' => false],
                    ],
                ],
            ]),
            'insights' => $this->generateSeasonalInsights($seasonalData),
        ];
    }

    /**
     * Generate KPI dashboard
     */
    public function generateKPIDashboard(string $period = 'monthly', int $months = 12): array
    {
        $kpiData = $this->getKPIData($period, $months);
        
        return [
            'type' => 'mixed',
            'title' => $this->localizationService->trans('bendahara.kpi_dashboard'),
            'widgets' => [
                [
                    'type' => 'metric',
                    'title' => 'Total Revenue',
                    'value' => CurrencyHelper::format($kpiData['total_revenue']),
                    'change' => $kpiData['revenue_change'],
                    'color' => 'success',
                ],
                [
                    'type' => 'metric',
                    'title' => 'Net Profit Margin',
                    'value' => number_format($kpiData['profit_margin'], 1) . '%',
                    'change' => $kpiData['margin_change'],
                    'color' => $kpiData['profit_margin'] > 20 ? 'success' : 'warning',
                ],
                [
                    'type' => 'metric',
                    'title' => 'Patient Growth',
                    'value' => CurrencyHelper::formatPercentage($kpiData['patient_growth']),
                    'change' => $kpiData['growth_change'],
                    'color' => $kpiData['patient_growth'] > 0 ? 'success' : 'danger',
                ],
                [
                    'type' => 'gauge',
                    'title' => 'Budget Utilization',
                    'value' => $kpiData['budget_utilization'],
                    'max' => 100,
                    'color' => $this->getBudgetUtilizationColor($kpiData['budget_utilization']),
                ],
            ],
            'insights' => $this->generateKPIInsights($kpiData),
        ];
    }

    /**
     * Generate comparative analysis
     */
    public function generateComparativeAnalysis(string $period = 'monthly', int $months = 12): array
    {
        $comparisonData = $this->getComparisonData($period, $months);
        
        return [
            'type' => 'comparison',
            'title' => $this->localizationService->trans('bendahara.comparative_analysis'),
            'comparisons' => [
                [
                    'title' => 'Revenue Comparison',
                    'current' => $comparisonData['current_revenue'],
                    'previous' => $comparisonData['previous_revenue'],
                    'change' => $comparisonData['revenue_change'],
                    'type' => 'currency',
                ],
                [
                    'title' => 'Expense Comparison',
                    'current' => $comparisonData['current_expense'],
                    'previous' => $comparisonData['previous_expense'],
                    'change' => $comparisonData['expense_change'],
                    'type' => 'currency',
                ],
                [
                    'title' => 'Patient Volume',
                    'current' => $comparisonData['current_patients'],
                    'previous' => $comparisonData['previous_patients'],
                    'change' => $comparisonData['patient_change'],
                    'type' => 'number',
                ],
            ],
            'insights' => $this->generateComparisonInsights($comparisonData),
        ];
    }

    // Helper methods for data retrieval and processing

    protected function getRevenueData(string $period, int $months): array
    {
        return Cache::remember("revenue_data_{$period}_{$months}", 3600, function () use ($period, $months) {
            $startDate = now()->subMonths($months);
            
            $query = Pendapatan::where('created_at', '>=', $startDate)
                ->where('status_validasi', 'disetujui');
                
            $format = $period === 'weekly' ? '%Y-W%u' : '%Y-%m';
            
            return $query->selectRaw("DATE_FORMAT(created_at, '{$format}') as period, SUM(nominal) as total")
                ->groupBy('period')
                ->orderBy('period')
                ->pluck('total', 'period')
                ->toArray();
        });
    }

    protected function getExpenseData(string $period, int $months): array
    {
        return Cache::remember("expense_data_{$period}_{$months}", 3600, function () use ($period, $months) {
            $startDate = now()->subMonths($months);
            
            $query = Pengeluaran::where('created_at', '>=', $startDate)
                ->where('status_validasi', 'disetujui');
                
            $format = $period === 'weekly' ? '%Y-W%u' : '%Y-%m';
            
            return $query->selectRaw("DATE_FORMAT(created_at, '{$format}') as period, SUM(nominal) as total")
                ->groupBy('period')
                ->orderBy('period')
                ->pluck('total', 'period')
                ->toArray();
        });
    }

    protected function getExpenseBreakdownData(string $period, int $months): array
    {
        return Cache::remember("expense_breakdown_{$period}_{$months}", 3600, function () use ($months) {
            $startDate = now()->subMonths($months);
            
            return Pengeluaran::where('created_at', '>=', $startDate)
                ->where('status_validasi', 'disetujui')
                ->selectRaw('SUBSTRING(keterangan, 1, 20) as category, SUM(nominal) as total')
                ->groupBy('category')
                ->orderByDesc('total')
                ->limit(8)
                ->pluck('total', 'category')
                ->toArray();
        });
    }

    protected function getPatientData(string $period, int $months): array
    {
        return Cache::remember("patient_data_{$period}_{$months}", 3600, function () use ($period, $months) {
            $startDate = now()->subMonths($months);
            
            $format = $period === 'weekly' ? '%Y-W%u' : '%Y-%m';
            
            return JumlahPasienHarian::where('tanggal_input', '>=', $startDate)
                ->selectRaw("DATE_FORMAT(tanggal_input, '{$format}') as period, SUM(jumlah_pasien) as total")
                ->groupBy('period')
                ->orderBy('period')
                ->pluck('total', 'period')
                ->toArray();
        });
    }

    protected function getChartOptions(string $type = 'currency'): array
    {
        $baseOptions = [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'top',
                ],
                'tooltip' => [
                    'mode' => 'index',
                    'intersect' => false,
                ],
            ],
            'scales' => [
                'x' => [
                    'display' => true,
                    'grid' => [
                        'display' => false,
                    ],
                ],
                'y' => [
                    'display' => true,
                    'beginAtZero' => true,
                ],
            ],
        ];

        if ($type === 'currency') {
            $baseOptions['plugins']['tooltip']['callbacks'] = [
                'label' => 'function(context) {
                    return context.dataset.label + ": " + new Intl.NumberFormat("id-ID", {
                        style: "currency",
                        currency: "IDR",
                        minimumFractionDigits: 0
                    }).format(context.parsed.y);
                }',
            ];
        }

        return $baseOptions;
    }

    protected function hexToRgba(string $hex, float $alpha = 1): string
    {
        $hex = ltrim($hex, '#');
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        
        return "rgba({$r}, {$g}, {$b}, {$alpha})";
    }

    // Additional helper methods would be implemented here for:
    // - generateRevenueInsights()
    // - generateExpenseInsights() 
    // - generateCashFlowInsights()
    // - generateMarginInsights()
    // - generateCorrelationInsights()
    // - generateServiceInsights()
    // - generateBudgetInsights()
    // - generateSeasonalInsights()
    // - generateKPIInsights()
    // - generateComparisonInsights()
    // - getServicePerformanceData()
    // - getBudgetUtilizationData()
    // - getSeasonalData()
    // - getKPIData()
    // - getComparisonData()
    // - getBudgetUtilizationColor()

    protected function generateRevenueInsights(array $data): array
    {
        $values = array_values($data);
        $trend = end($values) > reset($values) ? 'increasing' : 'decreasing';
        $average = array_sum($values) / count($values);
        
        return [
            'trend' => $trend,
            'average' => $average,
            'peak_period' => array_search(max($values), $data),
            'growth_rate' => count($values) > 1 ? (end($values) - reset($values)) / reset($values) * 100 : 0,
        ];
    }

    protected function generateExpenseInsights(array $data): array
    {
        $total = array_sum($data);
        $largest = max($data);
        $largestCategory = array_search($largest, $data);
        
        return [
            'total_expense' => $total,
            'largest_category' => $largestCategory,
            'largest_amount' => $largest,
            'category_percentage' => ($largest / $total) * 100,
        ];
    }

    protected function generateCashFlowInsights(array $netFlow): array
    {
        $positive = array_filter($netFlow, fn($flow) => $flow > 0);
        $negative = array_filter($netFlow, fn($flow) => $flow < 0);
        
        return [
            'positive_months' => count($positive),
            'negative_months' => count($negative),
            'average_flow' => array_sum($netFlow) / count($netFlow),
            'volatility' => $this->calculateVolatility($netFlow),
        ];
    }

    protected function calculateVolatility(array $data): float
    {
        $mean = array_sum($data) / count($data);
        $variance = array_sum(array_map(fn($x) => pow($x - $mean, 2), $data)) / count($data);
        return sqrt($variance);
    }
}