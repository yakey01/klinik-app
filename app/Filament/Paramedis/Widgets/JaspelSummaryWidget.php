<?php

namespace App\Filament\Paramedis\Widgets;

use App\Models\Jaspel;
use App\Models\Tindakan;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Carbon\Carbon;
use App\Helpers\AccurateTimeHelper;

class JaspelSummaryWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '30s'; // Sync with other widgets
    
    public static function canView(): bool
    {
        return auth()->check() && auth()->user()->role?->name === 'paramedis';
    }

    protected function getStats(): array
    {
        $user = auth()->user();
        $today = AccurateTimeHelper::today();
        $thisMonth = AccurateTimeHelper::startOfMonth();
        
        // Today's Jaspel (using dummy data for demo)
        $todayJaspel = rand(50000, 150000);
        
        // This month's Jaspel (using dummy data for demo)
        $monthlyJaspel = rand(500000, 1500000);
        
        // Count tindakan today (dummy data)
        $todayTindakan = rand(2, 8);
        
        // Monthly tindakan (dummy data)
        $monthlyTindakan = rand(20, 60);
        
        return [
            Stat::make('Jaspel Hari Ini', 'Rp ' . number_format($todayJaspel, 0, ',', '.'))
                ->description($todayTindakan . ' tindakan hari ini')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('success')
                ->chart([100, 200, 150, 300, 250, 400, $todayJaspel/1000]),
                
            Stat::make('Jaspel Bulan Ini', 'Rp ' . number_format($monthlyJaspel, 0, ',', '.'))
                ->description($monthlyTindakan . ' total tindakan')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('info')
                ->chart(array_fill(0, 7, $monthlyJaspel/10000)),
                
            Stat::make('Rata-rata Harian', $monthlyTindakan > 0 ? 'Rp ' . number_format($monthlyJaspel / Carbon::now()->day, 0, ',', '.') : 'Rp 0')
                ->description('Performa bulan ini')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color($monthlyJaspel > 500000 ? 'success' : ($monthlyJaspel > 200000 ? 'warning' : 'danger'))
                ->chart([50, 75, 100, 125, 100, 150, 175]),
        ];
    }
    
    protected function getColumns(): int
    {
        return 3;
    }
    
    public function getDisplayName(): string
    {
        return "Ringkasan Jaspel Paramedis";
    }
    
    protected static bool $isLazy = false;
    
    protected int | string | array $columnSpan = 'full';
}