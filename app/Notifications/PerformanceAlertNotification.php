<?php

namespace App\Notifications;

use App\Models\Pegawai;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PerformanceAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public Pegawai $staff;
    public array $performanceData;
    public string $severity;
    public ?string $customMessage;

    public function __construct(Pegawai $staff, array $performanceData, string $severity, ?string $customMessage = null)
    {
        $this->staff = $staff;
        $this->performanceData = $performanceData;
        $this->severity = $severity;
        $this->customMessage = $customMessage;
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $subject = $this->getSubjectBySeverity();
        $greeting = $this->getGreetingBySeverity();
        
        $message = (new MailMessage)
            ->subject($subject)
            ->greeting($greeting)
            ->line($this->getMainMessage())
            ->line('**Current Performance Summary:**')
            ->line("• Procedures this month: {$this->performanceData['current_procedures']}")
            ->line("• Department average: " . number_format($this->performanceData['department_average'], 1))
            ->line("• Efficiency rate: {$this->performanceData['efficiency_rate']}%")
            ->line("• Performance change: {$this->performanceData['performance_change']}%");

        if ($this->customMessage) {
            $message->line('**Manager\'s Note:**')
                   ->line($this->customMessage);
        }

        $message->line($this->getActionMessage())
               ->action('View Performance Details', 
                   route('filament.manajer.resources.strategic-plannings.performance-details', $this->staff))
               ->line('If you have any questions, please contact your manager or HR department.');

        return $message;
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => $this->getSubjectBySeverity(),
            'message' => $this->getMainMessage(),
            'staff_id' => $this->staff->id,
            'staff_name' => $this->staff->nama_lengkap,
            'severity' => $this->severity,
            'performance_data' => $this->performanceData,
            'custom_message' => $this->customMessage,
            'action_url' => route('filament.manajer.resources.strategic-plannings.performance-details', $this->staff),
        ];
    }

    private function getSubjectBySeverity(): string
    {
        return match ($this->severity) {
            'critical' => '🚨 Urgent: Performance Review Required',
            'high' => '⚠️ Performance Attention Needed',
            'medium' => '📊 Performance Check-in',
            'low' => '💡 Performance Improvement Opportunity',
            default => 'Performance Update',
        };
    }

    private function getGreetingBySeverity(): string
    {
        return match ($this->severity) {
            'critical' => 'Urgent Notice',
            'high' => 'Important Notice',
            'medium' => 'Performance Update',
            'low' => 'Hello',
            default => 'Hello',
        };
    }

    private function getMainMessage(): string
    {
        return match ($this->severity) {
            'critical' => 'Your current performance metrics require immediate attention. Our records show a significant decline in your procedure count this month.',
            'high' => 'We\'ve noticed your performance metrics are below the department average. Let\'s work together to improve your numbers.',
            'medium' => 'Your performance is slightly below average this month. Here\'s some information to help you track your progress.',
            'low' => 'We have some suggestions to help optimize your performance and reach your full potential.',
            default => 'Here\'s an update on your current performance metrics.',
        };
    }

    private function getActionMessage(): string
    {
        return match ($this->severity) {
            'critical' => 'Please schedule a meeting with your manager as soon as possible to discuss an improvement plan.',
            'high' => 'We recommend scheduling a meeting with your manager to discuss strategies for improvement.',
            'medium' => 'Consider reviewing your performance details and identifying areas for improvement.',
            'low' => 'Review your performance details to explore opportunities for growth.',
            default => 'Click below to view your detailed performance metrics.',
        };
    }
}