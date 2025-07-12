<?php

namespace App\Filament\Manajer\Widgets;

use App\Models\PendapatanHarian;
use App\Models\PengeluaranHarian;
use App\Models\Pasien;
use App\Models\Tindakan;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ManajerSummaryWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $today = Carbon::today();

        // Total pendapatan hari ini (hanya yang sudah divalidasi)
        $totalPendapatanHariIni = PendapatanHarian::whereDate('tanggal_input', $today)
            ->where('status_validasi', 'disetujui')
            ->sum('nominal');

        // Total pengeluaran hari ini (hanya yang sudah divalidasi)
        $totalPengeluaranHariIni = PengeluaranHarian::whereDate('tanggal_input', $today)
            ->where('status_validasi', 'disetujui')
            ->sum('nominal');

        // Jumlah pasien berdasarkan jenis tindakan hari ini
        $pasienUmumHariIni = Tindakan::whereDate('created_at', $today)
            ->whereHas('jenisTindakan', function ($query) {
                $query->where('kategori', 'umum');
            })
            ->distinct('pasien_id')
            ->count('pasien_id');

        $pasienGigiHariIni = Tindakan::whereDate('created_at', $today)
            ->whereHas('jenisTindakan', function ($query) {
                $query->where('kategori', 'gigi');
            })
            ->distinct('pasien_id')
            ->count('pasien_id');

        $pasienBpjsHariIni = Tindakan::whereDate('created_at', $today)
            ->whereHas('jenisTindakan', function ($query) {
                $query->where('kategori', 'bpjs');
            })
            ->distinct('pasien_id')
            ->count('pasien_id');

        // Hitung laba/rugi
        $labaRugiHariIni = $totalPendapatanHariIni - $totalPengeluaranHariIni;

        return [
            Stat::make('💰 Total Pendapatan Hari Ini', 'Rp ' . number_format($totalPendapatanHariIni, 0, ',', '.'))
                ->description('Pendapatan yang sudah divalidasi')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),

            Stat::make('💸 Total Pengeluaran Hari Ini', 'Rp ' . number_format($totalPengeluaranHariIni, 0, ',', '.'))
                ->description('Pengeluaran yang sudah disetujui')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('danger'),

            Stat::make('👥 Jumlah Pasien Umum', $pasienUmumHariIni)
                ->description('Pasien umum hari ini')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('info'),

            Stat::make('🦷 Jumlah Pasien Gigi', $pasienGigiHariIni)
                ->description('Pasien gigi hari ini')
                ->descriptionIcon('heroicon-m-face-smile')
                ->color('warning'),

            Stat::make('💳 Jumlah Pasien BPJS', $pasienBpjsHariIni)
                ->description('Pasien BPJS hari ini')
                ->descriptionIcon('heroicon-m-identification')
                ->color('primary'),

            Stat::make('⚖️ Laba / Rugi Hari Ini', 'Rp ' . number_format($labaRugiHariIni, 0, ',', '.'))
                ->description($labaRugiHariIni >= 0 ? 'Profit hari ini' : 'Loss hari ini')
                ->descriptionIcon($labaRugiHariIni >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($labaRugiHariIni >= 0 ? 'success' : 'danger'),
        ];
    }

    protected function getColumns(): int
    {
        return 3; // 2 rows, 3 columns each as specified in the requirements
    }

    public static function canView(): bool
    {
        return auth()->check() && auth()->user()->role?->name === 'manajer';
    }
}