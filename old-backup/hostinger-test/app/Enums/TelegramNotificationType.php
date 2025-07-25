<?php

namespace App\Enums;

enum TelegramNotificationType: string
{
    case PENDAPATAN = 'pendapatan';
    case PENGELUARAN = 'pengeluaran';
    case PASIEN = 'pasien';
    case USER_BARU = 'user_baru';
    case REKAP_HARIAN = 'rekap_harian';
    case REKAP_MINGGUAN = 'rekap_mingguan';
    case VALIDASI_DISETUJUI = 'validasi_disetujui';
    case JASPEL_SELESAI = 'jaspel_selesai';
    case BACKUP_GAGAL = 'backup_gagal';

    public function label(): string
    {
        return match($this) {
            self::PENDAPATAN => '💰 Notifikasi Pendapatan',
            self::PENGELUARAN => '📉 Notifikasi Pengeluaran',
            self::PASIEN => '👤 Notifikasi Pasien Baru',
            self::USER_BARU => '👋 Notifikasi User Baru',
            self::REKAP_HARIAN => '📊 Rekap Harian',
            self::REKAP_MINGGUAN => '📈 Rekap Mingguan',
            self::VALIDASI_DISETUJUI => '✅ Validasi Disetujui',
            self::JASPEL_SELESAI => '💼 Jaspel Selesai',
            self::BACKUP_GAGAL => '🚨 Backup Gagal',
        };
    }

    public function description(): string
    {
        return match($this) {
            self::PENDAPATAN => 'Notifikasi saat ada pendapatan baru diinput',
            self::PENGELUARAN => 'Notifikasi saat ada pengeluaran baru diinput',
            self::PASIEN => 'Notifikasi saat ada pasien baru didaftarkan',
            self::USER_BARU => 'Notifikasi saat ada user baru ditambahkan ke sistem',
            self::REKAP_HARIAN => 'Laporan rekap transaksi harian otomatis',
            self::REKAP_MINGGUAN => 'Laporan rekap transaksi mingguan otomatis',
            self::VALIDASI_DISETUJUI => 'Notifikasi saat validasi bendahara disetujui',
            self::JASPEL_SELESAI => 'Notifikasi saat perhitungan jaspel selesai',
            self::BACKUP_GAGAL => 'Alert saat backup sistem gagal',
        };
    }

    public static function getForRole(string $role): array
    {
        return match(strtolower($role)) {
            'petugas' => [
                self::PENDAPATAN,
                self::PENGELUARAN,
                self::PASIEN,
                self::REKAP_HARIAN,
            ],
            'bendahara' => [
                self::PENDAPATAN,        // Ditambah: notifikasi input pendapatan
                self::PENGELUARAN,       // Existing: notifikasi input pengeluaran
                self::PASIEN,            // Ditambah: notifikasi tindakan/pasien
                self::VALIDASI_DISETUJUI,
                self::REKAP_HARIAN,
                self::REKAP_MINGGUAN,
            ],
            'admin' => [
                self::USER_BARU,
                self::BACKUP_GAGAL,
                self::VALIDASI_DISETUJUI,
                self::REKAP_HARIAN,
                self::REKAP_MINGGUAN,
            ],
            'manajer' => [
                self::REKAP_HARIAN,
                self::REKAP_MINGGUAN,
                self::JASPEL_SELESAI,
                self::VALIDASI_DISETUJUI,
            ],
            default => [],
        };
    }

    public static function getAllOptions(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }
        return $options;
    }
}