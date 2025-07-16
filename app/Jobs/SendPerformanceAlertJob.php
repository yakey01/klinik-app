<?php

namespace App\Jobs;

use App\Models\Pegawai;
use App\Models\Tindakan;
use App\Models\User;
use App\Notifications\PerformanceAlertNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class SendPerformanceAlertJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Pegawai $staff;
    public ?string $alertType;
    public ?string $customMessage;

    public function __construct(Pegawai $staff, ?string $alertType = 'low_performance', ?string $customMessage = null)
    {
        $this->staff = $staff;
        $this->alertType = $alertType;
        $this->customMessage = $customMessage;
    }

    public function handle(): void
    {
        try {
            // Get performance data
            $performanceData = $this->getPerformanceData();
            
            // Determine alert severity
            $severity = $this->determineAlertSeverity($performanceData);
            
            // Send notification to staff member
            $this->sendStaffNotification($performanceData, $severity);
            
            // Send escalation to manager if needed
            if ($severity === 'critical') {
                $this->sendManagerEscalation($performanceData);
            }
            
            // Log the alert
            $this->logAlert($performanceData, $severity);
            
        } catch (\Exception $e) {
            Log::error('Performance alert job failed', [
                'staff_id' => $this->staff->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }

    private function getPerformanceData(): array
    {
        $currentMonth = now()->month;
        $currentYear = now()->year;
        $previousMonth = now()->subMonth()->month;
        $previousYear = now()->subMonth()->year;

        // Current month performance
        $currentProcedures = Tindakan::where(function($query) {
            $query->where('paramedis_id', $this->staff->id)
                  ->orWhere('non_paramedis_id', $this->staff->id);
        })
        ->whereMonth('created_at', $currentMonth)
        ->whereYear('created_at', $currentYear)
        ->count();

        // Previous month performance
        $previousProcedures = Tindakan::where(function($query) {
            $query->where('paramedis_id', $this->staff->id)
                  ->orWhere('non_paramedis_id', $this->staff->id);
        })
        ->whereMonth('created_at', $previousMonth)
        ->whereYear('created_at', $previousYear)
        ->count();

        // Department average
        $departmentAverage = \Illuminate\Support\Facades\DB::table('pegawais')
            ->selectRaw('AVG(
                (SELECT COUNT(*) FROM tindakan 
                 WHERE strftime("%m", tindakan.created_at) = ? 
                 AND (tindakan.paramedis_id = pegawais.id OR tindakan.non_paramedis_id = pegawais.id) 
                 AND tindakan.deleted_at IS NULL)
            ) as avg_procedures', [str_pad($currentMonth, 2, '0', STR_PAD_LEFT)])
            ->where('jenis_pegawai', $this->staff->jenis_pegawai)
            ->where('aktif', true)
            ->first();

        // Performance trend (last 6 months)
        $trend = collect(range(5, 0))->map(function ($monthsAgo) {
            $date = now()->subMonths($monthsAgo);
            return Tindakan::where(function($query) {
                $query->where('paramedis_id', $this->staff->id)
                      ->orWhere('non_paramedis_id', $this->staff->id);
            })
            ->whereMonth('created_at', $date->month)
            ->whereYear('created_at', $date->year)
            ->count();
        });

        return [
            'current_procedures' => $currentProcedures,
            'previous_procedures' => $previousProcedures,
            'department_average' => $departmentAverage->avg_procedures ?? 0,
            'trend' => $trend->toArray(),
            'performance_change' => $previousProcedures > 0 ? 
                round((($currentProcedures - $previousProcedures) / $previousProcedures) * 100, 1) : 0,
            'efficiency_rate' => min(100, ($currentProcedures / 30) * 100),
        ];
    }

    private function determineAlertSeverity(array $performanceData): string
    {
        $current = $performanceData['current_procedures'];
        $average = $performanceData['department_average'];
        $trend = $performanceData['trend'];
        
        // Critical: 0 procedures this month or declining trend for 3+ months
        if ($current === 0 || $this->isDeclineTrend($trend, 3)) {
            return 'critical';
        }
        
        // High: Below 50% of department average
        if ($current < ($average * 0.5)) {
            return 'high';
        }
        
        // Medium: Below department average but above 75%
        if ($current < ($average * 0.75)) {
            return 'medium';
        }
        
        // Low: General improvement suggestion
        return 'low';
    }

    private function isDeclineTrend(array $trend, int $months): bool
    {
        if (count($trend) < $months) {
            return false;
        }
        
        $recentTrend = array_slice($trend, -$months);
        
        for ($i = 1; $i < count($recentTrend); $i++) {
            if ($recentTrend[$i] > $recentTrend[$i - 1]) {
                return false;
            }
        }
        
        return true;
    }

    private function sendStaffNotification(array $performanceData, string $severity): void
    {
        // Get user accounts for this staff member
        $users = $this->staff->users()->where('status_akun', 'Aktif')->get();
        
        if ($users->isEmpty()) {
            Log::warning('No active user accounts found for staff member', [
                'staff_id' => $this->staff->id,
                'staff_name' => $this->staff->nama_lengkap
            ]);
            return;
        }

        $notification = new PerformanceAlertNotification(
            $this->staff,
            $performanceData,
            $severity,
            $this->customMessage
        );

        Notification::send($users, $notification);
    }

    private function sendManagerEscalation(array $performanceData): void
    {
        // Get all managers
        $managers = User::whereHas('role', function($query) {
            $query->where('name', 'manajer');
        })->get();

        if ($managers->isEmpty()) {
            Log::warning('No managers found for performance alert escalation');
            return;
        }

        $escalationNotification = new \App\Notifications\PerformanceEscalationNotification(
            $this->staff,
            $performanceData,
            'critical'
        );

        Notification::send($managers, $escalationNotification);
    }

    private function logAlert(array $performanceData, string $severity): void
    {
        Log::info('Performance alert sent', [
            'staff_id' => $this->staff->id,
            'staff_name' => $this->staff->nama_lengkap,
            'alert_type' => $this->alertType,
            'severity' => $severity,
            'performance_data' => $performanceData,
            'sent_at' => now()->toDateTimeString()
        ]);

        // Store alert in database for tracking
        \Illuminate\Support\Facades\DB::table('performance_alerts')->insert([
            'staff_id' => $this->staff->id,
            'alert_type' => $this->alertType,
            'severity' => $severity,
            'performance_data' => json_encode($performanceData),
            'custom_message' => $this->customMessage,
            'sent_by' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}