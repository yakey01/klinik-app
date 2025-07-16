<?php

namespace App\Filament\Manajer\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Pasien;
use App\Models\Tindakan;
use App\Models\Pegawai;

class OperationsDashboardWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $todayPatients = Pasien::whereDate('created_at', today())->count();
        $weeklyPatients = Pasien::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count();
        $todayProcedures = Tindakan::whereDate('created_at', today())->count();
        $avgWaitTime = $this->calculateAverageWaitTime();
        $activeStaff = Pegawai::where('updated_at', '>=', now()->subDays(7))->count();
        $totalStaff = Pegawai::count();
        
        return [
            Stat::make('Today\'s Patients', $todayPatients)
                ->description('New registrations today')
                ->descriptionIcon('heroicon-m-calendar')
                ->color('info'),
                
            Stat::make('Today\'s Procedures', $todayProcedures)
                ->description('Procedures completed today')
                ->descriptionIcon('heroicon-m-clipboard-document-check')
                ->color('success'),

            Stat::make('Avg Wait Time', $avgWaitTime . ' min')
                ->description('Average patient wait time')
                ->descriptionIcon('heroicon-m-clock')
                ->color($avgWaitTime > 30 ? 'warning' : 'success'),
                
            Stat::make('Staff Utilization', round(($activeStaff / max($totalStaff, 1)) * 100) . '%')
                ->description("{$activeStaff}/{$totalStaff} staff active this week")
                ->descriptionIcon('heroicon-m-users')
                ->color($activeStaff / max($totalStaff, 1) > 0.8 ? 'success' : 'warning'),
        ];
    }

    private function calculateAverageWaitTime(): int
    {
        // Simplified calculation based on procedures per day
        $todayProcedures = Tindakan::whereDate('created_at', today())->count();
        $estimatedWaitTime = $todayProcedures > 0 ? min(60, $todayProcedures * 2) : 0;
        
        return $estimatedWaitTime;
    }
}