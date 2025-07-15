<?php

namespace App\Services;

use App\Config\NavigationGroups;

class NamingStandardsService
{
    /**
     * Standard model labels in Indonesian
     */
    protected static array $modelLabels = [
        'Pasien' => [
            'singular' => 'Pasien',
            'plural' => 'Pasien',
            'navigation' => 'Pasien',
            'group' => NavigationGroups::GROUPS['PATIENT_MANAGEMENT'],
            'icon' => 'heroicon-o-user',
        ],
        'Dokter' => [
            'singular' => 'Dokter',
            'plural' => 'Dokter',
            'navigation' => 'Dokter',
            'group' => NavigationGroups::GROUPS['HR_MANAGEMENT'],
            'icon' => 'heroicon-o-user-plus',
        ],
        'Pegawai' => [
            'singular' => 'Pegawai',
            'plural' => 'Pegawai',
            'navigation' => 'Pegawai',
            'group' => NavigationGroups::GROUPS['HR_MANAGEMENT'],
            'icon' => 'heroicon-o-users',
        ],
        'Tindakan' => [
            'singular' => 'Tindakan',
            'plural' => 'Tindakan',
            'navigation' => 'Tindakan Medis',
            'group' => NavigationGroups::GROUPS['MEDICAL_PROCEDURES'],
            'icon' => 'heroicon-o-heart',
        ],
        'JenisTindakan' => [
            'singular' => 'Jenis Tindakan',
            'plural' => 'Jenis Tindakan',
            'navigation' => 'Jenis Tindakan',
            'group' => NavigationGroups::GROUPS['MEDICAL_PROCEDURES'],
            'icon' => 'heroicon-o-list-bullet',
        ],
        'Pendapatan' => [
            'singular' => 'Pendapatan',
            'plural' => 'Pendapatan',
            'navigation' => 'Pendapatan',
            'group' => NavigationGroups::GROUPS['FINANCIAL_MANAGEMENT'],
            'icon' => 'heroicon-o-arrow-trending-up',
        ],
        'PendapatanHarian' => [
            'singular' => 'Pendapatan Harian',
            'plural' => 'Pendapatan Harian',
            'navigation' => 'Pendapatan Harian',
            'group' => NavigationGroups::GROUPS['DAILY_TRANSACTIONS'],
            'icon' => 'heroicon-o-banknotes',
        ],
        'Pengeluaran' => [
            'singular' => 'Pengeluaran',
            'plural' => 'Pengeluaran',
            'navigation' => 'Pengeluaran',
            'group' => NavigationGroups::GROUPS['FINANCIAL_MANAGEMENT'],
            'icon' => 'heroicon-o-arrow-trending-down',
        ],
        'PengeluaranHarian' => [
            'singular' => 'Pengeluaran Harian',
            'plural' => 'Pengeluaran Harian',
            'navigation' => 'Pengeluaran Harian',
            'group' => NavigationGroups::GROUPS['DAILY_TRANSACTIONS'],
            'icon' => 'heroicon-o-minus-circle',
        ],
        'JumlahPasienHarian' => [
            'singular' => 'Jumlah Pasien Harian',
            'plural' => 'Jumlah Pasien Harian',
            'navigation' => 'Jumlah Pasien Harian',
            'group' => NavigationGroups::GROUPS['DAILY_TRANSACTIONS'],
            'icon' => 'heroicon-o-chart-bar',
        ],
        'User' => [
            'singular' => 'Pengguna',
            'plural' => 'Pengguna',
            'navigation' => 'Pengguna',
            'group' => NavigationGroups::GROUPS['USER_MANAGEMENT'],
            'icon' => 'heroicon-o-user-circle',
        ],
        'Role' => [
            'singular' => 'Peran',
            'plural' => 'Peran',
            'navigation' => 'Peran',
            'group' => NavigationGroups::GROUPS['USER_MANAGEMENT'],
            'icon' => 'heroicon-o-key',
        ],
    ];

    /**
     * Get model labels
     */
    public static function getModelLabels(string $model): array
    {
        return self::$modelLabels[$model] ?? [
            'singular' => $model,
            'plural' => $model,
            'navigation' => $model,
            'group' => NavigationGroups::GROUPS['SYSTEM_ADMIN'],
            'icon' => 'heroicon-o-document',
        ];
    }

    /**
     * Get standard field labels
     */
    public static function getFieldLabels(): array
    {
        return [
            'id' => 'ID',
            'nama' => 'Nama',
            'nama_lengkap' => 'Nama Lengkap',
            'tanggal' => 'Tanggal',
            'tanggal_lahir' => 'Tanggal Lahir',
            'alamat' => 'Alamat',
            'email' => 'Email',
            'no_telepon' => 'No. Telepon',
            'no_rekam_medis' => 'No. Rekam Medis',
            'jenis_kelamin' => 'Jenis Kelamin',
            'nominal' => 'Nominal',
            'keterangan' => 'Keterangan',
            'deskripsi' => 'Deskripsi',
            'status' => 'Status',
            'created_at' => 'Dibuat Pada',
            'updated_at' => 'Diperbarui Pada',
            'deleted_at' => 'Dihapus Pada',
            'input_by' => 'Diinput Oleh',
            'validasi_by' => 'Divalidasi Oleh',
            'validasi_at' => 'Divalidasi Pada',
            'status_validasi' => 'Status Validasi',
            'catatan_validasi' => 'Catatan Validasi',
            'shift' => 'Shift',
            'poli' => 'Poliklinik',
            'jumlah_pasien' => 'Jumlah Pasien',
            'jumlah_pasien_umum' => 'Jumlah Pasien Umum',
            'jumlah_pasien_bpjs' => 'Jumlah Pasien BPJS',
            'dokter_id' => 'Dokter',
            'pasien_id' => 'Pasien',
            'tindakan_id' => 'Tindakan',
            'kategori' => 'Kategori',
            'is_active' => 'Aktif',
            'aktif' => 'Aktif',
        ];
    }

    /**
     * Get field label
     */
    public static function getFieldLabel(string $field): string
    {
        return self::getFieldLabels()[$field] ?? ucfirst(str_replace('_', ' ', $field));
    }

    /**
     * Get status options
     */
    public static function getStatusOptions(): array
    {
        return [
            'pending' => 'Menunggu',
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
            'disetujui' => 'Disetujui',
            'ditolak' => 'Ditolak',
            'active' => 'Aktif',
            'inactive' => 'Tidak Aktif',
            'completed' => 'Selesai',
            'cancelled' => 'Dibatalkan',
        ];
    }

    /**
     * Get standardized route names
     */
    public static function getRouteNames(): array
    {
        return [
            'pasien' => 'pasien',
            'dokter' => 'dokter',
            'pegawai' => 'pegawai',
            'tindakan' => 'tindakan',
            'jenis-tindakan' => 'jenis-tindakan',
            'pendapatan' => 'pendapatan',
            'pendapatan-harian' => 'pendapatan-harian',
            'pengeluaran' => 'pengeluaran',
            'pengeluaran-harian' => 'pengeluaran-harian',
            'jumlah-pasien-harian' => 'jumlah-pasien-harian',
            'user' => 'user',
            'role' => 'role',
        ];
    }

    /**
     * Generate resource configuration
     */
    public static function generateResourceConfig(string $model): array
    {
        $labels = self::getModelLabels($model);
        
        return [
            'navigationIcon' => $labels['icon'],
            'navigationLabel' => $labels['navigation'],
            'navigationGroup' => $labels['group'],
            'modelLabel' => $labels['singular'],
            'pluralModelLabel' => $labels['plural'],
        ];
    }
}