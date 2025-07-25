<?php

namespace App\Filament\Widgets;

use App\Models\EmployeeCard;
use App\Models\Pegawai;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class EmployeeCardStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $totalCards = EmployeeCard::count();
        $activeCards = EmployeeCard::where('is_active', true)->count();
        $expiredCards = EmployeeCard::expired()->count();
        $totalEmployees = Pegawai::where('aktif', true)->count();
        $employeesWithoutCard = Pegawai::where('aktif', true)
            ->whereDoesntHave('employeeCard')
            ->count();

        return [
            Stat::make('Total Kartu Pegawai', $totalCards)
                ->description('Jumlah total kartu yang dibuat')
                ->descriptionIcon('heroicon-m-identification')
                ->color('primary'),

            Stat::make('Kartu Aktif', $activeCards)
                ->description('Kartu yang masih berlaku')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Kartu Expired', $expiredCards)
                ->description('Kartu yang sudah kedaluwarsa')
                ->descriptionIcon('heroicon-m-clock')
                ->color('danger'),

            Stat::make('Pegawai Tanpa Kartu', $employeesWithoutCard)
                ->description("Dari {$totalEmployees} total pegawai aktif")
                ->descriptionIcon('heroicon-m-user-plus')
                ->color('warning'),
        ];
    }
}