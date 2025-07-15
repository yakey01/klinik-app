<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class Notification extends Model
{
    protected $table = 'user_notifications';
    
    protected $fillable = [
        'user_id',
        'type',
        'title',
        'message',
        'data',
        'priority',
        'channel',
        'is_read',
        'read_at',
        'scheduled_for',
        'is_sent',
        'sent_at',
        'expires_at',
    ];

    protected $casts = [
        'data' => 'array',
        'is_read' => 'boolean',
        'is_sent' => 'boolean',
        'read_at' => 'datetime',
        'scheduled_for' => 'datetime',
        'sent_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * Relationship with User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for unread notifications
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * Scope for read notifications
     */
    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }

    /**
     * Scope for specific type
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope for specific priority
     */
    public function scopeWithPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Scope for non-expired notifications
     */
    public function scopeNotExpired($query)
    {
        return $query->where(function($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }

    /**
     * Scope for scheduled notifications ready to send
     */
    public function scopeReadyToSend($query)
    {
        return $query->where('is_sent', false)
                    ->where(function($q) {
                        $q->whereNull('scheduled_for')
                          ->orWhere('scheduled_for', '<=', now());
                    })
                    ->notExpired();
    }

    /**
     * Mark notification as read
     */
    public function markAsRead()
    {
        $this->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    /**
     * Mark notification as sent
     */
    public function markAsSent()
    {
        $this->update([
            'is_sent' => true,
            'sent_at' => now(),
        ]);
    }

    /**
     * Check if notification is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check if notification is ready to send
     */
    public function isReadyToSend(): bool
    {
        return !$this->is_sent && 
               (!$this->scheduled_for || $this->scheduled_for->isPast()) && 
               !$this->isExpired();
    }

    /**
     * Get priority color
     */
    public function getPriorityColorAttribute(): string
    {
        return match($this->priority) {
            'urgent' => '#dc2626',
            'high' => '#ea580c',
            'medium' => '#059669',
            'low' => '#4f46e5',
            default => '#6b7280',
        };
    }

    /**
     * Get type icon
     */
    public function getTypeIconAttribute(): string
    {
        return match($this->type) {
            'reminder' => '🔔',
            'alert' => '⚠️',
            'info' => 'ℹ️',
            'warning' => '⚠️',
            'success' => '✅',
            'error' => '❌',
            default => '📢',
        };
    }

    /**
     * Create attendance reminder notification
     */
    public static function createAttendanceReminder(User $user, $scheduledFor = null)
    {
        return self::create([
            'user_id' => $user->id,
            'type' => 'reminder',
            'title' => 'Attendance Reminder',
            'message' => 'Don\'t forget to check in for your shift today!',
            'priority' => 'medium',
            'channel' => 'in_app',
            'scheduled_for' => $scheduledFor,
            'data' => [
                'action' => 'check_in',
                'reminder_type' => 'attendance',
            ],
        ]);
    }

    /**
     * Create late check-in alert
     */
    public static function createLateCheckInAlert(User $user, $minutesLate)
    {
        return self::create([
            'user_id' => $user->id,
            'type' => 'alert',
            'title' => 'Late Check-in Alert',
            'message' => "You are {$minutesLate} minutes late for your shift.",
            'priority' => 'high',
            'channel' => 'in_app',
            'data' => [
                'action' => 'late_checkin',
                'minutes_late' => $minutesLate,
            ],
        ]);
    }

    /**
     * Create schedule update notification
     */
    public static function createScheduleUpdate(User $user, $scheduleData)
    {
        return self::create([
            'user_id' => $user->id,
            'type' => 'info',
            'title' => 'Schedule Updated',
            'message' => 'Your work schedule has been updated. Please check the new schedule.',
            'priority' => 'medium',
            'channel' => 'in_app',
            'data' => [
                'action' => 'view_schedule',
                'schedule_data' => $scheduleData,
            ],
        ]);
    }

    /**
     * Create approval status notification
     */
    public static function createApprovalNotification(User $user, $attendanceDate, $status, $notes = null)
    {
        $message = match($status) {
            'approved' => "Your attendance for {$attendanceDate} has been approved.",
            'rejected' => "Your attendance for {$attendanceDate} has been rejected.",
            default => "Your attendance for {$attendanceDate} status has been updated.",
        };

        if ($notes) {
            $message .= " Note: {$notes}";
        }

        return self::create([
            'user_id' => $user->id,
            'type' => $status === 'approved' ? 'success' : 'warning',
            'title' => 'Attendance Status Update',
            'message' => $message,
            'priority' => 'medium',
            'channel' => 'in_app',
            'data' => [
                'action' => 'view_attendance',
                'attendance_date' => $attendanceDate,
                'status' => $status,
                'notes' => $notes,
            ],
        ]);
    }
}
