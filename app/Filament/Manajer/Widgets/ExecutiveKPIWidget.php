<?php

namespace App\Filament\Manajer\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Pasien;
use App\Models\Pendapatan;
use App\Models\Pegawai;
use App\Models\PermohonanCuti;

class ExecutiveKPIWidget extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Patients', Pasien::count())
                ->description('Registered patients')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),
                
            Stat::make('Monthly Revenue', 'Rp ' . number_format(Pendapatan::whereMonth('created_at', now()->month)->sum('nominal')))
                ->description('This month\'s income')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),
                
            Stat::make('Active Staff', Pegawai::count())
                ->description('Total employees')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),
                
            Stat::make('Pending Approvals', PermohonanCuti::where('status', 'pending')->count())
                ->description('Awaiting your approval')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),
        ];
    }
}