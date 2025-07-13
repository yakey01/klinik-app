<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TelegramSetting extends Model
{
    protected $fillable = [
        'role',
        'chat_id',
        'notification_types',
        'is_active',
    ];

    protected $casts = [
        'notification_types' => 'array',
        'is_active' => 'boolean',
    ];

    public static function getAvailableNotificationTypes(): array
    {
        return [
            'income_success' => 'Input pendapatan berhasil',
            'patient_success' => 'Input pasien berhasil',
            'daily_validation_approved' => 'Validasi harian disetujui',
            'jaspel_completed' => 'Validasi JP selesai',
            'backup_failed' => 'Backup gagal',
            'user_added' => 'User baru ditambahkan',
            'daily_recap' => 'Rekap harian',
            'weekly_recap' => 'Rekap mingguan',
        ];
    }

    public static function getRoleNotifications(string $role): array
    {
        $roleMap = [
            'petugas' => ['income_success', 'patient_success', 'daily_recap'],
            'bendahara' => ['daily_validation_approved', 'jaspel_completed', 'daily_recap'],
            'admin' => ['backup_failed', 'user_added', 'daily_recap'],
            'manajer' => ['weekly_recap', 'daily_recap'],
        ];

        return $roleMap[$role] ?? [];
    }

    public function hasNotificationType(string $type): bool
    {
        return in_array($type, $this->notification_types ?? []);
    }
}
