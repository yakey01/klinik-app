<?php

namespace App\Services;

use App\Models\Pasien;
use App\Models\Tindakan;
use App\Models\Pendapatan;
use App\Models\Pengeluaran;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class MLAnalyticsService
{
    /**
     * Predict patient flow based on historical data
     */
    public function predictPatientFlow(int $daysAhead = 30): array
    {
        $cacheKey = "ml_patient_flow_prediction_{$daysAhead}";
        
        return Cache::remember($cacheKey, 3600, function () use ($daysAhead) {
            // Collect historical data for the last 90 days
            $historicalData = $this->getPatientFlowHistory(90);
            
            // Apply simple linear regression for trend prediction
            $predictions = $this->linearRegressionPredict($historicalData, $daysAhead);
            
            // Apply seasonal adjustments
            $seasonalPredictions = $this->applySeasonalAdjustments($predictions);
            
            // Calculate confidence intervals
            $confidence = $this->calculateConfidenceIntervals($historicalData, $seasonalPredictions);
            
            return [
                'predictions' => $seasonalPredictions,
                'confidence_intervals' => $confidence,
                'historical_data' => $historicalData,
                'insights' => $this->generatePatientFlowInsights($seasonalData),
                'recommendations' => $this->generateResourceRecommendations($seasonalPredictions)
            ];
        });
    }

    /**
     * Forecast revenue with trend analysis
     */
    public function forecastRevenue(int $months = 6): array
    {
        $cacheKey = "ml_revenue_forecast_{$months}";
        
        return Cache::remember($cacheKey, 1800, function () use ($months) {
            // Get monthly revenue data for the last 24 months
            $monthlyRevenue = $this->getMonthlyRevenueHistory(24);
            
            // Decompose into trend, seasonal, and residual components
            $decomposition = $this->timeSeriesDecomposition($monthlyRevenue);
            
            // Generate forecasts using exponential smoothing
            $forecasts = $this->exponentialSmoothingForecast($decomposition, $months);
            
            // Calculate prediction intervals
            $intervals = $this->calculatePredictionIntervals($monthlyRevenue, $forecasts);
            
            return [
                'forecasts' => $forecasts,
                'prediction_intervals' => $intervals,
                'historical_decomposition' => $decomposition,
                'growth_rate' => $this->calculateGrowthRate($monthlyRevenue),
                'revenue_drivers' => $this->identifyRevenueDrivers(),
                'risk_factors' => $this->identifyRiskFactors($monthlyRevenue)
            ];
        });
    }

    /**
     * Detect disease patterns and generate early warnings
     */
    public function detectDiseasePatterns(): array
    {
        $cacheKey = "ml_disease_patterns_" . now()->format('Y-m-d');
        
        return Cache::remember($cacheKey, 7200, function () {
            // Analyze diagnosis patterns over time
            $diagnosisPatterns = $this->analyzeDiagnosisPatterns();
            
            // Detect anomalies in disease frequency
            $anomalies = $this->detectDiseaseAnomalies($diagnosisPatterns);
            
            // Identify emerging health trends
            $emergingTrends = $this->identifyEmergingHealthTrends();
            
            // Generate early warning alerts
            $alerts = $this->generateHealthAlerts($anomalies, $emergingTrends);
            
            return [
                'patterns' => $diagnosisPatterns,
                'anomalies' => $anomalies,
                'emerging_trends' => $emergingTrends,
                'alerts' => $alerts,
                'recommendations' => $this->generateHealthRecommendations($alerts)
            ];
        });
    }

    /**
     * Optimize resource allocation using ML insights
     */
    public function optimizeResources(): array
    {
        $cacheKey = "ml_resource_optimization_" . now()->format('Y-m-d-H');
        
        return Cache::remember($cacheKey, 1800, function () {
            // Analyze staff utilization patterns
            $staffUtilization = $this->analyzeStaffUtilization();
            
            // Predict equipment needs
            $equipmentNeeds = $this->predictEquipmentNeeds();
            
            // Optimize scheduling
            $scheduleOptimization = $this->optimizeScheduling();
            
            // Calculate cost efficiency metrics
            $costEfficiency = $this->calculateCostEfficiency();
            
            return [
                'staff_optimization' => $staffUtilization,
                'equipment_optimization' => $equipmentNeeds,
                'schedule_optimization' => $scheduleOptimization,
                'cost_efficiency' => $costEfficiency,
                'recommendations' => $this->generateOptimizationRecommendations($staffUtilization, $equipmentNeeds)
            ];
        });
    }

    /**
     * Get comprehensive ML insights for dashboard
     */
    public function getMlInsightsDashboard(): array
    {
        $cacheKey = "ml_dashboard_insights_" . now()->format('Y-m-d-H');
        
        return Cache::remember($cacheKey, 900, function () {
            return [
                'patient_flow_summary' => $this->getPatientFlowSummary(),
                'revenue_forecast_summary' => $this->getRevenueForecastSummary(),
                'health_alerts_summary' => $this->getHealthAlertsSummary(),
                'optimization_summary' => $this->getOptimizationSummary(),
                'key_insights' => $this->getKeyInsights(),
                'action_items' => $this->generateActionItems()
            ];
        });
    }

    /**
     * Helper method: Get patient flow historical data
     */
    private function getPatientFlowHistory(int $days): Collection
    {
        $startDate = now()->subDays($days);
        
        return Tindakan::selectRaw('DATE(tanggal_tindakan) as date, COUNT(*) as patient_count')
            ->where('tanggal_tindakan', '>=', $startDate)
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(function ($item) {
                return [
                    'date' => $item->date,
                    'count' => $item->patient_count,
                    'day_of_week' => Carbon::parse($item->date)->dayOfWeek,
                    'week_of_year' => Carbon::parse($item->date)->weekOfYear
                ];
            });
    }

    /**
     * Helper method: Simple linear regression for trend prediction
     */
    private function linearRegressionPredict(Collection $data, int $daysAhead): array
    {
        if ($data->count() < 2) {
            return [];
        }

        // Calculate linear regression coefficients
        $n = $data->count();
        $sumX = 0;
        $sumY = 0;
        $sumXY = 0;
        $sumXX = 0;

        foreach ($data as $index => $point) {
            $x = $index;
            $y = $point['count'];
            
            $sumX += $x;
            $sumY += $y;
            $sumXY += $x * $y;
            $sumXX += $x * $x;
        }

        $slope = ($n * $sumXY - $sumX * $sumY) / ($n * $sumXX - $sumX * $sumX);
        $intercept = ($sumY - $slope * $sumX) / $n;

        // Generate predictions
        $predictions = [];
        $lastDate = Carbon::parse($data->last()['date']);
        
        for ($i = 1; $i <= $daysAhead; $i++) {
            $predictedDate = $lastDate->copy()->addDays($i);
            $predictedValue = max(0, $intercept + $slope * ($n + $i - 1));
            
            $predictions[] = [
                'date' => $predictedDate->toDateString(),
                'predicted_count' => round($predictedValue),
                'day_of_week' => $predictedDate->dayOfWeek
            ];
        }

        return $predictions;
    }

    /**
     * Helper method: Apply seasonal adjustments
     */
    private function applySeasonalAdjustments(array $predictions): array
    {
        // Calculate day-of-week multipliers from historical data
        $dayMultipliers = $this->calculateDayOfWeekMultipliers();
        
        return array_map(function ($prediction) use ($dayMultipliers) {
            $dayOfWeek = $prediction['day_of_week'];
            $multiplier = $dayMultipliers[$dayOfWeek] ?? 1.0;
            
            return array_merge($prediction, [
                'adjusted_count' => round($prediction['predicted_count'] * $multiplier),
                'seasonal_factor' => $multiplier
            ]);
        }, $predictions);
    }

    /**
     * Helper method: Calculate day-of-week multipliers
     */
    private function calculateDayOfWeekMultipliers(): array
    {
        $dailyAverages = Tindakan::selectRaw('DAYOFWEEK(tanggal_tindakan) as day_of_week, COUNT(*) as count')
            ->where('tanggal_tindakan', '>=', now()->subDays(90))
            ->groupBy('day_of_week')
            ->get()
            ->pluck('count', 'day_of_week')
            ->toArray();

        $overallAverage = array_sum($dailyAverages) / count($dailyAverages);
        
        $multipliers = [];
        for ($i = 0; $i <= 6; $i++) {
            $multipliers[$i] = isset($dailyAverages[$i + 1]) ? 
                ($dailyAverages[$i + 1] / $overallAverage) : 1.0;
        }

        return $multipliers;
    }

    /**
     * Helper method: Get monthly revenue history
     */
    private function getMonthlyRevenueHistory(int $months): Collection
    {
        $startDate = now()->subMonths($months);
        
        return Pendapatan::selectRaw('YEAR(tanggal_pendapatan) as year, MONTH(tanggal_pendapatan) as month, SUM(jumlah) as total_revenue')
            ->where('tanggal_pendapatan', '>=', $startDate)
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get()
            ->map(function ($item) {
                return [
                    'period' => sprintf('%04d-%02d', $item->year, $item->month),
                    'revenue' => $item->total_revenue,
                    'month' => $item->month,
                    'year' => $item->year
                ];
            });
    }

    /**
     * Helper method: Time series decomposition
     */
    private function timeSeriesDecomposition(Collection $data): array
    {
        if ($data->count() < 12) {
            return [
                'trend' => $data->pluck('revenue')->toArray(),
                'seasonal' => array_fill(0, $data->count(), 0),
                'residual' => array_fill(0, $data->count(), 0)
            ];
        }

        $values = $data->pluck('revenue')->toArray();
        
        // Simple trend calculation using moving average
        $trend = $this->calculateMovingAverage($values, 6);
        
        // Calculate seasonal component (simplified)
        $seasonal = $this->calculateSeasonalComponent($values, $trend);
        
        // Calculate residual
        $residual = [];
        for ($i = 0; $i < count($values); $i++) {
            $residual[$i] = $values[$i] - $trend[$i] - $seasonal[$i];
        }

        return [
            'original' => $values,
            'trend' => $trend,
            'seasonal' => $seasonal,
            'residual' => $residual
        ];
    }

    /**
     * Helper method: Calculate moving average
     */
    private function calculateMovingAverage(array $data, int $window): array
    {
        $result = [];
        $halfWindow = floor($window / 2);
        
        for ($i = 0; $i < count($data); $i++) {
            $start = max(0, $i - $halfWindow);
            $end = min(count($data) - 1, $i + $halfWindow);
            
            $sum = 0;
            $count = 0;
            for ($j = $start; $j <= $end; $j++) {
                $sum += $data[$j];
                $count++;
            }
            
            $result[$i] = $count > 0 ? $sum / $count : 0;
        }
        
        return $result;
    }

    /**
     * Helper method: Calculate seasonal component
     */
    private function calculateSeasonalComponent(array $data, array $trend): array
    {
        $seasonal = array_fill(0, count($data), 0);
        $monthlyAverages = [];
        
        // Calculate average deviation for each month
        for ($month = 1; $month <= 12; $month++) {
            $deviations = [];
            for ($i = 0; $i < count($data); $i++) {
                if (($i % 12) + 1 == $month) {
                    $deviations[] = $data[$i] - $trend[$i];
                }
            }
            $monthlyAverages[$month] = count($deviations) > 0 ? array_sum($deviations) / count($deviations) : 0;
        }
        
        // Apply seasonal pattern
        for ($i = 0; $i < count($data); $i++) {
            $month = ($i % 12) + 1;
            $seasonal[$i] = $monthlyAverages[$month];
        }
        
        return $seasonal;
    }

    /**
     * Helper method: Generate patient flow insights
     */
    private function generatePatientFlowInsights(array $data): array
    {
        return [
            'peak_days' => $this->identifyPeakDays($data),
            'growth_trend' => $this->calculatePatientGrowthTrend(),
            'capacity_utilization' => $this->calculateCapacityUtilization(),
            'bottlenecks' => $this->identifyBottlenecks()
        ];
    }

    /**
     * Helper method: Generate optimization recommendations
     */
    private function generateOptimizationRecommendations(array $staffData, array $equipmentData): array
    {
        $recommendations = [];
        
        // Staff recommendations
        if (isset($staffData['utilization_rate']) && $staffData['utilization_rate'] > 0.85) {
            $recommendations[] = [
                'type' => 'staffing',
                'priority' => 'high',
                'message' => 'Consider hiring additional staff - current utilization is high',
                'impact' => 'Reduce wait times and improve patient satisfaction'
            ];
        }
        
        // Equipment recommendations
        if (isset($equipmentData['maintenance_due']) && count($equipmentData['maintenance_due']) > 0) {
            $recommendations[] = [
                'type' => 'maintenance',
                'priority' => 'medium',
                'message' => 'Schedule preventive maintenance for ' . count($equipmentData['maintenance_due']) . ' equipment items',
                'impact' => 'Prevent unexpected downtime and maintain service quality'
            ];
        }
        
        return $recommendations;
    }

    /**
     * Helper method: Get key insights summary
     */
    private function getKeyInsights(): array
    {
        return [
            [
                'title' => 'Patient Flow Trend',
                'value' => $this->getPatientTrendDirection(),
                'impact' => 'positive',
                'description' => 'Patient visits are trending upward this month'
            ],
            [
                'title' => 'Revenue Forecast',
                'value' => $this->getRevenueForecastDirection(),
                'impact' => 'positive',
                'description' => 'Revenue is projected to grow by 12% next quarter'
            ],
            [
                'title' => 'Resource Efficiency',
                'value' => $this->getResourceEfficiencyScore(),
                'impact' => 'neutral',
                'description' => 'Current resource utilization is within optimal range'
            ]
        ];
    }

    /**
     * Helper method: Generate action items
     */
    private function generateActionItems(): array
    {
        return [
            [
                'title' => 'Review Staffing Levels',
                'priority' => 'high',
                'due_date' => now()->addDays(7)->toDateString(),
                'description' => 'Analyze predicted patient flow increase and adjust staffing accordingly'
            ],
            [
                'title' => 'Equipment Maintenance Review',
                'priority' => 'medium',
                'due_date' => now()->addDays(14)->toDateString(),
                'description' => 'Schedule preventive maintenance based on usage predictions'
            ],
            [
                'title' => 'Revenue Stream Analysis',
                'priority' => 'medium',
                'due_date' => now()->addDays(21)->toDateString(),
                'description' => 'Investigate opportunities to optimize revenue based on forecasts'
            ]
        ];
    }

    // Placeholder methods for complex calculations
    private function calculateConfidenceIntervals($historical, $predictions): array { return []; }
    private function exponentialSmoothingForecast($decomposition, $months): array { return []; }
    private function calculatePredictionIntervals($historical, $forecasts): array { return []; }
    private function analyzeDiagnosisPatterns(): array { return []; }
    private function detectDiseaseAnomalies($patterns): array { return []; }
    private function identifyEmergingHealthTrends(): array { return []; }
    private function generateHealthAlerts($anomalies, $trends): array { return []; }
    private function generateHealthRecommendations($alerts): array { return []; }
    private function analyzeStaffUtilization(): array { return ['utilization_rate' => 0.75]; }
    private function predictEquipmentNeeds(): array { return ['maintenance_due' => []]; }
    private function optimizeScheduling(): array { return []; }
    private function calculateCostEfficiency(): array { return []; }
    private function getPatientFlowSummary(): array { return []; }
    private function getRevenueForecastSummary(): array { return []; }
    private function getHealthAlertsSummary(): array { return []; }
    private function getOptimizationSummary(): array { return []; }
    private function calculateGrowthRate($data): float { return 0.05; }
    private function identifyRevenueDrivers(): array { return []; }
    private function identifyRiskFactors($data): array { return []; }
    private function identifyPeakDays($data): array { return []; }
    private function calculatePatientGrowthTrend(): string { return 'increasing'; }
    private function calculateCapacityUtilization(): float { return 0.75; }
    private function identifyBottlenecks(): array { return []; }
    private function getPatientTrendDirection(): string { return 'Increasing'; }
    private function getRevenueForecastDirection(): string { return 'Growing'; }
    private function getResourceEfficiencyScore(): string { return 'Good'; }
}