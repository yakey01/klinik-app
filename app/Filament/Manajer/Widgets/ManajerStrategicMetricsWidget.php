<?php

namespace App\Filament\Manajer\Widgets;

use Filament\Widgets\Widget;
use App\Models\Pendapatan;
use App\Models\Pasien;
use App\Models\Tindakan;
use Carbon\Carbon;

class ManajerStrategicMetricsWidget extends Widget
{
    protected static string $view = 'filament.manajer.widgets.strategic-metrics-widget';
    
    protected int | string | array $columnSpan = 'full';
    
    protected static ?int $sort = 5;

    public function getViewData(): array
    {
        return [
            'strategic_goals' => $this->getStrategicGoals(),
            'growth_metrics' => $this->getGrowthMetrics(),
            'market_position' => $this->getMarketPosition(),
            'future_projections' => $this->getFutureProjections(),
        ];
    }

    private function getStrategicGoals(): array
    {
        $currentMonth = Carbon::now();
        $currentYear = Carbon::now()->year;
        
        // Strategic goals and targets
        $goals = [
            'revenue' => [
                'target' => 50000000, // 50 million per month
                'actual' => Pendapatan::whereMonth('created_at', $currentMonth->month)
                    ->whereYear('created_at', $currentMonth->year)
                    ->sum('jumlah'),
                'label' => 'Monthly Revenue Target',
                'unit' => 'Rp',
            ],
            'patients' => [
                'target' => 1000, // 1000 patients per month
                'actual' => Pasien::whereMonth('created_at', $currentMonth->month)
                    ->whereYear('created_at', $currentMonth->year)
                    ->count(),
                'label' => 'Monthly Patient Target',
                'unit' => '',
            ],
            'procedures' => [
                'target' => 1500, // 1500 procedures per month
                'actual' => Tindakan::whereMonth('created_at', $currentMonth->month)
                    ->whereYear('created_at', $currentMonth->year)
                    ->count(),
                'label' => 'Monthly Procedures Target',
                'unit' => '',
            ],
            'efficiency' => [
                'target' => 85, // 85% efficiency target
                'actual' => $this->calculateOverallEfficiency(),
                'label' => 'Operational Efficiency',
                'unit' => '%',
            ],
        ];
        
        // Calculate progress for each goal
        foreach ($goals as $key => &$goal) {
            $goal['progress'] = $goal['target'] > 0 ? ($goal['actual'] / $goal['target']) * 100 : 0;
            $goal['status'] = $goal['progress'] >= 100 ? 'achieved' : 
                ($goal['progress'] >= 80 ? 'on-track' : 'needs-attention');
        }
        
        return $goals;
    }

    private function getGrowthMetrics(): array
    {
        $currentMonth = Carbon::now();
        $previousMonth = Carbon::now()->subMonth();
        $currentYear = Carbon::now()->year;
        $previousYear = Carbon::now()->subYear()->year;
        
        // Month-over-month growth
        $currentMonthRevenue = Pendapatan::whereMonth('created_at', $currentMonth->month)
            ->whereYear('created_at', $currentMonth->year)
            ->sum('jumlah');
            
        $previousMonthRevenue = Pendapatan::whereMonth('created_at', $previousMonth->month)
            ->whereYear('created_at', $previousMonth->year)
            ->sum('jumlah');
            
        $monthlyGrowth = $previousMonthRevenue > 0 ? 
            (($currentMonthRevenue - $previousMonthRevenue) / $previousMonthRevenue) * 100 : 0;
        
        // Year-over-year growth
        $currentYearRevenue = Pendapatan::whereYear('created_at', $currentYear)->sum('jumlah');
        $previousYearRevenue = Pendapatan::whereYear('created_at', $previousYear)->sum('jumlah');
        
        $yearlyGrowth = $previousYearRevenue > 0 ? 
            (($currentYearRevenue - $previousYearRevenue) / $previousYearRevenue) * 100 : 0;
        
        // Patient growth
        $currentMonthPatients = Pasien::whereMonth('created_at', $currentMonth->month)
            ->whereYear('created_at', $currentMonth->year)
            ->count();
            
        $previousMonthPatients = Pasien::whereMonth('created_at', $previousMonth->month)
            ->whereYear('created_at', $previousMonth->year)
            ->count();
            
        $patientGrowth = $previousMonthPatients > 0 ? 
            (($currentMonthPatients - $previousMonthPatients) / $previousMonthPatients) * 100 : 0;
        
        return [
            'monthly_revenue_growth' => round($monthlyGrowth, 1),
            'yearly_revenue_growth' => round($yearlyGrowth, 1),
            'patient_growth' => round($patientGrowth, 1),
            'growth_trajectory' => $this->getGrowthTrajectory(),
        ];
    }

    private function getGrowthTrajectory(): array
    {
        $trajectory = [];
        
        for ($i = 11; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $monthlyRevenue = Pendapatan::whereMonth('created_at', $date->month)
                ->whereYear('created_at', $date->year)
                ->sum('jumlah');
                
            $trajectory[] = [
                'month' => $date->format('M Y'),
                'revenue' => $monthlyRevenue,
                'patients' => Pasien::whereMonth('created_at', $date->month)
                    ->whereYear('created_at', $date->year)
                    ->count(),
            ];
        }
        
        return $trajectory;
    }

    private function getMarketPosition(): array
    {
        $currentMonth = Carbon::now();
        
        // Market metrics (placeholder values - replace with actual market data)
        return [
            'market_share' => 15.5, // Percentage
            'competitive_position' => 'strong',
            'customer_satisfaction' => 88.5,
            'brand_recognition' => 72.0,
            'service_quality_rating' => 4.3, // Out of 5
            'repeat_patient_rate' => 65.0, // Percentage
        ];
    }

    private function getFutureProjections(): array
    {
        $currentTrend = $this->getGrowthMetrics();
        
        // Simple linear projection based on current trends
        $projectedRevenue = [];
        $currentRevenue = Pendapatan::whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->sum('jumlah');
            
        for ($i = 1; $i <= 6; $i++) {
            $projectedMonth = Carbon::now()->addMonths($i);
            $growthFactor = 1 + ($currentTrend['monthly_revenue_growth'] / 100);
            $projectedValue = $currentRevenue * pow($growthFactor, $i);
            
            $projectedRevenue[] = [
                'month' => $projectedMonth->format('M Y'),
                'projected_revenue' => $projectedValue,
            ];
        }
        
        return [
            'revenue_projections' => $projectedRevenue,
            'confidence_level' => 75, // Percentage
            'key_assumptions' => [
                'Current growth trend continues',
                'No major market disruptions',
                'Stable operational capacity',
                'Seasonal variations accounted for',
            ],
        ];
    }

    private function calculateOverallEfficiency(): float
    {
        $currentMonth = Carbon::now();
        
        // Simple efficiency calculation based on procedures vs capacity
        $totalProcedures = Tindakan::whereMonth('created_at', $currentMonth->month)
            ->whereYear('created_at', $currentMonth->year)
            ->count();
            
        $workingDays = $currentMonth->diffInWeekdays(Carbon::now()->startOfMonth());
        $totalStaff = \App\Models\Pegawai::where('aktif', true)->count() + 
                     \App\Models\Dokter::where('aktif', true)->count();
        
        $theoreticalCapacity = $totalStaff * $workingDays * 8; // 8 procedures per staff per day
        
        return $theoreticalCapacity > 0 ? ($totalProcedures / $theoreticalCapacity) * 100 : 0;
    }

    public static function canView(): bool
    {
        return auth()->user()?->hasRole('manajer') ?? false;
    }
}