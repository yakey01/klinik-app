<?php

namespace App\Filament\Bendahara\Widgets;

use App\Models\PendapatanHarian;
use App\Models\Pengeluaran;
use App\Models\Tindakan;
use App\Models\Jaspel;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class BendaharaStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $today = Carbon::today();
        $currentMonth = Carbon::now()->startOfMonth();

        // Today's income (approved only)
        $pendapatanHariIni = PendapatanHarian::whereDate('tanggal_input', $today)
            ->where('status_validasi', 'disetujui')
            ->sum('nominal');

        // Today's expenses (approved only)
        $pengeluaranHariIni = Pengeluaran::whereDate('tanggal', $today)
            ->where('status_validasi', 'disetujui')
            ->sum('nominal');

        // Pending procedures for validation
        $tindakanBelumValidasi = Tindakan::where('status', 'pending')
            ->whereDate('created_at', '>=', $currentMonth)
            ->count();

        // Unpaid JASPEL this month
        $jaspelBelumBayar = Jaspel::whereDate('created_at', '>=', $currentMonth)
            ->where('status_pembayaran', '!=', 'paid')
            ->sum('total_jaspel');

        return [
            Stat::make('💰 Pendapatan Hari Ini', 'Rp ' . number_format($pendapatanHariIni, 0, ',', '.'))
                ->description('Pendapatan yang sudah divalidasi')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),

            Stat::make('💸 Pengeluaran Hari Ini', 'Rp ' . number_format($pengeluaranHariIni, 0, ',', '.'))
                ->description('Pengeluaran yang sudah disetujui')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('danger'),

            Stat::make('⏳ Tindakan Belum Divalidasi', $tindakanBelumValidasi)
                ->description('Tindakan menunggu validasi')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('🧮 JASPEL Belum Dibayar', 'Rp ' . number_format($jaspelBelumBayar, 0, ',', '.'))
                ->description('Total JASPEL bulan ini')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('info'),
        ];
    }

    protected function getColumns(): int
    {
        return 4;
    }
}