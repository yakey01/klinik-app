<?php

namespace App\Filament\Manajer\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Pasien;

class OperationsDashboardWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $todayPatients = Pasien::whereDate('created_at', today())->count();
        $weeklyPatients = Pasien::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count();
        
        return [
            Stat::make('Today\'s Patients', $todayPatients)
                ->description('New registrations today')
                ->descriptionIcon('heroicon-m-calendar')
                ->color('info'),
                
            Stat::make('Weekly Patients', $weeklyPatients)
                ->description('This week\'s total')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('success'),
        ];
    }
}