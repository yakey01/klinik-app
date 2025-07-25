<?php

namespace App\Filament\Verifikator\Widgets;

use App\Models\Pasien;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PasienVerificationStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $pendingCount = Pasien::where('status', 'pending')->count();
        $verifiedCount = Pasien::where('status', 'verified')->count();
        $rejectedCount = Pasien::where('status', 'rejected')->count();
        $totalCount = Pasien::count();
        
        $pendingToday = Pasien::where('status', 'pending')
            ->whereDate('created_at', today())
            ->count();
        
        $verifiedToday = Pasien::where('status', 'verified')
            ->whereDate('verified_at', today())
            ->count();

        return [
            Stat::make('Menunggu Verifikasi', $pendingCount)
                ->description($pendingToday > 0 ? "{$pendingToday} hari ini" : 'Tidak ada hari ini')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning')
                ->chart([7, 2, 10, 3, 15, 4, 17]),
            
            Stat::make('Total Terverifikasi', $verifiedCount)
                ->description($verifiedToday > 0 ? "{$verifiedToday} hari ini" : 'Tidak ada hari ini')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success')
                ->chart([15, 4, 10, 8, 20, 12, 25]),
            
            Stat::make('Ditolak', $rejectedCount)
                ->description('Total data yang ditolak')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger')
                ->chart([2, 1, 3, 2, 5, 1, 2]),
            
            Stat::make('Total Pasien', $totalCount)
                ->description('Semua data pasien')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary')
                ->chart([10, 15, 20, 25, 30, 35, 40]),
        ];
    }
}