<?php

namespace App\Filament\Manajer\Widgets;

use App\Services\MLAnalyticsService;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Cache;

class MLInsightsDashboard extends Widget
{
    protected static string $view = 'filament.manajer.widgets.ml-insights-dashboard';
    
    protected static ?int $sort = 2;
    
    protected int | string | array $columnSpan = 'full';
    
    protected MLAnalyticsService $mlService;

    public function __construct()
    {
        $this->mlService = app(MLAnalyticsService::class);
    }

    public function getViewData(): array
    {
        return Cache::remember('ml_insights_widget_data', 900, function () {
            return [
                'insights' => $this->mlService->getMlInsightsDashboard(),
                'quickSummary' => $this->getQuickSummary(),
                'alerts' => $this->getPredictiveAlerts(),
                'recommendations' => $this->getTopRecommendations()
            ];
        });
    }

    private function getQuickSummary(): array
    {
        return [
            'patient_flow' => [
                'trend' => 'increasing',
                'change_percentage' => 8.5,
                'next_peak' => now()->addDays(3)->format('M d'),
                'confidence' => 85
            ],
            'revenue_forecast' => [
                'direction' => 'positive',
                'growth_rate' => 12.3,
                'next_month' => 'Rp 85M',
                'confidence' => 78
            ],
            'health_trends' => [
                'emerging_patterns' => 2,
                'active_alerts' => 1,
                'risk_level' => 'medium'
            ],
            'resource_efficiency' => [
                'utilization_score' => 78,
                'optimization_potential' => 15,
                'maintenance_due' => 3
            ]
        ];
    }

    private function getPredictiveAlerts(): array
    {
        return [
            [
                'type' => 'patient_surge',
                'severity' => 'high',
                'title' => 'Patient Volume Spike Expected',
                'message' => '40% increase predicted in next 3 days',
                'probability' => 78,
                'actions' => ['Schedule extra staff', 'Prepare supplies']
            ],
            [
                'type' => 'revenue_risk',
                'severity' => 'medium',
                'title' => 'Seasonal Revenue Decline',
                'message' => 'Monthly revenue may drop by 12%',
                'probability' => 65,
                'actions' => ['Implement promotions', 'Focus on high-value services']
            ],
            [
                'type' => 'health_trend',
                'severity' => 'medium',
                'title' => 'Respiratory Infections Rising',
                'message' => '25% above normal levels',
                'probability' => 82,
                'actions' => ['Review protocols', 'Stock medications']
            ]
        ];
    }

    private function getTopRecommendations(): array
    {
        return [
            [
                'title' => 'Optimize Tuesday Operations',
                'impact' => 'High',
                'effort' => 'Low',
                'roi' => '15% efficiency gain',
                'category' => 'operations'
            ],
            [
                'title' => 'Implement Dynamic Pricing',
                'impact' => 'Medium',
                'effort' => 'Medium',
                'roi' => '8% revenue increase',
                'category' => 'revenue'
            ],
            [
                'title' => 'Proactive Health Screening',
                'impact' => 'High',
                'effort' => 'Medium',
                'roi' => 'Early disease detection',
                'category' => 'health'
            ],
            [
                'title' => 'Equipment Maintenance Optimization',
                'impact' => 'Medium',
                'effort' => 'Low',
                'roi' => '20% downtime reduction',
                'category' => 'resources'
            ]
        ];
    }

    public static function canView(): bool
    {
        return auth()->user()?->hasRole(['admin', 'manajer']) ?? false;
    }
}