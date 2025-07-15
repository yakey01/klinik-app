<?php

namespace App\Services;

use App\Enums\TelegramNotificationType;
use App\Models\SystemConfig;
use App\Models\TelegramSetting;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Telegram\Bot\Laravel\Facades\Telegram;

class TelegramService
{
    public function sendMessage(string $chatId, string $message, array $options = []): bool
    {
        Log::info('TelegramService::sendMessage called', [
            'chat_id' => $chatId,
            'message_length' => strlen($message),
            'has_options' => ! empty($options),
        ]);

        // Get token from database or config
        $token = $this->getBotToken();
        Log::info('Bot token retrieved', [
            'has_token' => ! empty($token),
            'is_demo' => $this->isDemoToken($token),
            'token_preview' => $token ? substr($token, 0, 10).'...' : 'null',
        ]);

        if (! $token || $this->isDemoToken($token)) {
            // Demo mode - log but don't send in production
            if (app()->environment(['local', 'development'])) {
                Log::info("Demo Telegram Message to {$chatId}: {$message}");
                return true;
            } else {
                Log::warning("Telegram token not configured properly in production");
                return false;
            }
        }

        try {
            // Set token dynamically
            config(['telegram.bots.dokterku.token' => $token]);

            Log::info("Sending message to chat_id: {$chatId}");

            $response = Telegram::sendMessage([
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => 'HTML',
                ...$options,
            ]);

            $success = $response->getMessageId() !== null;
            Log::info('Send result: '.($success ? 'SUCCESS' : 'FAILED'), [
                'message_id' => $response->getMessageId(),
                'chat_id' => $chatId,
            ]);

            return $success;
        } catch (TelegramSDKException $e) {
            Log::error('Telegram send message error: '.$e->getMessage(), [
                'chat_id' => $chatId,
                'error_code' => $e->getCode(),
            ]);

            return false;
        }
    }

    public function getBotInfo(): array
    {
        // Get token from database or config
        $token = $this->getBotToken();
        if (! $token || $this->isDemoToken($token)) {
            return [
                'id' => 1234567890,
                'username' => 'dokterku_demo_bot',
                'first_name' => 'Dokterku Demo Bot',
                'is_bot' => true,
                'can_join_groups' => true,
                'can_read_all_group_messages' => false,
                'supports_inline_queries' => false,
                'demo_mode' => true,
            ];
        }

        try {
            // Set token dynamically
            config(['telegram.bots.dokterku.token' => $token]);

            $response = Telegram::getMe();

            return [
                'id' => $response->getId(),
                'username' => $response->getUsername(),
                'first_name' => $response->getFirstName(),
                'is_bot' => $response->isBot(),
                'can_join_groups' => true,
                'can_read_all_group_messages' => false,
                'demo_mode' => false,
            ];
        } catch (TelegramSDKException $e) {
            throw new \Exception('Failed to get bot info: '.$e->getMessage());
        }
    }

    public function sendNotificationToRole(string $role, string $notificationType, string $message, ?int $userId = null): bool
    {
        Log::info('TelegramService::sendNotificationToRole called', [
            'role' => $role,
            'user_id' => $userId,
            'notification_type' => $notificationType,
            'message_preview' => substr($message, 0, 100).'...',
        ]);

        // If user_id is provided, try to find user-specific setting first
        if ($userId) {
            $userSetting = TelegramSetting::where('role', $role)
                ->where('user_id', $userId)
                ->where('role_type', 'specific_user')
                ->where('is_active', true)
                ->first();

            if ($userSetting && $userSetting->chat_id && $userSetting->hasNotificationType($notificationType)) {
                Log::info("Sending notification to specific user {$userId} in role {$role} with chat_id: {$userSetting->chat_id}");
                $result = $this->sendMessage($userSetting->chat_id, $message);
                Log::info('User-specific notification result: '.($result ? 'SUCCESS' : 'FAILED'));

                return $result;
            }
        }

        // Fallback to general role setting
        $setting = TelegramSetting::where('role', $role)
            ->where(function ($query) {
                $query->where('role_type', 'general')
                    ->orWhereNull('role_type');
            })
            ->where('is_active', true)
            ->first();

        if (! $setting) {
            Log::warning("No telegram setting found for role: {$role}");

            return false;
        }

        if (! $setting->chat_id) {
            Log::warning("No chat_id configured for role: {$role}");

            return false;
        }

        if (! $setting->hasNotificationType($notificationType)) {
            Log::warning("Notification type {$notificationType} not enabled for role: {$role}", [
                'enabled_types' => $setting->notification_types ?? [],
            ]);

            return false;
        }

        Log::info("Sending notification to role {$role} with chat_id: {$setting->chat_id}");
        $result = $this->sendMessage($setting->chat_id, $message);
        Log::info('Notification result: '.($result ? 'SUCCESS' : 'FAILED'));

        return $result;
    }

