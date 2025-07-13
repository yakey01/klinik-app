<?php

namespace App\Services;

use App\Models\SystemConfig;
use App\Models\TelegramSetting;
use Telegram\Bot\Laravel\Facades\Telegram;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    public function sendMessage(string $chatId, string $message, array $options = []): bool
    {
        try {
            $response = Telegram::sendMessage([
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => 'HTML',
                ...$options
            ]);

            return $response->getMessageId() !== null;
        } catch (TelegramSDKException $e) {
            Log::error('Telegram send message error: ' . $e->getMessage());
            return false;
        }
    }

    public function getBotInfo(): array
    {
        try {
            $response = Telegram::getMe();
            return [
                'id' => $response->getId(),
                'username' => $response->getUsername(),
                'first_name' => $response->getFirstName(),
                'is_bot' => $response->isBot(),
            ];
        } catch (TelegramSDKException $e) {
            throw new \Exception('Failed to get bot info: ' . $e->getMessage());
        }
    }

    public function sendNotificationToRole(string $role, string $notificationType, string $message): bool
    {
        $setting = TelegramSetting::where('role', $role)
            ->where('is_active', true)
            ->first();

        if (!$setting || !$setting->chat_id || !$setting->hasNotificationType($notificationType)) {
            return false;
        }

        return $this->sendMessage($setting->chat_id, $message);
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
        
        switch ($type) {
            case 'income_success':
                $message .= "💰 Pendapatan: Rp " . number_format($data['amount'] ?? 0, 0, ',', '.') . "\n";
                $message .= "📝 Deskripsi: " . ($data['description'] ?? '-') . "\n";
                break;
                
            case 'patient_success':
                $message .= "👤 Nama Pasien: " . ($data['patient_name'] ?? '-') . "\n";
                $message .= "🩺 Tindakan: " . ($data['procedure'] ?? '-') . "\n";
                break;
                
            case 'daily_validation_approved':
                $message .= "📊 Total Validasi: " . ($data['total_validations'] ?? 0) . "\n";
                $message .= "💰 Total Nilai: Rp " . number_format($data['total_amount'] ?? 0, 0, ',', '.') . "\n";
                break;
                
            case 'jaspel_completed':
                $message .= "👨‍⚕️ Dokter: " . ($data['doctor_name'] ?? '-') . "\n";
                $message .= "💰 Jaspel: Rp " . number_format($data['jaspel_amount'] ?? 0, 0, ',', '.') . "\n";
                break;
                
            case 'backup_failed':
                $message .= "❌ Error: " . ($data['error'] ?? 'Unknown error') . "\n";
                $message .= "🕐 Waktu: " . ($data['time'] ?? now()->format('H:i:s')) . "\n";
                break;
                
            case 'user_added':
                $message .= "👤 Nama: " . ($data['user_name'] ?? '-') . "\n";
                $message .= "🏷️ Role: " . ($data['role'] ?? '-') . "\n";
                break;
                
            case 'daily_recap':
            case 'weekly_recap':
                $message .= "📊 Total Pendapatan: Rp " . number_format($data['total_income'] ?? 0, 0, ',', '.') . "\n";
                $message .= "📉 Total Pengeluaran: Rp " . number_format($data['total_expense'] ?? 0, 0, ',', '.') . "\n";
                $message .= "💰 Saldo: Rp " . number_format(($data['total_income'] ?? 0) - ($data['total_expense'] ?? 0), 0, ',', '.') . "\n";
                break;
        }
        
        $message .= "\n📅 " . now()->format('d/m/Y H:i:s') . "\n";
        $message .= "🏥 <i>Dokterku - SAHABAT MENUJU SEHAT</i>";
        
        return $message;
    }

    private function getNotificationEmoji(string $type): string
    {
        $emojis = [
            'income_success' => '💰',
            'patient_success' => '👤',
            'daily_validation_approved' => '✅',
            'jaspel_completed' => '💼',
            'backup_failed' => '🚨',
            'user_added' => '👋',
            'daily_recap' => '📊',
            'weekly_recap' => '📈',
        ];

        return $emojis[$type] ?? '📢';
    }

    private function getNotificationTitle(string $type): string
    {
        $titles = [
            'income_success' => 'Pendapatan Berhasil Diinput',
            'patient_success' => 'Pasien Berhasil Diinput',
            'daily_validation_approved' => 'Validasi Harian Disetujui',
            'jaspel_completed' => 'Validasi JP Selesai',
            'backup_failed' => 'Backup Gagal',
            'user_added' => 'User Baru Ditambahkan',
            'daily_recap' => 'Rekap Harian',
            'weekly_recap' => 'Rekap Mingguan',
        ];

        return $titles[$type] ?? 'Notifikasi Sistem';
    }

    public function isConfigured(): bool
    {
        $token = SystemConfig::get('telegram_bot_token');
        return !empty($token);
    }

    public function getActiveSettings(): array
    {
        return TelegramSetting::where('is_active', true)
            ->whereNotNull('chat_id')
            ->get()
            ->keyBy('role')
            ->toArray();
    }
}