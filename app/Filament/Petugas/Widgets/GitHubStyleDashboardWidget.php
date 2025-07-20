<?php

namespace App\Filament\Petugas\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class GitHubStyleDashboardWidget extends Widget
{
    protected static string $view = 'filament.petugas.widgets.github-style-dashboard-widget';
    
    protected static ?int $sort = 1;
    
    protected int | string | array $columnSpan = 'full';
    
    protected static bool $isLazy = false;
    
    public function getDashboardData(): array
    {
        return [
            'user' => [
                'name' => Auth::user()->name ?? 'Petugas',
                'avatar' => 'https://ui-avatars.com/api/?name=' . urlencode(Auth::user()->name ?? 'P') . '&background=f59e0b&color=fff&rounded=true',
                'role' => 'Healthcare Staff',
                'location' => 'Klinik Dokterku'
            ],
            'stats' => [
                'today' => [
                    'patients' => 15,
                    'procedures' => 23,
                    'revenue' => 2750000,
                    'efficiency' => 87.5
                ],
                'weekly' => [
                    'patients' => [12, 15, 18, 14, 16, 19, 15],
                    'procedures' => [18, 23, 25, 21, 24, 27, 23],
                    'revenue' => [2100000, 2750000, 2950000, 2400000, 2650000, 3100000, 2750000]
                ]
            ],
            'activities' => [
                ['type' => 'patient', 'message' => 'Pasien baru terdaftar: Ahmad Rizki', 'time' => '2 menit lalu'],
                ['type' => 'procedure', 'message' => 'Tindakan pembersihan karang gigi selesai', 'time' => '5 menit lalu'],
                ['type' => 'payment', 'message' => 'Pembayaran Rp 350.000 berhasil', 'time' => '8 menit lalu'],
                ['type' => 'appointment', 'message' => 'Jadwal baru ditambahkan untuk besok', 'time' => '12 menit lalu']
            ],
            'quickActions' => [
                ['title' => 'Daftar Pasien Baru', 'icon' => 'user-plus', 'url' => '/petugas/pasiens/create', 'color' => 'emerald'],
                ['title' => 'Input Tindakan', 'icon' => 'clipboard-list', 'url' => '/petugas/tindakans/create', 'color' => 'blue'],
                ['title' => 'Catat Pendapatan', 'icon' => 'currency-dollar', 'url' => '/petugas/pendapatan-harians/create', 'color' => 'amber'],
                ['title' => 'Lihat Laporan', 'icon' => 'chart-bar', 'url' => '/petugas', 'color' => 'purple']
            ]
        ];
    }
}