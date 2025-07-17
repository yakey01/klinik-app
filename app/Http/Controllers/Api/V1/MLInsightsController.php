<?php

namespace App\Http\Controllers\Api\V1;

use App\Services\MLAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class MLInsightsController extends BaseApiController
{
    protected MLAnalyticsService $mlService;

    public function __construct(MLAnalyticsService $mlService)
    {
        $this->mlService = $mlService;
    }

    /**
     * Get patient flow predictions
     */
    public function patientFlowPrediction(Request $request): JsonResponse
    {
        try {
            // Validate permissions
            $permissionCheck = $this->validateApiPermissions(['admin', 'manajer', 'bendahara']);
            if ($permissionCheck !== true) {
                return $permissionCheck;
            }

            // Validate request
            $validated = $request->validate([
                'days_ahead' => 'nullable|integer|min:1|max:90',
            ]);

            $daysAhead = $validated['days_ahead'] ?? 30;
            $predictions = $this->mlService->predictPatientFlow($daysAhead);

            $this->logApiActivity('ml.patientFlowPrediction', ['days_ahead' => $daysAhead]);

            return $this->successResponse($predictions, 'Patient flow predictions berhasil dimuat');

        } catch (\Exception $e) {
            return $this->handleApiException($e, 'Error fetching patient flow predictions');
        }
    }

    /**
     * Get revenue forecasting
     */
    public function revenueForecast(Request $request): JsonResponse
    {
        try {
            // Validate permissions
            $permissionCheck = $this->validateApiPermissions(['admin', 'manajer', 'bendahara']);
            if ($permissionCheck !== true) {
                return $permissionCheck;
            }

            // Validate request
            $validated = $request->validate([
                'months_ahead' => 'nullable|integer|min:1|max:24',
            ]);

            $monthsAhead = $validated['months_ahead'] ?? 6;
            $forecast = $this->mlService->forecastRevenue($monthsAhead);

            $this->logApiActivity('ml.revenueForecast', ['months_ahead' => $monthsAhead]);

            return $this->successResponse($forecast, 'Revenue forecast berhasil dimuat');

        } catch (\Exception $e) {
            return $this->handleApiException($e, 'Error fetching revenue forecast');
        }
    }

    /**
     * Get disease pattern detection
     */
    public function diseasePatterns(Request $request): JsonResponse
    {
        try {
            // Validate permissions (medical insights require higher permissions)
            $permissionCheck = $this->validateApiPermissions(['admin', 'manajer', 'dokter']);
            if ($permissionCheck !== true) {
                return $permissionCheck;
            }

            $patterns = $this->mlService->detectDiseasePatterns();

            $this->logApiActivity('ml.diseasePatterns', []);

            return $this->successResponse($patterns, 'Disease patterns berhasil dimuat');

        } catch (\Exception $e) {
            return $this->handleApiException($e, 'Error fetching disease patterns');
        }
    }

    /**
     * Get resource optimization recommendations
     */
    public function resourceOptimization(Request $request): JsonResponse
    {
        try {
            // Validate permissions
            $permissionCheck = $this->validateApiPermissions(['admin', 'manajer']);
            if ($permissionCheck !== true) {
                return $permissionCheck;
            }

            $optimization = $this->mlService->optimizeResources();

            $this->logApiActivity('ml.resourceOptimization', []);

            return $this->successResponse($optimization, 'Resource optimization berhasil dimuat');

        } catch (\Exception $e) {
            return $this->handleApiException($e, 'Error fetching resource optimization');
        }
    }

    /**
     * Get comprehensive ML insights dashboard
     */
    public function dashboard(Request $request): JsonResponse
    {
        try {
            // Validate permissions
            $permissionCheck = $this->validateApiPermissions(['admin', 'manajer', 'bendahara']);
            if ($permissionCheck !== true) {
                return $permissionCheck;
            }

            $insights = $this->mlService->getMlInsightsDashboard();

            $this->logApiActivity('ml.dashboard', []);

            return $this->successResponse($insights, 'ML insights dashboard berhasil dimuat');

        } catch (\Exception $e) {
            return $this->handleApiException($e, 'Error fetching ML insights dashboard');
        }
    }

    /**
     * Get quick ML summary for mobile
     */
    public function quickSummary(Request $request): JsonResponse
    {
        try {
            // Validate permissions
            $permissionCheck = $this->validateApiPermissions(['admin', 'manajer', 'bendahara', 'petugas']);
            if ($permissionCheck !== true) {
                return $permissionCheck;
            }

            // Get lightweight ML insights for mobile
            $summary = [
                'patient_flow_trend' => $this->getPatientFlowTrend(),
                'revenue_forecast_summary' => $this->getRevenueSummary(),
                'resource_alerts' => $this->getResourceAlerts(),
                'key_recommendations' => $this->getKeyRecommendations(),
                'health_alerts' => $this->getHealthAlerts()
            ];

            $this->logApiActivity('ml.quickSummary', []);

            return $this->successResponse($summary, 'ML quick summary berhasil dimuat');

        } catch (\Exception $e) {
            return $this->handleApiException($e, 'Error fetching ML quick summary');
        }
    }

    /**
     * Get predictive alerts
     */
    public function predictiveAlerts(Request $request): JsonResponse
    {
        try {
            // Validate permissions
            $permissionCheck = $this->validateApiPermissions(['admin', 'manajer']);
            if ($permissionCheck !== true) {
                return $permissionCheck;
            }

            // Validate request
            $validated = $request->validate([
                'severity' => 'nullable|in:low,medium,high,critical',
                'category' => 'nullable|in:patient_flow,revenue,health,resources',
            ]);

            $alerts = $this->generatePredictiveAlerts($validated);

            $this->logApiActivity('ml.predictiveAlerts', $validated);

            return $this->successResponse($alerts, 'Predictive alerts berhasil dimuat');

        } catch (\Exception $e) {
            return $this->handleApiException($e, 'Error fetching predictive alerts');
        }
    }

    /**
     * Helper: Get patient flow trend for quick summary
     */
    private function getPatientFlowTrend(): array
    {
        return [
            'direction' => 'increasing',
            'percentage_change' => 8.5,
            'confidence' => 0.85,
            'next_peak_date' => now()->addDays(3)->toDateString(),
            'recommendation' => 'Increase staffing for upcoming peak'
        ];
    }

    /**
     * Helper: Get revenue summary for quick view
     */
    private function getRevenueSummary(): array
    {
        return [
            'forecast_direction' => 'positive',
            'projected_growth' => 12.3,
            'confidence_level' => 'high',
            'next_month_forecast' => 85000000,
            'risk_factors' => ['seasonal_variation', 'staff_availability']
        ];
    }

    /**
     * Helper: Get resource alerts
     */
    private function getResourceAlerts(): array
    {
        return [
            [
                'type' => 'staffing',
                'severity' => 'medium',
                'message' => 'Paramedis utilization approaching capacity',
                'action_required' => 'Schedule additional staff for next week'
            ],
            [
                'type' => 'equipment',
                'severity' => 'low',
                'message' => 'Preventive maintenance due for 2 devices',
                'action_required' => 'Schedule maintenance within 14 days'
            ]
        ];
    }

    /**
     * Helper: Get key recommendations
     */
    private function getKeyRecommendations(): array
    {
        return [
            [
                'title' => 'Optimize Tuesday Scheduling',
                'priority' => 'high',
                'impact' => 'Reduce wait times by 15%',
                'effort' => 'low'
            ],
            [
                'title' => 'Implement Demand-Based Pricing',
                'priority' => 'medium',
                'impact' => 'Increase revenue by 8%',
                'effort' => 'medium'
            ],
            [
                'title' => 'Proactive Health Screening',
                'priority' => 'medium',
                'impact' => 'Early disease detection',
                'effort' => 'medium'
            ]
        ];
    }

    /**
     * Helper: Get health alerts
     */
    private function getHealthAlerts(): array
    {
        return [
            [
                'type' => 'trend_alert',
                'severity' => 'medium',
                'condition' => 'Respiratory infections',
                'change' => '+25% from normal',
                'recommendation' => 'Consider additional health protocols'
            ]
        ];
    }

    /**
     * Helper: Generate predictive alerts based on filters
     */
    private function generatePredictiveAlerts(array $filters): array
    {
        $allAlerts = [
            [
                'id' => 1,
                'title' => 'Patient Volume Spike Expected',
                'severity' => 'high',
                'category' => 'patient_flow',
                'probability' => 0.78,
                'impact' => 'high',
                'timeline' => 'Next 3 days',
                'description' => 'ML model predicts 40% increase in patient visits',
                'recommendations' => [
                    'Schedule additional doctors',
                    'Extend operating hours',
                    'Prepare extra medical supplies'
                ],
                'created_at' => now()->toISOString()
            ],
            [
                'id' => 2,
                'title' => 'Revenue Decline Risk',
                'severity' => 'medium',
                'category' => 'revenue',
                'probability' => 0.65,
                'impact' => 'medium',
                'timeline' => 'Next month',
                'description' => 'Seasonal patterns suggest potential revenue reduction',
                'recommendations' => [
                    'Implement promotional campaigns',
                    'Focus on high-value services',
                    'Review pricing strategy'
                ],
                'created_at' => now()->subHours(2)->toISOString()
            ],
            [
                'id' => 3,
                'title' => 'Equipment Maintenance Alert',
                'severity' => 'low',
                'category' => 'resources',
                'probability' => 0.92,
                'impact' => 'low',
                'timeline' => 'Next 2 weeks',
                'description' => 'Predictive maintenance required for diagnostic equipment',
                'recommendations' => [
                    'Schedule maintenance appointments',
                    'Prepare backup equipment',
                    'Notify affected departments'
                ],
                'created_at' => now()->subHours(6)->toISOString()
            ]
        ];

        // Apply filters
        if (isset($filters['severity'])) {
            $allAlerts = array_filter($allAlerts, function($alert) use ($filters) {
                return $alert['severity'] === $filters['severity'];
            });
        }

        if (isset($filters['category'])) {
            $allAlerts = array_filter($allAlerts, function($alert) use ($filters) {
                return $alert['category'] === $filters['category'];
            });
        }

        return array_values($allAlerts);
    }
}