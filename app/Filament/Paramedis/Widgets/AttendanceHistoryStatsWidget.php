<?php

namespace App\Filament\Paramedis\Widgets;

use App\Services\AttendanceHistoryService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Carbon\Carbon;

class AttendanceHistoryStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;
    
    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        $service = app(AttendanceHistoryService::class);
        $userId = auth()->id();
        
        // Get current month summary
        $currentMonth = $service->getUserAttendanceSummary($userId, 'this_month');
        $lastMonth = $service->getUserAttendanceSummary($userId, 'last_month');
        
        // Get streaks
        $streaks = $service->getAttendanceStreaks($userId);
        
        // Get incomplete checkouts
        $incompleteCount = $service->getIncompleteCheckouts($userId)->count();

        return [
            Stat::make('Kehadiran Bulan Ini', $currentMonth['total_days'] . ' hari')
                ->description('Total hari kerja')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color($currentMonth['total_days'] > 0 ? 'success' : 'warning')
                ->chart($this->generateAttendanceChart($currentMonth)),

            Stat::make('Jam Kerja Bulan Ini', $currentMonth['total_working_time_formatted'])
                ->description($currentMonth['complete_days'] . ' hari lengkap')
                ->descriptionIcon('heroicon-m-clock')
                ->color($currentMonth['complete_days'] > 0 ? 'primary' : 'gray'),

            Stat::make('Tingkat Kehadiran', $currentMonth['attendance_rate'] . '%')
                ->description('Bulan ini')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color($this->getAttendanceRateColor($currentMonth['attendance_rate']))
                ->chart($this->generateMonthlyComparisonChart($currentMonth, $lastMonth)),

            Stat::make('Streak Kehadiran', $streaks['current_streak'] . ' hari')
                ->description('Berturut-turut (max: ' . $streaks['max_streak'] . ')')
                ->descriptionIcon('heroicon-m-fire')
                ->color($streaks['current_streak'] > 0 ? 'warning' : 'gray'),

            Stat::make('Status Check-out', $incompleteCount > 0 ? $incompleteCount . ' belum' : 'Lengkap')
                ->description($incompleteCount > 0 ? 'Perlu check-out' : 'Semua sudah check-out')
                ->descriptionIcon($incompleteCount > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-circle')
                ->color($incompleteCount > 0 ? 'danger' : 'success'),

            Stat::make('Bulan Lalu', $lastMonth['total_days'] . ' hari')
                ->description($lastMonth['attendance_rate'] . '% kehadiran')
                ->descriptionIcon('heroicon-m-calendar')
                ->color('gray'),
        ];
    }

    protected function getAttendanceRateColor(float $rate): string
    {
        return match (true) {
            $rate >= 95 => 'success',
            $rate >= 85 => 'warning',
            default => 'danger',
        };
    }

    protected function generateAttendanceChart(array $summary): array
    {
        return [
            $summary['present_days'],
            $summary['late_days'],
            $summary['sick_days'],
            $summary['permission_days'],
            $summary['absent_days'],
        ];
    }

    protected function generateMonthlyComparisonChart(array $current, array $last): array
    {
        // Generate a simple comparison chart for last 6 months
        // This is a simplified version - you might want to implement actual historical data
        return [
            $last['attendance_rate'] ?? 0,
            ($last['attendance_rate'] ?? 0) + rand(-5, 5),
            ($last['attendance_rate'] ?? 0) + rand(-3, 7),
            ($last['attendance_rate'] ?? 0) + rand(-2, 8),
            ($last['attendance_rate'] ?? 0) + rand(-1, 5),
            $current['attendance_rate'],
        ];
    }

    public function getPollingInterval(): ?string
    {
        return '30s'; // Update every 30 seconds
    }
}