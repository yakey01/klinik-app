<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Models\User;
use App\Models\AuditLog;
use App\Services\TelegramService;
use Carbon\Carbon;
use Exception;

class NotificationService
{
    protected TelegramService $telegramService;
    
    protected array $notificationTypes = [
        'validation_submitted' => 'Validasi Diajukan',
        'validation_approved' => 'Validasi Disetujui',
        'validation_rejected' => 'Validasi Ditolak',
        'validation_revision' => 'Revisi Diperlukan',
        'task_reminder' => 'Pengingat Tugas',
        'deadline_warning' => 'Peringatan Deadline',
        'system_alert' => 'Notifikasi Sistem',
        'bulk_operation' => 'Operasi Massal',
        'export_complete' => 'Export Selesai',
        'import_complete' => 'Import Selesai',
    ];

    protected array $priorityLevels = [
        'low' => 1,
        'medium' => 2,
        'high' => 3,
        'urgent' => 4,
        'critical' => 5,
    ];

    public function __construct(TelegramService $telegramService)
    {
        $this->telegramService = $telegramService;
    }

    /**
     * Send real-time notification
     */
    public function sendRealTimeNotification(
        int $userId,
        string $type,
        string $title,
        string $message,
        array $data = [],
        string $priority = 'medium'
    ): array {
        try {
            $user = User::find($userId);
            if (!$user) {
                throw new Exception('User not found');
            }

            // Prepare notification data
            $notificationData = [
                'id' => uniqid(),
                'user_id' => $userId,
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'data' => $data,
                'priority' => $priority,
                'priority_level' => $this->priorityLevels[$priority] ?? 2,
                'created_at' => now(),
                'read_at' => null,
                'expires_at' => now()->addDays(7),
            ];

            // Store in cache for real-time retrieval
            $this->storeNotificationInCache($notificationData);

            // Send via appropriate channels
            $channels = $this->determineNotificationChannels($user, $type, $priority);
            
            foreach ($channels as $channel) {
                $this->sendViaChannel($channel, $user, $notificationData);
            }

            // Log notification
            $this->logNotification($notificationData);

            return [
                'success' => true,
                'notification_id' => $notificationData['id'],
                'channels' => $channels,
                'message' => 'Notification sent successfully',
            ];

        } catch (Exception $e) {
            Log::error('Failed to send real-time notification', [
                'user_id' => $userId,
                'type' => $type,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get user notifications from cache
     */
    public function getUserNotifications(int $userId, int $limit = 50): array
    {
        try {
            $cacheKey = "user_notifications_{$userId}";
            $notifications = Cache::get($cacheKey, []);

            // Sort by priority and date
            usort($notifications, function ($a, $b) {
                if ($a['priority_level'] === $b['priority_level']) {
                    return $b['created_at'] <=> $a['created_at'];
                }
                return $b['priority_level'] <=> $a['priority_level'];
            });

            // Filter out expired notifications
            $notifications = array_filter($notifications, function ($notification) {
                return Carbon::parse($notification['expires_at'])->isFuture();
            });

            // Limit results
            $notifications = array_slice($notifications, 0, $limit);

            // Update cache
            Cache::put($cacheKey, $notifications, now()->addHours(24));

            return [
                'success' => true,
                'notifications' => $notifications,
                'total' => count($notifications),
                'unread' => count(array_filter($notifications, fn($n) => !$n['read_at'])),
            ];

        } catch (Exception $e) {
            Log::error('Failed to get user notifications', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(int $userId, string $notificationId): array
    {
        try {
            $cacheKey = "user_notifications_{$userId}";
            $notifications = Cache::get($cacheKey, []);

            $updated = false;
            foreach ($notifications as &$notification) {
                if ($notification['id'] === $notificationId) {
                    $notification['read_at'] = now()->toISOString();
                    $updated = true;
                    break;
                }
            }

            if ($updated) {
                Cache::put($cacheKey, $notifications, now()->addHours(24));
            }

            return [
                'success' => $updated,
                'message' => $updated ? 'Notification marked as read' : 'Notification not found',
            ];

        } catch (Exception $e) {
            Log::error('Failed to mark notification as read', [
                'user_id' => $userId,
                'notification_id' => $notificationId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Send task reminders
     */
    public function sendTaskReminders(): array
    {
        try {
            $results = [];

            // Get users who need reminders
            $users = User::whereHas('roles', function ($query) {
                $query->whereIn('name', ['petugas', 'supervisor', 'manager']);
            })->get();

            foreach ($users as $user) {
                $reminders = $this->getTaskRemindersForUser($user);
                
                foreach ($reminders as $reminder) {
                    $result = $this->sendRealTimeNotification(
                        $user->id,
                        'task_reminder',
                        $reminder['title'],
                        $reminder['message'],
                        $reminder['data'],
                        $reminder['priority']
                    );
                    
                    $results[] = $result;
                }
            }

            return [
                'success' => true,
                'reminders_sent' => count($results),
                'results' => $results,
            ];

        } catch (Exception $e) {
            Log::error('Failed to send task reminders', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get task reminders for specific user
     */
    protected function getTaskRemindersForUser(User $user): array
    {
        $reminders = [];

        // Check for pending validations
        $pendingValidations = $this->getPendingValidationsCount($user);
        if ($pendingValidations > 0) {
            $reminders[] = [
                'title' => '📋 Validasi Menunggu',
                'message' => "Anda memiliki {$pendingValidations} validasi yang menunggu persetujuan",
                'data' => ['count' => $pendingValidations, 'type' => 'validations'],
                'priority' => $pendingValidations > 5 ? 'high' : 'medium',
            ];
        }

        // Check for overdue submissions
        $overdueSubmissions = $this->getOverdueSubmissionsCount($user);
        if ($overdueSubmissions > 0) {
            $reminders[] = [
                'title' => '⚠️ Submission Terlambat',
                'message' => "Anda memiliki {$overdueSubmissions} submission yang terlambat",
                'data' => ['count' => $overdueSubmissions, 'type' => 'overdue'],
                'priority' => 'urgent',
            ];
        }

        // Daily target reminder
        if ($this->shouldSendDailyTargetReminder($user)) {
            $reminders[] = [
                'title' => '🎯 Target Harian',
                'message' => 'Jangan lupa untuk mencapai target harian Anda',
                'data' => ['type' => 'daily_target'],
                'priority' => 'low',
            ];
        }

        return $reminders;
    }

    /**
     * Store notification in cache
     */
    protected function storeNotificationInCache(array $notification): void
    {
        $cacheKey = "user_notifications_{$notification['user_id']}";
        $notifications = Cache::get($cacheKey, []);
        
        // Add new notification at the beginning
        array_unshift($notifications, $notification);
        
        // Keep only last 100 notifications
        $notifications = array_slice($notifications, 0, 100);
        
        Cache::put($cacheKey, $notifications, now()->addHours(24));
    }

    /**
     * Determine notification channels
     */
    protected function determineNotificationChannels(User $user, string $type, string $priority): array
    {
        $channels = ['cache']; // Always store in cache

        // Add Telegram for high priority notifications
        if (in_array($priority, ['high', 'urgent', 'critical'])) {
            $channels[] = 'telegram';
        }

        // Add Telegram for validation notifications
        if (str_contains($type, 'validation')) {
            $channels[] = 'telegram';
        }

        return $channels;
    }

    /**
     * Send via specific channel
     */
    protected function sendViaChannel(string $channel, User $user, array $notification): void
    {
        switch ($channel) {
            case 'telegram':
                $this->sendViaTelegram($user, $notification);
                break;
            case 'cache':
                // Already stored in cache
                break;
        }
    }

    /**
     * Send via Telegram
     */
    protected function sendViaTelegram(User $user, array $notification): void
    {
        try {
            $message = $this->formatTelegramMessage($notification);
            $this->telegramService->sendMessage($user->id, $message);
        } catch (Exception $e) {
            Log::error('Failed to send Telegram notification', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Format message for Telegram
     */
    protected function formatTelegramMessage(array $notification): string
    {
        $priority = match ($notification['priority']) {
            'low' => 'ℹ️',
            'medium' => '📢',
            'high' => '⚠️',
            'urgent' => '🚨',
            'critical' => '🔥',
            default => '📢',
        };

        return "{$priority} *{$notification['title']}*\n\n{$notification['message']}\n\n📅 " . 
               now()->format('d/m/Y H:i');
    }

    /**
     * Helper methods for checking user data
     */
    protected function getPendingValidationsCount(User $user): int
    {
        if (!$user->hasAnyRole(['supervisor', 'manager', 'admin'])) {
            return 0;
        }

        $count = 0;
        foreach (['Tindakan', 'PendapatanHarian', 'PengeluaranHarian'] as $model) {
            $modelClass = "App\\Models\\$model";
            if (class_exists($modelClass)) {
                $count += $modelClass::where('status_validasi', 'pending')
                    ->whereNotNull('submitted_at')
                    ->count();
            }
        }

        return $count;
    }

    protected function getOverdueSubmissionsCount(User $user): int
    {
        $count = 0;
        foreach (['Tindakan', 'PendapatanHarian', 'PengeluaranHarian'] as $model) {
            $modelClass = "App\\Models\\$model";
            if (class_exists($modelClass)) {
                $count += $modelClass::where('status_validasi', 'pending')
                    ->whereNull('submitted_at')
                    ->where('created_at', '<', now()->subDays(3))
                    ->where('input_by', $user->id)
                    ->count();
            }
        }

        return $count;
    }

    protected function shouldSendDailyTargetReminder(User $user): bool
    {
        // Send daily target reminder at 9 AM and 3 PM
        $currentHour = now()->hour;
        return in_array($currentHour, [9, 15]) && $user->hasRole('petugas');
    }

    /**
     * Log notification
     */
    protected function logNotification(array $notification): void
    {
        try {
            AuditLog::create([
                'user_id' => $notification['user_id'],
                'action' => 'notification_sent',
                'model_type' => 'Notification',
                'model_id' => null,
                'changes' => json_encode($notification),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'url' => request()->fullUrl(),
                'method' => request()->method(),
                'risk_level' => 'low',
            ]);
        } catch (Exception $e) {
            Log::error('Failed to log notification', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}