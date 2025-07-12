<?php

namespace App\Filament\Widgets;

use App\Models\User;
use App\Models\Pasien;
use App\Models\Tindakan;
use App\Models\Pendapatan;
use App\Models\Pengeluaran;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ClinicStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $totalPatients = Pasien::count();
        $totalUsers = User::count();
        $totalProcedures = Tindakan::count();
        $totalIncome = Pendapatan::sum('jumlah');
        $totalExpenses = Pengeluaran::sum('jumlah');
        $netIncome = $totalIncome - $totalExpenses;
        
        $monthlyPatients = Pasien::whereMonth('created_at', now()->month)->count();
        $monthlyProcedures = Tindakan::whereMonth('created_at', now()->month)->count();
        $monthlyIncome = Pendapatan::whereMonth('created_at', now()->month)->sum('jumlah');
        $monthlyExpenses = Pengeluaran::whereMonth('created_at', now()->month)->sum('jumlah');
        
        return [
            Stat::make('Total Pasien', $totalPatients)
                ->description($monthlyPatients . ' pasien bulan ini')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),
                
            Stat::make('Total Pengguna', $totalUsers)
                ->description('Pengguna aktif sistem')
                ->descriptionIcon('heroicon-m-users')
                ->color('info'),
                
            Stat::make('Total Tindakan', $totalProcedures)
                ->description($monthlyProcedures . ' tindakan bulan ini')
                ->descriptionIcon('heroicon-m-heart')
                ->color('warning'),
                
            Stat::make('Total Pendapatan', 'Rp ' . number_format($totalIncome, 0, ',', '.'))
                ->description('Rp ' . number_format($monthlyIncome, 0, ',', '.') . ' bulan ini')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('success'),
                
            Stat::make('Total Pengeluaran', 'Rp ' . number_format($totalExpenses, 0, ',', '.'))
                ->description('Rp ' . number_format($monthlyExpenses, 0, ',', '.') . ' bulan ini')
                ->descriptionIcon('heroicon-m-minus-circle')
                ->color('danger'),
                
            Stat::make('Laba Bersih', 'Rp ' . number_format($netIncome, 0, ',', '.'))
                ->description(($netIncome >= 0 ? 'Profit' : 'Loss') . ' keseluruhan')
                ->descriptionIcon($netIncome >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($netIncome >= 0 ? 'success' : 'danger'),
        ];
    }
}