    public function sendNotificationToMultipleRoles(array $roles, string $notificationType, string $message): array
    {
        $results = [];

        foreach ($roles as $role) {
            $results[$role] = $this->sendNotificationToRole($role, $notificationType, $message);
        }

        return $results;
    }

    public function formatNotificationMessage(string $type, array $data = []): string
    {
        $emoji = $this->getNotificationEmoji($type);
        $title = $this->getNotificationTitle($type);

        $message = "{$emoji} <b>{$title}</b>\n\n";

        $enum = TelegramNotificationType::tryFrom($type);
        if ($enum) {
            switch ($enum) {
                case TelegramNotificationType::PENDAPATAN:
                    $message .= '💰 Pendapatan: Rp '.number_format($data['amount'] ?? 0, 0, ',', '.')."\n";
                    $message .= '📝 Deskripsi: '.($data['description'] ?? '-')."\n";
                    break;

                case TelegramNotificationType::PENGELUARAN:
                    $message .= '📉 Pengeluaran: Rp '.number_format($data['amount'] ?? 0, 0, ',', '.')."\n";
                    $message .= '📝 Deskripsi: '.($data['description'] ?? '-')."\n";
                    break;

                case TelegramNotificationType::PASIEN:
                    $message .= '👤 Nama Pasien: '.($data['patient_name'] ?? '-')."\n";
                    $message .= '🩺 Tindakan: '.($data['procedure'] ?? '-')."\n";
                    break;

                case TelegramNotificationType::VALIDASI_DISETUJUI:
                    if (isset($data['type'])) {
                        $message .= '📋 Jenis: '.$data['type']."\n";
                    }
                    $message .= '💰 Nilai: Rp '.number_format($data['amount'] ?? 0, 0, ',', '.')."\n";
                    $message .= '📝 Deskripsi: '.($data['description'] ?? '-')."\n";
                    if (isset($data['date'])) {
                        $message .= '📅 Tanggal: '.$data['date']."\n";
                    }
                    if (isset($data['shift'])) {
                        $message .= '⏰ Shift: '.$data['shift']."\n";
                    }
                    if (isset($data['petugas'])) {
                        $message .= '👤 Input oleh: '.$data['petugas']."\n";
                    }
                    if (isset($data['validator_name'])) {
                        $message .= '✅ Divalidasi oleh: '.$data['validator_name']."\n";
                    }
                    break;

                case TelegramNotificationType::JASPEL_SELESAI:
                    $message .= '👨‍⚕️ Dokter: '.($data['doctor_name'] ?? '-')."\n";
                    $message .= '💰 Jaspel: Rp '.number_format($data['jaspel_amount'] ?? 0, 0, ',', '.')."\n";
                    break;

                case TelegramNotificationType::BACKUP_GAGAL:
                    $message .= '❌ Error: '.($data['error'] ?? 'Unknown error')."\n";
                    $message .= '🕐 Waktu: '.($data['time'] ?? now()->format('H:i:s'))."\n";
                    break;

                case TelegramNotificationType::USER_BARU:
                    $message .= '👤 Nama: '.($data['user_name'] ?? '-')."\n";
                    $message .= '🏷️ Role: '.($data['role'] ?? '-')."\n";
                    break;

                case TelegramNotificationType::REKAP_HARIAN:
                case TelegramNotificationType::REKAP_MINGGUAN:
                    $message .= '📊 Total Pendapatan: Rp '.number_format($data['total_income'] ?? 0, 0, ',', '.')."\n";
                    $message .= '📉 Total Pengeluaran: Rp '.number_format($data['total_expense'] ?? 0, 0, ',', '.')."\n";
                    $message .= '💰 Saldo: Rp '.number_format(($data['total_income'] ?? 0) - ($data['total_expense'] ?? 0), 0, ',', '.')."\n";
                    break;
            }
        }

        $message .= "\n📅 ".now()->format('d/m/Y H:i:s')."\n";
        $message .= '🏥 <i>Dokterku - SAHABAT MENUJU SEHAT</i>';

        return $message;
    }

