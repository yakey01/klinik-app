<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class GpsAttendanceFeaturesPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-map-pin';
    protected static string $view = 'filament.pages.gps-attendance-features-page';
    protected static ?string $navigationLabel = 'Aplikasi Absensi Berbasis GPS';
    protected static ?string $title = 'Aplikasi Absensi Berbasis GPS';
    protected static ?string $navigationGroup = 'Presensi';
    protected static ?int $navigationSort = 1;

    public function getTitle(): string
    {
        return 'Aplikasi Absensi Berbasis GPS';
    }

    public function getHeading(): string
    {
        return 'Fitur-Fitur Aplikasi Absensi Berbasis GPS';
    }

    public function getSubheading(): ?string
    {
        return 'Sistem kehadiran modern dengan teknologi GPS dan geofencing untuk monitoring lokasi real-time';
    }

    public function getViewData(): array
    {
        return [
            'features' => [
                [
                    'id' => 1,
                    'fitur' => '🔐 Login & Role-based Access',
                    'penjelasan' => 'Akses berdasarkan peran (admin, manajer, karyawan) dengan otentikasi aman.',
                    'kategori' => 'Keamanan',
                    'prioritas' => 'Tinggi'
                ],
                [
                    'id' => 2,
                    'fitur' => '📍 Absensi Masuk & Pulang via GPS',
                    'penjelasan' => 'Karyawan melakukan absen hanya jika berada dalam radius tertentu dari lokasi kerja.',
                    'kategori' => 'Lokasi',
                    'prioritas' => 'Tinggi'
                ],
                [
                    'id' => 3,
                    'fitur' => '🛡️ Validasi Lokasi (Geofencing)',
                    'penjelasan' => 'Sistem hanya mengizinkan absen jika pengguna berada di dalam area yang telah ditentukan (mis. kantor, lokasi proyek).',
                    'kategori' => 'Validasi',
                    'prioritas' => 'Tinggi'
                ],
                [
                    'id' => 4,
                    'fitur' => '⏰ Auto Capture Lokasi & Waktu',
                    'penjelasan' => 'Lokasi (koordinat + nama tempat) dan waktu otomatis terekam saat absen.',
                    'kategori' => 'Automasi',
                    'prioritas' => 'Tinggi'
                ],
                [
                    'id' => 5,
                    'fitur' => '📱 Scan QR Code Tambahan (opsional)',
                    'penjelasan' => 'Verifikasi tambahan untuk membuktikan kehadiran di lokasi fisik tertentu.',
                    'kategori' => 'Verifikasi',
                    'prioritas' => 'Sedang'
                ],
                [
                    'id' => 6,
                    'fitur' => '📊 Riwayat Absensi & Laporan',
                    'penjelasan' => 'Pegawai dan admin bisa melihat riwayat kehadiran lengkap, termasuk peta dan jam absen.',
                    'kategori' => 'Laporan',
                    'prioritas' => 'Tinggi'
                ],
                [
                    'id' => 7,
                    'fitur' => '👁️ Monitoring Real-Time untuk Admin',
                    'penjelasan' => 'Admin bisa melihat siapa saja yang sudah absen, di mana lokasinya, dan jamnya secara langsung.',
                    'kategori' => 'Monitoring',
                    'prioritas' => 'Tinggi'
                ],
                [
                    'id' => 8,
                    'fitur' => '📝 Request Izin, Sakit, dan Cuti Online',
                    'penjelasan' => 'Pegawai bisa mengajukan izin, cuti, atau sakit dengan bukti (misalnya surat sakit).',
                    'kategori' => 'Manajemen',
                    'prioritas' => 'Sedang'
                ],
                [
                    'id' => 9,
                    'fitur' => '🔔 Notifikasi Otomatis (Email/WA/Telegram)',
                    'penjelasan' => 'Notifikasi dikirim ke atasan jika ada keterlambatan atau tidak absen.',
                    'kategori' => 'Komunikasi',
                    'prioritas' => 'Sedang'
                ],
                [
                    'id' => 10,
                    'fitur' => '📸 Integrasi Kamera (Selfie Absensi)',
                    'penjelasan' => 'Untuk mencegah titip absen, pengguna diminta selfie saat absen.',
                    'kategori' => 'Verifikasi',
                    'prioritas' => 'Tinggi'
                ],
                [
                    'id' => 11,
                    'fitur' => '📄 Export Excel / PDF Laporan Absensi',
                    'penjelasan' => 'Admin dapat unduh rekap harian/bulanan.',
                    'kategori' => 'Export',
                    'prioritas' => 'Sedang'
                ],
                [
                    'id' => 12,
                    'fitur' => '📈 Analitik Keterlambatan & Kehadiran',
                    'penjelasan' => 'Menampilkan grafik dan metrik performa kehadiran.',
                    'kategori' => 'Analitik',
                    'prioritas' => 'Sedang'
                ],
                [
                    'id' => 13,
                    'fitur' => '🏢 Support Multi-Lokasi',
                    'penjelasan' => 'Cocok untuk perusahaan dengan beberapa cabang atau lokasi kerja.',
                    'kategori' => 'Skalabilitas',
                    'prioritas' => 'Sedang'
                ],
                [
                    'id' => 14,
                    'fitur' => '📱 Mobile App (Android/iOS)',
                    'penjelasan' => 'Aplikasi ringan dengan izin lokasi dan latar belakang berjalan.',
                    'kategori' => 'Platform',
                    'prioritas' => 'Tinggi'
                ],
                [
                    'id' => 15,
                    'fitur' => '🔍 GPS Spoofing Detection',
                    'penjelasan' => 'Sistem mendeteksi jika ada manipulasi lokasi (fake GPS).',
                    'kategori' => 'Keamanan',
                    'prioritas' => 'Tinggi'
                ],
            ]
        ];
    }
}