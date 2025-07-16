<?php

namespace App\Notifications;

use App\Models\Pegawai;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PerformanceEscalationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public Pegawai $staff;
    public array $performanceData;
    public string $severity;

    public function __construct(Pegawai $staff, array $performanceData, string $severity)
    {
        $this->staff = $staff;
        $this->performanceData = $performanceData;
        $this->severity = $severity;
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('🚨 Critical Performance Alert - Escalation Required')
            ->greeting('Manager Alert')
            ->line("A critical performance issue has been identified for staff member: **{$this->staff->nama_lengkap}**")
            ->line('**Performance Summary:**')
            ->line("• Current procedures: {$this->performanceData['current_procedures']}")
            ->line("• Department average: " . number_format($this->performanceData['department_average'], 1))
            ->line("• Performance change: {$this->performanceData['performance_change']}%")
            ->line("• Employee type: {$this->staff->jenis_pegawai}")
            ->line('**Escalation Reason:**')
            ->line($this->getEscalationReason())
            ->line('**Recommended Actions:**')
            ->line('• Schedule immediate 1-on-1 meeting')
            ->line('• Review workload and training needs')
            ->line('• Create performance improvement plan')
            ->line('• Consider additional support or resources')
            ->action('View Full Performance Details', 
                route('filament.manajer.resources.strategic-plannings.performance-details', $this->staff))
            ->line('This alert was automatically generated based on performance thresholds. Please take appropriate action within 24 hours.');
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => 'Critical Performance Alert',
            'message' => "Staff member {$this->staff->nama_lengkap} requires immediate attention",
            'staff_id' => $this->staff->id,
            'staff_name' => $this->staff->nama_lengkap,
            'staff_type' => $this->staff->jenis_pegawai,
            'severity' => $this->severity,
            'performance_data' => $this->performanceData,
            'escalation_reason' => $this->getEscalationReason(),
            'action_url' => route('filament.manajer.resources.strategic-plannings.performance-details', $this->staff),
            'requires_action' => true,
        ];
    }

    private function getEscalationReason(): string
    {
        $current = $this->performanceData['current_procedures'];
        $trend = $this->performanceData['trend'];
        
        if ($current === 0) {
            return 'Zero procedures completed this month - requires immediate intervention.';
        }
        
        if ($this->isDeclineTrend($trend, 3)) {
            return 'Performance has been declining for 3 consecutive months.';
        }
        
        $average = $this->performanceData['department_average'];
        if ($current < ($average * 0.5)) {
            return 'Performance is significantly below department average (less than 50%).';
        }
        
        return 'Critical performance threshold exceeded.';
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
}