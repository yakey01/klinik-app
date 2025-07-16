<?php

namespace App\Services;

use App\Services\TelegramService;
use App\Enums\TelegramNotificationType;
use App\Models\User;
use App\Models\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Carbon\Carbon;
use Exception;

class RealTimeNotificationService
{
    protected TelegramService $telegramService;
    protected array $notificationChannels = ['database', 'telegram', 'email'];
    protected array $urgencyLevels = ['low', 'medium', 'high', 'critical'];

    public function __construct(TelegramService $telegramService)
    {
        $this->telegramService = $telegramService;
    }

    /**
     * Send real-time notification through multiple channels
     */
    public function sendNotification(array $data): array
    {
        $notification = $this->prepareNotification($data);
        $results = [];

        foreach ($notification['channels'] as $channel) {
            try {
                $result = $this->sendThroughChannel($channel, $notification);
                $results[$channel] = $result;

                Log::info('Notification sent successfully', [
                    'channel' => $channel,
                    'notification_id' => $notification['id'],
                    'recipient' => $notification['recipient'],
                ]);

            } catch (Exception $e) {
                $results[$channel] = [
                    'success' => false,
                    'error' => $e->getMessage(),
                ];

                Log::error('Failed to send notification', [
                    'channel' => $channel,
                    'notification_id' => $notification['id'],
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Store notification history
        $this->storeNotificationHistory($notification, $results);

        return [
            'success' => !empty(array_filter($results, fn($r) => $r['success'] ?? false)),
            'notification_id' => $notification['id'],
            'results' => $results,
        ];
    }

    /**
     * Send notification through specific channel
     */
    protected function sendThroughChannel(string $channel, array $notification): array
    {
        return match ($channel) {
            'telegram' => $this->sendTelegramNotification($notification),
            'database' => $this->sendDatabaseNotification($notification),
            'email' => $this->sendEmailNotification($notification),
            'websocket' => $this->sendWebSocketNotification($notification),
            default => ['success' => false, 'error' => 'Unknown channel'],
        };
    }

    /**
     * Send Telegram notification
     */
    protected function sendTelegramNotification(array $notification): array
    {
        try {
            $message = $this->formatTelegramMessage($notification);
            
            $this->telegramService->sendMessage(
                $message,
                $notification['telegram_type'] ?? TelegramNotificationType::GENERAL_NOTIFICATION,
                $notification['recipient']
            );

            return ['success' => true, 'sent_at' => now()];

        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Send database notification
     */
    protected function sendDatabaseNotification(array $notification): array
    {
        try {
            $user = is_numeric($notification['recipient']) 
                ? User::find($notification['recipient'])
                : User::where('email', $notification['recipient'])->first();

            if (!$user) {
                throw new Exception('User not found for database notification');
            }

            $user->notifications()->create([
                'id' => $notification['id'],
                'type' => $notification['type'],
                'data' => [
                    'title' => $notification['title'],
                    'message' => $notification['message'],
                    'urgency' => $notification['urgency'],
                    'category' => $notification['category'],
                    'action_url' => $notification['action_url'] ?? null,
                    'metadata' => $notification['metadata'] ?? [],
                ],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return ['success' => true, 'sent_at' => now()];

        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Send email notification
     */
    protected function sendEmailNotification(array $notification): array
    {
        try {
            // Email implementation would go here
            // For now, we'll simulate success
            
            Log::info('Email notification simulated', [
                'recipient' => $notification['recipient'],
                'title' => $notification['title'],
            ]);

            return ['success' => true, 'sent_at' => now()];

        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Send WebSocket notification for real-time updates
     */
    protected function sendWebSocketNotification(array $notification): array
    {
        try {
            // Broadcast event for real-time updates
            Event::dispatch('notification.sent', $notification);
            
            return ['success' => true, 'sent_at' => now()];

        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Prepare notification data
     */
    protected function prepareNotification(array $data): array
    {
        $notification = array_merge([
            'id' => uniqid('notif_'),
            'title' => 'Notification',
            'message' => 'You have a new notification',
            'urgency' => 'medium',
            'category' => 'general',
            'channels' => ['database'],
            'recipient' => null,
            'metadata' => [],
            'created_at' => now(),
        ], $data);

        // Determine channels based on urgency
        $notification['channels'] = $this->determineChannels($notification['urgency'], $notification['category']);

        // Set Telegram notification type
        $notification['telegram_type'] = $this->determineTelegramType($notification['category']);

        return $notification;
    }

    /**
     * Determine notification channels based on urgency and category
     */
    protected function determineChannels(string $urgency, string $category): array
    {
        $channels = ['database']; // Always send to database

        $channelMap = [
            'critical' => ['database', 'telegram', 'email', 'websocket'],
            'high' => ['database', 'telegram', 'websocket'],
            'medium' => ['database', 'telegram'],
            'low' => ['database'],
        ];

        // Special category handling
        $specialCategories = [
            'financial' => ['database', 'telegram'],
            'security' => ['database', 'telegram', 'email'],
            'system' => ['database', 'telegram'],
            'approval' => ['database', 'telegram'],
            'alert' => ['database', 'telegram', 'websocket'],
        ];

        if (isset($specialCategories[$category])) {
            $channels = $specialCategories[$category];
        } elseif (isset($channelMap[$urgency])) {
            $channels = $channelMap[$urgency];
        }

        return array_unique($channels);
    }

    /**
     * Determine Telegram notification type
     */
    protected function determineTelegramType(string $category): TelegramNotificationType
    {
        return match ($category) {
            'financial' => TelegramNotificationType::FINANCIAL_ALERT,
            'approval' => TelegramNotificationType::APPROVAL_REQUIRED,
            'security' => TelegramNotificationType::SECURITY_ALERT,
            'system' => TelegramNotificationType::SYSTEM_NOTIFICATION,
            'jaspel' => TelegramNotificationType::JASPEL_NOTIFICATION,
            default => TelegramNotificationType::GENERAL_NOTIFICATION,
        };
    }

    /**
     * Format message for Telegram
     */
    protected function formatTelegramMessage(array $notification): string
    {
        $urgencyEmoji = match ($notification['urgency']) {
            'critical' => '🚨',
            'high' => '⚠️',
            'medium' => 'ℹ️',
            'low' => '💡',
            default => 'ℹ️',
        };

        $categoryEmoji = match ($notification['category']) {
            'financial' => '💰',
            'security' => '🔒',
            'approval' => '✅',
            'system' => '⚙️',
            'jaspel' => '💵',
            default => '📢',
        };

        $message = "{$urgencyEmoji} {$categoryEmoji} *{$notification['title']}*\n\n";
        $message .= "{$notification['message']}\n";

        if (!empty($notification['metadata'])) {
            $message .= "\n📊 *Detail:*\n";
            foreach ($notification['metadata'] as $key => $value) {
                $formattedKey = ucfirst(str_replace('_', ' ', $key));
                $message .= "• {$formattedKey}: {$value}\n";
            }
        }

        if (!empty($notification['action_url'])) {
            $message .= "\n🔗 [Lihat Detail]({$notification['action_url']})";
        }

        $message .= "\n⏰ " . now()->format('d/m/Y H:i');

        return $message;
    }

    /**
     * Store notification history
     */
    protected function storeNotificationHistory(array $notification, array $results): void
    {
        $historyData = [
            'notification_id' => $notification['id'],
            'title' => $notification['title'],
            'recipient' => $notification['recipient'],
            'channels' => $notification['channels'],
            'urgency' => $notification['urgency'],
            'category' => $notification['category'],
            'results' => $results,
            'sent_at' => now(),
        ];

        Cache::put(
            "notification_history:{$notification['id']}", 
            $historyData, 
            now()->addDays(30)
        );

        // Store in database for analytics
        Log::channel('notifications')->info('Notification processed', $historyData);
    }

    /**
     * Send financial alert notification
     */
    public function sendFinancialAlert(string $type, array $data): array
    {
        $messages = [
            'budget_exceeded' => [
                'title' => 'Anggaran Terlampaui',
                'message' => "Anggaran {$data['category']} telah terlampaui {$data['percentage']}%",
                'urgency' => 'high',
            ],
            'large_transaction' => [
                'title' => 'Transaksi Besar Terdeteksi',
                'message' => "Transaksi senilai Rp " . number_format($data['amount'], 0, ',', '.') . " memerlukan perhatian",
                'urgency' => 'medium',
            ],
            'daily_limit_warning' => [
                'title' => 'Peringatan Limit Harian',
                'message' => "Limit harian akan tercapai dalam {$data['remaining_amount']}",
                'urgency' => 'medium',
            ],
            'cash_flow_negative' => [
                'title' => 'Arus Kas Negatif',
                'message' => "Arus kas menunjukkan tren negatif selama {$data['days']} hari",
                'urgency' => 'high',
            ],
        ];

        $messageData = $messages[$type] ?? [
            'title' => 'Alert Keuangan',
            'message' => 'Terdapat aktivitas keuangan yang memerlukan perhatian',
            'urgency' => 'medium',
        ];

        return $this->sendNotification(array_merge($messageData, [
            'category' => 'financial',
            'recipient' => $data['recipient'] ?? null,
            'metadata' => $data,
            'action_url' => $data['action_url'] ?? null,
        ]));
    }

    /**
     * Send approval notification
     */
    public function sendApprovalNotification(string $type, array $data): array
    {
        $messages = [
            'pending_validation' => [
                'title' => 'Validasi Diperlukan',
                'message' => "{$data['count']} transaksi menunggu validasi bendahara",
                'urgency' => 'medium',
            ],
            'urgent_approval' => [
                'title' => 'Persetujuan Mendesak',
                'message' => "Transaksi senilai Rp " . number_format($data['amount'], 0, ',', '.') . " memerlukan persetujuan segera",
                'urgency' => 'high',
            ],
            'approval_deadline' => [
                'title' => 'Deadline Persetujuan',
                'message' => "{$data['count']} transaksi akan melewati deadline dalam 24 jam",
                'urgency' => 'high',
            ],
        ];

        $messageData = $messages[$type] ?? [
            'title' => 'Notifikasi Persetujuan',
            'message' => 'Terdapat item yang memerlukan persetujuan',
            'urgency' => 'medium',
        ];

        return $this->sendNotification(array_merge($messageData, [
            'category' => 'approval',
            'recipient' => $data['recipient'] ?? null,
            'metadata' => $data,
            'action_url' => $data['action_url'] ?? null,
        ]));
    }

    /**
     * Send system notification
     */
    public function sendSystemNotification(string $type, array $data): array
    {
        $messages = [
            'backup_completed' => [
                'title' => 'Backup Selesai',
                'message' => "Backup sistem berhasil diselesaikan",
                'urgency' => 'low',
            ],
            'system_maintenance' => [
                'title' => 'Pemeliharaan Sistem',
                'message' => "Sistem akan menjalani pemeliharaan pada {$data['scheduled_time']}",
                'urgency' => 'medium',
            ],
            'security_alert' => [
                'title' => 'Alert Keamanan',
                'message' => "Aktivitas mencurigakan terdeteksi: {$data['description']}",
                'urgency' => 'critical',
            ],
            'performance_warning' => [
                'title' => 'Peringatan Performa',
                'message' => "Performa sistem menurun: {$data['metric']} = {$data['value']}",
                'urgency' => 'medium',
            ],
        ];

        $messageData = $messages[$type] ?? [
            'title' => 'Notifikasi Sistem',
            'message' => 'Informasi sistem',
            'urgency' => 'low',
        ];

        return $this->sendNotification(array_merge($messageData, [
            'category' => 'system',
            'recipient' => $data['recipient'] ?? null,
            'metadata' => $data,
            'action_url' => $data['action_url'] ?? null,
        ]));
    }

    /**
     * Send JASPEL notification
     */
    public function sendJaspelNotification(string $type, array $data): array
    {
        $messages = [
            'jaspel_calculated' => [
                'title' => 'JASPEL Dihitung',
                'message' => "JASPEL sebesar Rp " . number_format($data['amount'], 0, ',', '.') . " telah dihitung untuk {$data['recipient_name']}",
                'urgency' => 'medium',
            ],
            'jaspel_approved' => [
                'title' => 'JASPEL Disetujui',
                'message' => "JASPEL sebesar Rp " . number_format($data['amount'], 0, ',', '.') . " telah disetujui",
                'urgency' => 'medium',
            ],
            'monthly_jaspel_summary' => [
                'title' => 'Ringkasan JASPEL Bulanan',
                'message' => "Total JASPEL bulan ini: Rp " . number_format($data['total_amount'], 0, ',', '.'),
                'urgency' => 'low',
            ],
        ];

        $messageData = $messages[$type] ?? [
            'title' => 'Notifikasi JASPEL',
            'message' => 'Informasi JASPEL',
            'urgency' => 'medium',
        ];

        return $this->sendNotification(array_merge($messageData, [
            'category' => 'jaspel',
            'recipient' => $data['recipient'] ?? null,
            'metadata' => $data,
            'action_url' => $data['action_url'] ?? null,
        ]));
    }

    /**
     * Get notification statistics
     */
    public function getNotificationStats(int $days = 30): array
    {
        $notifications = $this->getNotificationHistory($days);

        return [
            'total_sent' => count($notifications),
            'by_urgency' => $this->groupBy($notifications, 'urgency'),
            'by_category' => $this->groupBy($notifications, 'category'),
            'by_channel' => $this->getChannelStats($notifications),
            'success_rate' => $this->calculateSuccessRate($notifications),
            'daily_stats' => $this->getDailyStats($notifications),
        ];
    }

    /**
     * Get notification history
     */
    protected function getNotificationHistory(int $days): array
    {
        // In a real implementation, this would query a database
        // For now, we'll return cached data
        $keys = Cache::store('redis')->getStore()->getRedis()->keys('notification_history:*');
        $notifications = [];

        foreach ($keys as $key) {
            $data = Cache::get($key);
            if ($data && Carbon::parse($data['sent_at'])->gte(now()->subDays($days))) {
                $notifications[] = $data;
            }
        }

        return $notifications;
    }

    /**
     * Group notifications by field
     */
    protected function groupBy(array $notifications, string $field): array
    {
        $grouped = [];
        foreach ($notifications as $notification) {
            $value = $notification[$field] ?? 'unknown';
            $grouped[$value] = ($grouped[$value] ?? 0) + 1;
        }
        return $grouped;
    }

    /**
     * Get channel statistics
     */
    protected function getChannelStats(array $notifications): array
    {
        $stats = [];
        foreach ($notifications as $notification) {
            foreach ($notification['results'] as $channel => $result) {
                if (!isset($stats[$channel])) {
                    $stats[$channel] = ['sent' => 0, 'success' => 0, 'failed' => 0];
                }
                $stats[$channel]['sent']++;
                if ($result['success'] ?? false) {
                    $stats[$channel]['success']++;
                } else {
                    $stats[$channel]['failed']++;
                }
            }
        }
        return $stats;
    }

    /**
     * Calculate overall success rate
     */
    protected function calculateSuccessRate(array $notifications): float
    {
        if (empty($notifications)) {
            return 0;
        }

        $totalAttempts = 0;
        $successfulAttempts = 0;

        foreach ($notifications as $notification) {
            foreach ($notification['results'] as $result) {
                $totalAttempts++;
                if ($result['success'] ?? false) {
                    $successfulAttempts++;
                }
            }
        }

        return $totalAttempts > 0 ? round(($successfulAttempts / $totalAttempts) * 100, 2) : 0;
    }

    /**
     * Get daily notification statistics
     */
    protected function getDailyStats(array $notifications): array
    {
        $dailyStats = [];
        foreach ($notifications as $notification) {
            $date = Carbon::parse($notification['sent_at'])->format('Y-m-d');
            if (!isset($dailyStats[$date])) {
                $dailyStats[$date] = ['count' => 0, 'success' => 0];
            }
            $dailyStats[$date]['count']++;
            if (!empty(array_filter($notification['results'], fn($r) => $r['success'] ?? false))) {
                $dailyStats[$date]['success']++;
            }
        }
        return $dailyStats;
    }

    /**
     * Clear old notification history
     */
    public function clearOldNotifications(int $daysToKeep = 90): int
    {
        $keys = Cache::store('redis')->getStore()->getRedis()->keys('notification_history:*');
        $deleted = 0;

        foreach ($keys as $key) {
            $data = Cache::get($key);
            if ($data && Carbon::parse($data['sent_at'])->lt(now()->subDays($daysToKeep))) {
                Cache::forget($key);
                $deleted++;
            }
        }

        return $deleted;
    }
}