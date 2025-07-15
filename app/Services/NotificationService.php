<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use App\Models\NonParamedisAttendance;
use App\Models\Schedule;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Send notification to user
     */
    public function sendNotification(User $user, array $data): Notification
    {
        $notification = Notification::create(array_merge([
            'user_id' => $user->id,
            'channel' => 'in_app',
            'priority' => 'medium',
        ], $data));

        // Mark as sent immediately for in-app notifications
        if ($notification->channel === 'in_app') {
            $notification->markAsSent();
        }

        Log::info('Notification sent', [
            'user_id' => $user->id,
            'notification_id' => $notification->id,
            'type' => $notification->type,
            'title' => $notification->title,
        ]);

        return $notification;
    }

    /**
     * Send attendance reminder to user
     */
    public function sendAttendanceReminder(User $user, $scheduledFor = null): Notification
    {
        return $this->sendNotification($user, [
            'type' => 'reminder',
            'title' => 'Attendance Reminder',
            'message' => 'Don\'t forget to check in for your shift today!',
            'priority' => 'medium',
            'scheduled_for' => $scheduledFor,
            'data' => [
                'action' => 'check_in',
                'reminder_type' => 'attendance',
            ],
        ]);
    }

    /**
     * Send late check-in alert
     */
    public function sendLateCheckInAlert(User $user, int $minutesLate): Notification
    {
        return $this->sendNotification($user, [
            'type' => 'alert',
            'title' => 'Late Check-in Alert',
            'message' => "You are {$minutesLate} minutes late for your shift.",
            'priority' => 'high',
            'data' => [
                'action' => 'late_checkin',
                'minutes_late' => $minutesLate,
            ],
        ]);
    }

    /**
     * Send schedule update notification
     */
    public function sendScheduleUpdate(User $user, array $scheduleData): Notification
    {
        return $this->sendNotification($user, [
            'type' => 'info',
            'title' => 'Schedule Updated',
            'message' => 'Your work schedule has been updated. Please check the new schedule.',
            'priority' => 'medium',
            'data' => [
                'action' => 'view_schedule',
                'schedule_data' => $scheduleData,
            ],
        ]);
    }

    /**
     * Send approval status notification
     */
    public function sendApprovalNotification(User $user, string $attendanceDate, string $status, ?string $notes = null): Notification
    {
        $message = match($status) {
            'approved' => "Your attendance for {$attendanceDate} has been approved.",
            'rejected' => "Your attendance for {$attendanceDate} has been rejected.",
            default => "Your attendance for {$attendanceDate} status has been updated.",
        };

        if ($notes) {
            $message .= " Note: {$notes}";
        }

        return $this->sendNotification($user, [
            'type' => $status === 'approved' ? 'success' : 'warning',
            'title' => 'Attendance Status Update',
            'message' => $message,
            'priority' => 'medium',
            'data' => [
                'action' => 'view_attendance',
                'attendance_date' => $attendanceDate,
                'status' => $status,
                'notes' => $notes,
            ],
        ]);
    }

    /**
     * Send missing check-out reminder
     */
    public function sendMissingCheckoutReminder(User $user, NonParamedisAttendance $attendance): Notification
    {
        return $this->sendNotification($user, [
            'type' => 'reminder',
            'title' => 'Missing Check-out',
            'message' => 'You forgot to check out yesterday. Please contact admin to resolve this.',
            'priority' => 'high',
            'data' => [
                'action' => 'contact_admin',
                'attendance_id' => $attendance->id,
                'attendance_date' => $attendance->attendance_date->format('Y-m-d'),
            ],
        ]);
    }

    /**
     * Send bulk notifications
     */
    public function sendBulkNotifications(array $users, array $notificationData): array
    {
        $notifications = [];

        foreach ($users as $user) {
            try {
                $notifications[] = $this->sendNotification($user, $notificationData);
            } catch (\Exception $e) {
                Log::error('Failed to send bulk notification', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $notifications;
    }

    /**
     * Get user notifications
     */
    public function getUserNotifications(User $user, array $filters = []): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = Notification::where('user_id', $user->id)
            ->notExpired()
            ->orderBy('created_at', 'desc');

        // Apply filters
        if (isset($filters['type'])) {
            $query->ofType($filters['type']);
        }

        if (isset($filters['priority'])) {
            $query->withPriority($filters['priority']);
        }

        if (isset($filters['is_read'])) {
            if ($filters['is_read']) {
                $query->read();
            } else {
                $query->unread();
            }
        }

        return $query->paginate($filters['per_page'] ?? 20);
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(Notification $notification): bool
    {
        if ($notification->is_read) {
            return true;
        }

        $notification->markAsRead();
        
        Log::info('Notification marked as read', [
            'notification_id' => $notification->id,
            'user_id' => $notification->user_id,
        ]);

        return true;
    }

    /**
     * Mark all user notifications as read
     */
    public function markAllAsRead(User $user): int
    {
        $count = Notification::where('user_id', $user->id)
            ->unread()
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        Log::info('All notifications marked as read', [
            'user_id' => $user->id,
            'count' => $count,
        ]);

        return $count;
    }

    /**
     * Get unread notification count
     */
    public function getUnreadCount(User $user): int
    {
        return Notification::where('user_id', $user->id)
            ->unread()
            ->notExpired()
            ->count();
    }

    /**
     * Process scheduled notifications
     */
    public function processScheduledNotifications(): int
    {
        $notifications = Notification::readyToSend()
            ->with('user')
            ->get();

        $processedCount = 0;

        foreach ($notifications as $notification) {
            try {
                // For in-app notifications, just mark as sent
                if ($notification->channel === 'in_app') {
                    $notification->markAsSent();
                    $processedCount++;
                }
                
                // TODO: Add email, push, SMS sending logic here
                
            } catch (\Exception $e) {
                Log::error('Failed to process scheduled notification', [
                    'notification_id' => $notification->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Processed scheduled notifications', [
            'processed_count' => $processedCount,
            'total_found' => $notifications->count(),
        ]);

        return $processedCount;
    }

    /**
     * Clean up expired notifications
     */
    public function cleanupExpiredNotifications(): int
    {
        $expiredCount = Notification::where('expires_at', '<', now())
            ->delete();

        Log::info('Cleaned up expired notifications', [
            'deleted_count' => $expiredCount,
        ]);

        return $expiredCount;
    }

    /**
     * Schedule daily attendance reminders
     */
    public function scheduleAttendanceReminders(Carbon $date): int
    {
        $users = User::byRole('non_paramedis')
            ->active()
            ->whereHas('schedules', function($query) use ($date) {
                $query->where('date', $date->format('Y-m-d'))
                      ->whereNotNull('shift_id');
            })
            ->with(['schedules' => function($query) use ($date) {
                $query->where('date', $date->format('Y-m-d'))
                      ->with('shift');
            }])
            ->get();

        $scheduledCount = 0;

        foreach ($users as $user) {
            $schedule = $user->schedules->first();
            if ($schedule && $schedule->shift) {
                // Schedule reminder 30 minutes before shift start
                $reminderTime = $date->copy()
                    ->setTimeFromTimeString($schedule->shift->start_time)
                    ->subMinutes(30);

                if ($reminderTime->isFuture()) {
                    $this->sendAttendanceReminder($user, $reminderTime);
                    $scheduledCount++;
                }
            }
        }

        Log::info('Scheduled attendance reminders', [
            'date' => $date->format('Y-m-d'),
            'scheduled_count' => $scheduledCount,
        ]);

        return $scheduledCount;
    }

    /**
     * Check for missing check-outs and send reminders
     */
    public function checkMissingCheckouts(): int
    {
        $yesterday = Carbon::yesterday();
        
        $incompleteAttendances = NonParamedisAttendance::where('attendance_date', $yesterday->format('Y-m-d'))
            ->whereNotNull('check_in_time')
            ->whereNull('check_out_time')
            ->with('user')
            ->get();

        $reminderCount = 0;

        foreach ($incompleteAttendances as $attendance) {
            if ($attendance->user) {
                $this->sendMissingCheckoutReminder($attendance->user, $attendance);
                $reminderCount++;
            }
        }

        Log::info('Checked for missing check-outs', [
            'date' => $yesterday->format('Y-m-d'),
            'missing_count' => $incompleteAttendances->count(),
            'reminder_count' => $reminderCount,
        ]);

        return $reminderCount;
    }

    /**
     * Send late check-in alerts
     */
    public function checkLateCheckIns(): int
    {
        $today = Carbon::today();
        
        $schedules = Schedule::where('date', $today->format('Y-m-d'))
            ->whereNotNull('shift_id')
            ->with(['user', 'shift'])
            ->get();

        $alertCount = 0;

        foreach ($schedules as $schedule) {
            if (!$schedule->user || !$schedule->shift) {
                continue;
            }

            $shiftStart = $today->copy()->setTimeFromTimeString($schedule->shift->start_time);
            $lateThreshold = $shiftStart->copy()->addMinutes(15); // 15 minutes late threshold

            if (now()->gt($lateThreshold)) {
                $attendance = NonParamedisAttendance::where('user_id', $schedule->user->id)
                    ->where('attendance_date', $today->format('Y-m-d'))
                    ->first();

                if (!$attendance || !$attendance->check_in_time) {
                    $minutesLate = now()->diffInMinutes($shiftStart);
                    $this->sendLateCheckInAlert($schedule->user, $minutesLate);
                    $alertCount++;
                }
            }
        }

        Log::info('Checked for late check-ins', [
            'date' => $today->format('Y-m-d'),
            'alert_count' => $alertCount,
        ]);

        return $alertCount;
    }
}