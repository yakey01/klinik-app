<?php

namespace App\Config;

class NavigationGroups
{
    /**
     * Standardized navigation groups for Dokterku application
     */
    public const GROUPS = [
        'DASHBOARD' => '🏠 Dashboard',
        'PATIENT_MANAGEMENT' => '👥 Manajemen Pasien',
        'MEDICAL_PROCEDURES' => '🏥 Tindakan Medis',
        'FINANCIAL_MANAGEMENT' => '💰 Manajemen Keuangan',
        'DAILY_TRANSACTIONS' => '📊 Transaksi Harian',
        'VALIDATION' => '✅ Validasi Data',
        'HR_MANAGEMENT' => '👨‍💼 Manajemen SDM',
        'ATTENDANCE' => '📋 Kehadiran',
        'LEAVE_MANAGEMENT' => '🏖️ Cuti & Izin',
        'SCHEDULE_MANAGEMENT' => '📅 Jadwal',
        'JASPEL' => '💼 Jaspel',
        'REPORTS' => '📈 Laporan',
        'SYSTEM_ADMIN' => '⚙️ Administrasi Sistem',
        'USER_MANAGEMENT' => '👤 Manajemen Pengguna',
    ];

    /**
     * Get navigation group by key
     */
    public static function get(string $key): string
    {
        return self::GROUPS[$key] ?? $key;
    }

    /**
     * Get all navigation groups
     */
    public static function all(): array
    {
        return self::GROUPS;
    }
}