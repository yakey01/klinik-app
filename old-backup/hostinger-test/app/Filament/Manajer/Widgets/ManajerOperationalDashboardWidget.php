<?php

namespace App\Filament\Manajer\Widgets;

use Filament\Widgets\Widget;
use App\Models\Pasien;
use App\Models\Tindakan;
use App\Models\Pegawai;
use App\Models\Dokter;
use Carbon\Carbon;

class ManajerOperationalDashboardWidget extends Widget
{
    protected static string $view = 'filament.manajer.widgets.operational-dashboard-widget';
    
    protected int | string | array $columnSpan = 'full';
    
    protected static ?int $sort = 4;

    public function getViewData(): array
    {
        return [
            'capacity_metrics' => $this->getCapacityMetrics(),
            'quality_metrics' => $this->getQualityMetrics(),
            'operational_alerts' => $this->getOperationalAlerts(),
            'daily_operations' => $this->getDailyOperations(),
        ];
    }

    private function getCapacityMetrics(): array
    {
        $today = Carbon::today();
        $currentMonth = Carbon::now();
        
        // Calculate capacity utilization
        $totalStaff = Pegawai::where('aktif', true)->count() + Dokter::where('aktif', true)->count();
        $maxDailyCapacity = $totalStaff * 8; // 8 hours per staff
        
        $todayProcedures = Tindakan::whereDate('created_at', $today)->count();
        $avgProcedureTime = 0.5; // 30 minutes per procedure
        $todayCapacityUsed = $todayProcedures * $avgProcedureTime;
        
        $capacityUtilization = $maxDailyCapacity > 0 ? ($todayCapacityUsed / $maxDailyCapacity) * 100 : 0;
        
        // Monthly capacity trend
        $monthlyCapacity = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $dailyProcedures = Tindakan::whereDate('created_at', $date)->count();
            $dailyCapacityUsed = $dailyProcedures * $avgProcedureTime;
            $dailyUtilization = $maxDailyCapacity > 0 ? ($dailyCapacityUsed / $maxDailyCapacity) * 100 : 0;
            
            $monthlyCapacity[] = [
                'date' => $date->format('Y-m-d'),
                'utilization' => round($dailyUtilization, 1),
                'procedures' => $dailyProcedures,
            ];
        }
        
        return [
            'total_capacity' => $maxDailyCapacity,
            'used_capacity' => $todayCapacityUsed,
            'utilization_percentage' => round($capacityUtilization, 1),
            'monthly_trend' => $monthlyCapacity,
            'status' => $capacityUtilization > 90 ? 'critical' : ($capacityUtilization > 70 ? 'warning' : 'good'),
        ];
    }

    private function getQualityMetrics(): array
    {
        $currentMonth = Carbon::now();
        
        // Quality metrics based on validation status
        $totalProcedures = Tindakan::whereMonth('created_at', $currentMonth->month)
            ->whereYear('created_at', $currentMonth->year)
            ->count();
            
        $validatedProcedures = Tindakan::whereMonth('created_at', $currentMonth->month)
            ->whereYear('created_at', $currentMonth->year)
            ->where('validation_status', 'validated')
            ->count();
            
        $rejectedProcedures = Tindakan::whereMonth('created_at', $currentMonth->month)
            ->whereYear('created_at', $currentMonth->year)
            ->where('validation_status', 'rejected')
            ->count();
            
        $qualityScore = $totalProcedures > 0 ? ($validatedProcedures / $totalProcedures) * 100 : 0;
        $rejectionRate = $totalProcedures > 0 ? ($rejectedProcedures / $totalProcedures) * 100 : 0;
        
        // Patient satisfaction (placeholder - implement based on feedback system)
        $patientSatisfaction = 87.5; // Placeholder
        
        return [
            'total_procedures' => $totalProcedures,
            'validated_procedures' => $validatedProcedures,
            'rejected_procedures' => $rejectedProcedures,
            'quality_score' => round($qualityScore, 1),
            'rejection_rate' => round($rejectionRate, 1),
            'patient_satisfaction' => $patientSatisfaction,
            'quality_trend' => $this->getQualityTrend(),
        ];
    }

    private function getQualityTrend(): array
    {
        $trend = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $dailyTotal = Tindakan::whereDate('created_at', $date)->count();
            $dailyValidated = Tindakan::whereDate('created_at', $date)
                ->where('validation_status', 'validated')
                ->count();
                
            $dailyQuality = $dailyTotal > 0 ? ($dailyValidated / $dailyTotal) * 100 : 0;
            
            $trend[] = [
                'date' => $date->format('Y-m-d'),
                'quality_score' => round($dailyQuality, 1),
            ];
        }
        
        return $trend;
    }

    private function getOperationalAlerts(): array
    {
        $alerts = [];
        
        // High capacity utilization alert
        $capacityMetrics = $this->getCapacityMetrics();
        if ($capacityMetrics['utilization_percentage'] > 90) {
            $alerts[] = [
                'type' => 'critical',
                'title' => 'High Capacity Utilization',
                'message' => 'Current utilization is at ' . $capacityMetrics['utilization_percentage'] . '%',
                'action' => 'Consider scheduling adjustments',
                'priority' => 'high',
            ];
        }
        
        // Low quality score alert
        $qualityMetrics = $this->getQualityMetrics();
        if ($qualityMetrics['quality_score'] < 80) {
            $alerts[] = [
                'type' => 'warning',
                'title' => 'Quality Score Below Target',
                'message' => 'Current quality score is ' . $qualityMetrics['quality_score'] . '%',
                'action' => 'Review procedure validation process',
                'priority' => 'medium',
            ];
        }
        
        // Pending procedures alert
        $pendingProcedures = Tindakan::where('validation_status', 'pending')->count();
        if ($pendingProcedures > 10) {
            $alerts[] = [
                'type' => 'warning',
                'title' => 'High Pending Procedures',
                'message' => $pendingProcedures . ' procedures awaiting validation',
                'action' => 'Expedite validation process',
                'priority' => 'medium',
            ];
        }
        
        return $alerts;
    }

    private function getDailyOperations(): array
    {
        $today = Carbon::today();
        
        return [
            'patients_today' => Pasien::whereDate('created_at', $today)->count(),
            'procedures_today' => Tindakan::whereDate('created_at', $today)->count(),
            'staff_on_duty' => Pegawai::where('aktif', true)->count() + Dokter::where('aktif', true)->count(),
            'pending_validations' => Tindakan::where('validation_status', 'pending')->count(),
            'completed_procedures' => Tindakan::whereDate('created_at', $today)
                ->where('validation_status', 'validated')
                ->count(),
            'average_procedure_time' => 30, // minutes (placeholder)
        ];
    }

    public static function canView(): bool
    {
        return auth()->user()?->hasRole('manajer') ?? false;
    }
}