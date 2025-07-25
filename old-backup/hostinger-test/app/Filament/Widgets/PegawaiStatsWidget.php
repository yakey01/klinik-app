<?php

namespace App\Filament\Widgets;

use App\Models\Pegawai;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PegawaiStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $totalPegawai = Pegawai::count();
        $paramedis = Pegawai::where('jenis_pegawai', 'Paramedis')->count();
        $nonParamedis = Pegawai::where('jenis_pegawai', 'Non-Paramedis')->count();
        $aktif = Pegawai::where('aktif', true)->count();

        return [
            Stat::make('Total Pegawai', $totalPegawai)
                ->description('Total semua karyawan')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),

            Stat::make('Paramedis', $paramedis)
                ->description('Tenaga medis')
                ->descriptionIcon('heroicon-m-heart')
                ->color('success'),

            Stat::make('Non-Paramedis', $nonParamedis)
                ->description('Tenaga non-medis')
                ->descriptionIcon('heroicon-m-briefcase')
                ->color('info'),

            Stat::make('Status Aktif', $aktif)
                ->description('Pegawai yang aktif')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('warning'),
        ];
    }
}