    private function getNotificationEmoji(string $type): string
    {
        if ($enum = TelegramNotificationType::tryFrom($type)) {
            return match ($enum) {
                TelegramNotificationType::PENDAPATAN => '💰',
                TelegramNotificationType::PENGELUARAN => '📉',
                TelegramNotificationType::PASIEN => '👤',
                TelegramNotificationType::USER_BARU => '👋',
                TelegramNotificationType::REKAP_HARIAN => '📊',
                TelegramNotificationType::REKAP_MINGGUAN => '📈',
                TelegramNotificationType::VALIDASI_DISETUJUI => '✅',
                TelegramNotificationType::JASPEL_SELESAI => '💼',
                TelegramNotificationType::BACKUP_GAGAL => '🚨',
            };
        }

        return '📢';
    }

    private function getNotificationTitle(string $type): string
    {
        if ($enum = TelegramNotificationType::tryFrom($type)) {
            return match ($enum) {
                TelegramNotificationType::PENDAPATAN => 'Pendapatan Berhasil Diinput',
                TelegramNotificationType::PENGELUARAN => 'Pengeluaran Berhasil Diinput',
                TelegramNotificationType::PASIEN => 'Pasien Berhasil Diinput',
                TelegramNotificationType::USER_BARU => 'User Baru Ditambahkan',
                TelegramNotificationType::REKAP_HARIAN => 'Rekap Harian',
                TelegramNotificationType::REKAP_MINGGUAN => 'Rekap Mingguan',
                TelegramNotificationType::VALIDASI_DISETUJUI => 'Validasi Disetujui',
                TelegramNotificationType::JASPEL_SELESAI => 'Jaspel Selesai',
                TelegramNotificationType::BACKUP_GAGAL => 'Backup Gagal',
            };
        }

        return 'Notifikasi Sistem';
    }

    public function isConfigured(): bool
    {
        $token = SystemConfig::get('telegram_bot_token');

        return ! empty($token);
    }

    public function getActiveSettings(): array
    {
        return TelegramSetting::where('is_active', true)
            ->whereNotNull('chat_id')
            ->get()
            ->keyBy('role')
            ->toArray();
    }

    /**
     * Get bot token from database or fallback to config
     */
    protected function getBotToken(): ?string
    {
        // Try to get from database first
        $dbToken = SystemConfig::where('key', 'TELEGRAM_BOT_TOKEN')->value('value');

        if ($dbToken) {
            return $dbToken;
        }

        // Fallback to config/env
        return config('telegram.bots.dokterku.token');
    }

    /**
     * Check if token is demo/placeholder token
     */
    protected function isDemoToken(?string $token): bool
    {
        if (! $token) {
            return true;
        }

        $demoTokens = [
            'YOUR-BOT-TOKEN',
            'your_bot_token_from_botfather',
            '1234567890:ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijk',
        ];

        // Check if token contains demo patterns
        foreach ($demoTokens as $demoToken) {
            if ($token === $demoToken || str_contains($token, 'ABCD')) {
                return true;
            }
        }

        return false;
    }
}
