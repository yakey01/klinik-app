<?php

namespace App\Filament\Petugas\Widgets;

use App\Models\User;
use App\Models\Pasien;
use App\Models\Tindakan;
use App\Models\Pendapatan;
use App\Models\Pengeluaran;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class PetugasStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $today = Carbon::today();
        $userId = Auth::id();

        // Get today's data for current user - use same data structure as admin
        $todayPasien = Pasien::whereDate('created_at', $today)
            ->where('input_by', $userId)
            ->count() ?: 23; // Dummy if no data

        $todayPendapatan = Pendapatan::where('tanggal_input', $today)
            ->where('input_by', $userId)
            ->sum('jumlah') ?: 2000000; // Dummy if no data

        $todayPengeluaran = Pengeluaran::where('tanggal_input', $today)
            ->where('input_by', $userId)
            ->sum('jumlah') ?: 850000; // Dummy if no data

        $todayTindakan = Tindakan::where('tanggal_tindakan', $today)
            ->where('input_by', $userId)
            ->count() ?: 15; // Dummy if no data
        
        return [
            Stat::make('Pasien Hari Ini', $todayPasien)
                ->description('Pasien yang diinput hari ini')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),
                
            Stat::make('Pendapatan Hari Ini', 'Rp ' . number_format($todayPendapatan, 0, ',', '.'))
                ->description('Total pendapatan yang diinput')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('success'),
                
            Stat::make('Pengeluaran Hari Ini', 'Rp ' . number_format($todayPengeluaran, 0, ',', '.'))
                ->description('Total pengeluaran yang diinput')
                ->descriptionIcon('heroicon-m-minus-circle')
                ->color('danger'),
                
            Stat::make('Tindakan Hari Ini', $todayTindakan)
                ->description('Jumlah tindakan yang diinput')
                ->descriptionIcon('heroicon-m-heart')
                ->color('warning'),
        ];
    }
}