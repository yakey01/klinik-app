<?php

namespace App\Filament\Dokter\Widgets;

use App\Models\Attendance;
use App\Models\Tindakan;
use App\Models\Jaspel;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class PresensiStatsWidget extends BaseWidget
{
    protected static ?int $sort = 3;
    
    protected int | string | array $columnSpan = [
        'md' => 2,
        'xl' => 2,
    ];

    protected function getStats(): array
    {
        $userId = Auth::id();
        $today = Carbon::today();
        $currentMonth = Carbon::now()->startOfMonth();
        
        // Today's procedures count
        $tindakanHariIni = Tindakan::where('dokter_id', $userId)
            ->whereDate('created_at', $today)
            ->count();
            
        // This month's procedures count
        $tindakanBulanIni = Tindakan::where('dokter_id', $userId)
            ->whereDate('created_at', '>=', $currentMonth)
            ->count();
            
        // This month's total JASPEL
        $jaspelBulanIni = Jaspel::where('user_id', $userId)
            ->whereDate('created_at', '>=', $currentMonth)
            ->sum('total_jaspel');
            
        // This month's attendance count
        $presensiCount = Attendance::where('user_id', $userId)
            ->whereDate('created_at', '>=', $currentMonth)
            ->where('status', 'present')
            ->count();
            
        // Calculate attendance percentage (assuming 22 working days per month)
        $workingDaysThisMonth = Carbon::now()->day; // Days passed this month
        $attendancePercentage = $workingDaysThisMonth > 0 ? round(($presensiCount / $workingDaysThisMonth) * 100, 1) : 0;

        return [
            Stat::make('🩺 Tindakan Hari Ini', $tindakanHariIni)
                ->description('Tindakan yang dilakukan hari ini')
                ->descriptionIcon('heroicon-m-clipboard-document-check')
                ->color('success'),

            Stat::make('📊 Tindakan Bulan Ini', $tindakanBulanIni)
                ->description('Total tindakan bulan ini')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('info'),

            Stat::make('💰 JASPEL Bulan Ini', 'Rp ' . number_format($jaspelBulanIni, 0, ',', '.'))
                ->description('Total pendapatan JASPEL')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('warning'),

            Stat::make('📅 Kehadiran', $attendancePercentage . '%')
                ->description($presensiCount . ' dari ' . $workingDaysThisMonth . ' hari')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color($attendancePercentage >= 90 ? 'success' : ($attendancePercentage >= 75 ? 'warning' : 'danger')),
        ];
    }

    protected function getColumns(): int
    {
        return 2;
    }
}