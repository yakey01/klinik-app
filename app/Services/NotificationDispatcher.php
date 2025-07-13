<?php

namespace App\Services;

use App\Models\TelegramSetting;
use Illuminate\Support\Facades\Log;

class NotificationDispatcher
{
    protected TelegramService $telegramService;

    public function __construct(TelegramService $telegramService)
    {
        $this->telegramService = $telegramService;
    }

    public function dispatchIncomeSuccess(array $data): void
    {
        $message = $this->telegramService->formatNotificationMessage('income_success', $data);
        $this->sendToRoles(['petugas'], 'income_success', $message);
    }

    public function dispatchPatientSuccess(array $data): void
    {
        $message = $this->telegramService->formatNotificationMessage('patient_success', $data);
        $this->sendToRoles(['petugas'], 'patient_success', $message);
    }

    public function dispatchDailyValidationApproved(array $data): void
    {
        $message = $this->telegramService->formatNotificationMessage('daily_validation_approved', $data);
        $this->sendToRoles(['bendahara'], 'daily_validation_approved', $message);
    }

    public function dispatchJaspelCompleted(array $data): void
    {
        $message = $this->telegramService->formatNotificationMessage('jaspel_completed', $data);
        $this->sendToRoles(['bendahara'], 'jaspel_completed', $message);
    }

    public function dispatchBackupFailed(array $data): void
    {
        $message = $this->telegramService->formatNotificationMessage('backup_failed', $data);
        $this->sendToRoles(['admin'], 'backup_failed', $message);
    }

    public function dispatchUserAdded(array $data): void
    {
        $message = $this->telegramService->formatNotificationMessage('user_added', $data);
        $this->sendToRoles(['admin'], 'user_added', $message);
    }

    public function dispatchDailyRecap(array $data): void
    {
        $message = $this->telegramService->formatNotificationMessage('daily_recap', $data);
        $this->sendToRoles(['petugas', 'bendahara', 'admin', 'manajer'], 'daily_recap', $message);
    }

    public function dispatchWeeklyRecap(array $data): void
    {
        $message = $this->telegramService->formatNotificationMessage('weekly_recap', $data);
        $this->sendToRoles(['manajer'], 'weekly_recap', $message);
    }

    private function sendToRoles(array $roles, string $notificationType, string $message): void
    {
        foreach ($roles as $role) {
            try {
                $result = $this->telegramService->sendNotificationToRole($role, $notificationType, $message);
                
                if (!$result) {
                    Log::warning("Failed to send Telegram notification to role: {$role}, type: {$notificationType}");
                }
            } catch (\Exception $e) {
                Log::error("Error sending Telegram notification to role: {$role}, type: {$notificationType}, error: " . $e->getMessage());
            }
        }
    }

    public function sendTestNotification(string $role, array $data = []): bool
    {
        $message = "🧪 <b>Test Notifikasi</b>\n\n";
        $message .= "👤 Role: {$role}\n";
        $message .= "📅 " . now()->format('d/m/Y H:i:s') . "\n";
        $message .= "🏥 <i>Dokterku - SAHABAT MENUJU SEHAT</i>";

        return $this->telegramService->sendNotificationToRole($role, 'daily_recap', $message);
    }
}