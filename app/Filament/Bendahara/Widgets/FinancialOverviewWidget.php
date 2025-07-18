<?php

namespace App\Filament\Bendahara\Widgets;

use App\Models\Pendapatan;
use App\Models\Pengeluaran;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class FinancialOverviewWidget extends BaseWidget
{
    protected function getStats(): array
    {
        // Get basic financial stats
        $pendapatanPending = Pendapatan::where('status_validasi', 'pending')->count();
        $pengeluaranPending = Pengeluaran::where('status_validasi', 'pending')->count();
        $totalPending = $pendapatanPending + $pengeluaranPending;
        
        $pendapatanToday = Pendapatan::whereDate('tanggal', today())->count();
        $pengeluaranToday = Pengeluaran::whereDate('tanggal', today())->count();
        
        $pendapatanValue = Pendapatan::where('status_validasi', 'disetujui')->sum('nominal');
        $pengeluaranValue = Pengeluaran::where('status_validasi', 'disetujui')->sum('nominal');
        $netCashFlow = $pendapatanValue - $pengeluaranValue;
        
        return [
            Stat::make('🕐 Total Tertunda', $totalPending)
                ->description('Menunggu validasi')
                ->color($totalPending > 10 ? 'warning' : 'success'),
                
            Stat::make('💰 Penerimaan Tertunda', $pendapatanPending)
                ->description('Transaksi penerimaan')
                ->color('success'),
                
            Stat::make('💸 Pengeluaran Tertunda', $pengeluaranPending)
                ->description('Transaksi pengeluaran')
                ->color('danger'),
                
            Stat::make('📊 Arus Kas Bersih', 'Rp ' . number_format($netCashFlow / 1000000, 1) . 'M')
                ->description('Transaksi disetujui')
                ->color($netCashFlow > 0 ? 'success' : 'danger'),
                
            Stat::make('📅 Total Hari Ini', $pendapatanToday + $pengeluaranToday)
                ->description('Transaksi hari ini')
                ->color('info'),
        ];
    }
}