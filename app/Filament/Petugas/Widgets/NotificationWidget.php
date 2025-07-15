<?php

namespace App\Filament\Petugas\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Services\NotificationService;
use App\Services\TelegramService;
use Exception;

class NotificationWidget extends Widget
{
    protected static string $view = 'filament.petugas.widgets.notification-widget';
    
    protected static ?int $sort = 1;
    
    protected static bool $isLazy = false;
    
    protected int|string|array $columnSpan = 'full';
    
    protected static ?string $pollingInterval = '30s';
    
    protected NotificationService $notificationService;
    
    public function __construct()
    {
        try {
            $telegramService = app(TelegramService::class);
            $this->notificationService = new NotificationService($telegramService);
        } catch (Exception $e) {
            Log::error('NotificationWidget: Failed to initialize services', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);
            
            // Fallback initialization
            $telegramService = new TelegramService();
            $this->notificationService = new NotificationService($telegramService);
        }
    }

    public function getViewData(): array
    {
        try {
            $userId = Auth::id();
            
            if (!$userId) {
                Log::warning('NotificationWidget: No authenticated user');
                return $this->getEmptyViewData('Tidak ada user yang terautentikasi');
            }
            
            $notifications = $this->notificationService->getUserNotifications($userId, 10);
            
            if (!$notifications['success']) {
                Log::warning('NotificationWidget: Failed to get notifications', [
                    'user_id' => $userId,
                    'error' => $notifications['error'] ?? 'Unknown error'
                ]);
                return $this->getEmptyViewData('Gagal memuat notifikasi');
            }
            
            return [
                'notifications' => $notifications['notifications'] ?? [],
                'total' => $notifications['total'] ?? 0,
                'unread' => $notifications['unread'] ?? 0,
                'last_updated' => now()->format('d/m/Y H:i'),
                'user_id' => $userId,
            ];
            
        } catch (Exception $e) {
            Log::error('NotificationWidget: Failed to get view data', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return $this->getEmptyViewData('Terjadi kesalahan saat memuat notifikasi');
        }
    }

    public function markAsRead(string $notificationId): void
    {
        try {
            $userId = Auth::id();
            
            if (!$userId) {
                Log::warning('NotificationWidget: Cannot mark as read - no authenticated user');
                return;
            }
            
            if (empty($notificationId)) {
                Log::warning('NotificationWidget: Cannot mark as read - empty notification ID', [
                    'user_id' => $userId,
                ]);
                return;
            }
            
            $result = $this->notificationService->markAsRead($userId, $notificationId);
            
            if (!$result['success']) {
                Log::warning('NotificationWidget: Failed to mark notification as read', [
                    'user_id' => $userId,
                    'notification_id' => $notificationId,
                    'error' => $result['error'] ?? 'Unknown error'
                ]);
            } else {
                Log::info('NotificationWidget: Notification marked as read', [
                    'user_id' => $userId,
                    'notification_id' => $notificationId,
                ]);
            }
            
        } catch (Exception $e) {
            Log::error('NotificationWidget: Failed to mark notification as read', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'notification_id' => $notificationId,
            ]);
        }
    }
    
    public function clearAll(): void
    {
        try {
            $userId = Auth::id();
            
            if (!$userId) {
                Log::warning('NotificationWidget: Cannot clear all - no authenticated user');
                return;
            }
            
            $result = $this->notificationService->clearAllNotifications($userId);
            
            if (!$result['success']) {
                Log::warning('NotificationWidget: Failed to clear all notifications', [
                    'user_id' => $userId,
                    'error' => $result['error'] ?? 'Unknown error'
                ]);
            } else {
                Log::info('NotificationWidget: All notifications cleared', [
                    'user_id' => $userId,
                ]);
            }
            
        } catch (Exception $e) {
            Log::error('NotificationWidget: Failed to clear all notifications', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);
        }
    }
    
    protected function getEmptyViewData(string $error = ''): array
    {
        return [
            'notifications' => [],
            'total' => 0,
            'unread' => 0,
            'last_updated' => now()->format('d/m/Y H:i'),
            'user_id' => Auth::id(),
            'error' => $error,
        ];
    }
    
    public function getPollingInterval(): ?string
    {
        return static::$pollingInterval;
    }
}