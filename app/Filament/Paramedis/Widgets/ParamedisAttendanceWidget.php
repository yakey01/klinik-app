<?php

namespace App\Filament\Paramedis\Widgets;

use App\Models\Attendance;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Carbon\Carbon;
use App\Helpers\AccurateTimeHelper;

class ParamedisAttendanceWidget extends BaseWidget
{
    protected static ?string $pollingInterval = null; // Disable polling; // Sync with other widgets
    
    public static function canView(): bool
    {
        return auth()->check() && auth()->user()->role?->name === 'paramedis';
    }

    protected function getStats(): array
    {
        $user = auth()->user();
        
        // Today's attendance (using accurate time)
        $today = AccurateTimeHelper::today();
        $todayAttendance = Attendance::where('user_id', $user->id)
            ->where('date', $today)
            ->first();
        
        // This month statistics (using accurate time)
        $thisMonth = AccurateTimeHelper::startOfMonth();
        $monthlyAttendance = Attendance::where('user_id', $user->id)
            ->whereDate('date', '>=', $thisMonth)
            ->count();
        
        // Working days this month (excluding weekends)
        $workingDays = 0;
        $current = $thisMonth->copy();
        while ($current->lte(AccurateTimeHelper::today())) {
            if ($current->isWeekday()) {
                $workingDays++;
            }
            $current->addDay();
        }
        
        $attendanceRate = $workingDays > 0 ? round(($monthlyAttendance / $workingDays) * 100, 1) : 0;
        
        // Status for today
        $todayStatus = 'Belum Absen';
        $statusColor = 'warning';
        $statusIcon = 'heroicon-m-clock';
        
        if ($todayAttendance) {
            if ($todayAttendance->time_out) {
                $todayStatus = 'Sudah Pulang';
                $statusColor = 'success';
                $statusIcon = 'heroicon-m-check-circle';
            } else {
                $todayStatus = 'Sudah Masuk';
                $statusColor = 'info';
                $statusIcon = 'heroicon-m-play-circle';
            }
        }
        
        return [
            Stat::make('Status Hari Ini', $todayStatus)
                ->description($todayAttendance ? 
                    'Masuk: ' . Carbon::parse($todayAttendance->time_in)->format('H:i') . 
                    ($todayAttendance->time_out ? ' | Pulang: ' . Carbon::parse($todayAttendance->time_out)->format('H:i') : '') 
                    : 'Belum melakukan presensi hari ini')
                ->descriptionIcon($statusIcon)
                ->color($statusColor)
                ->chart([1, 3, 2, 4, 3, 5, 4]),
                
            Stat::make('Kehadiran Bulan Ini', $monthlyAttendance)
                ->description($workingDays . ' hari kerja | Rate: ' . $attendanceRate . '%')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color($attendanceRate >= 90 ? 'success' : ($attendanceRate >= 75 ? 'warning' : 'danger'))
                ->chart(array_fill(0, 7, $monthlyAttendance)),
                
            Stat::make('Durasi Kerja Hari Ini', 
                $todayAttendance && $todayAttendance->formatted_work_duration ? 
                $todayAttendance->formatted_work_duration : 'Belum Selesai')
                ->description($todayAttendance ? 
                    'Status: ' . ($todayAttendance->status === 'late' ? '⚠️ Terlambat' : '✅ Tepat Waktu') 
                    : 'Belum check-in')
                ->descriptionIcon('heroicon-m-clock')
                ->color($todayAttendance && $todayAttendance->status === 'late' ? 'warning' : 'info')
                ->chart([8, 7, 8, 7, 8, 6, 8]),
        ];
    }
    
    protected function getColumns(): int
    {
        return 3;
    }
    
    public function getDisplayName(): string
    {
        return "Status Presensi Paramedis";
    }
    
    protected static bool $isLazy = false;
    
    protected int | string | array $columnSpan = 'full';
}