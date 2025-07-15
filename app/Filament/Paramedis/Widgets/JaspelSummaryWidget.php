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
    protected static ?string $pollingInterval = '30s'; // Real-time updates with real data
    
    public static function canView(): bool
    {
        return auth()->check() && auth()->user()->role?->name === 'paramedis';
    }

    protected function getStats(): array
    {
        $user = auth()->user();
        $today = AccurateTimeHelper::today();
        $thisMonth = AccurateTimeHelper::startOfMonth();
        
        // Today's Jaspel (real data)
        $todayJaspel = Jaspel::where('user_id', $user->id)
            ->whereDate('tanggal', $today)
            ->where('status', 'approved')
            ->sum('jumlah') ?? 0;
        
        // This month's Jaspel (real data)
        $monthlyJaspel = Jaspel::where('user_id', $user->id)
            ->whereMonth('tanggal', $thisMonth->month)
            ->whereYear('tanggal', $thisMonth->year)
            ->where('status', 'approved')
            ->sum('jumlah') ?? 0;
        
        // Count tindakan today (real data)
        $todayTindakan = Tindakan::where('user_id', $user->id)
            ->whereDate('tanggal', $today)
            ->count();
        
        // Monthly tindakan (real data)
        $monthlyTindakan = Tindakan::where('user_id', $user->id)
            ->whereMonth('tanggal', $thisMonth->month)
            ->whereYear('tanggal', $thisMonth->year)
            ->count();
        
        // Generate real trend data for last 7 days
        $weeklyJaspelTrend = [];
        $weeklyTindakanTrend = [];
        $weeklyAvgTrend = [];
        
        for ($i = 6; $i >= 0; $i--) {
            $date = $today->copy()->subDays($i);
            
            $dailyJaspel = Jaspel::where('user_id', $user->id)
                ->whereDate('tanggal', $date)
                ->where('status', 'approved')
                ->sum('jumlah') ?? 0;
                
            $dailyTindakan = Tindakan::where('user_id', $user->id)
                ->whereDate('tanggal', $date)
                ->count();
            
            $weeklyJaspelTrend[] = $dailyJaspel / 1000; // Scale for chart
            $weeklyTindakanTrend[] = $dailyTindakan;
            $weeklyAvgTrend[] = $dailyTindakan > 0 ? ($dailyJaspel / $dailyTindakan) / 1000 : 0;
        }

        return [
            Stat::make('Jaspel Hari Ini', 'Rp ' . number_format($todayJaspel, 0, ',', '.'))
                ->description($todayTindakan . ' tindakan hari ini')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color($todayJaspel > 100000 ? 'success' : ($todayJaspel > 50000 ? 'warning' : 'info'))
                ->chart($weeklyJaspelTrend),
                
            Stat::make('Jaspel Bulan Ini', 'Rp ' . number_format($monthlyJaspel, 0, ',', '.'))
                ->description($monthlyTindakan . ' total tindakan')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color($monthlyJaspel > 1000000 ? 'success' : ($monthlyJaspel > 500000 ? 'warning' : 'info'))
                ->chart($weeklyTindakanTrend),
                
            Stat::make('Rata-rata Harian', $monthlyTindakan > 0 ? 'Rp ' . number_format($monthlyJaspel / Carbon::now()->day, 0, ',', '.') : 'Rp 0')
                ->description('Performa bulan ini')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color($monthlyJaspel > 500000 ? 'success' : ($monthlyJaspel > 200000 ? 'warning' : 'danger'))
                ->chart($weeklyAvgTrend),
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