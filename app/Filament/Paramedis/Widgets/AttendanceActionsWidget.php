<?php

namespace App\Filament\Paramedis\Widgets;

use App\Models\Attendance;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Carbon\Carbon;
use App\Helpers\AccurateTimeHelper;

class AttendanceActionsWidget extends BaseWidget
{
    protected static ?string $pollingInterval = null; // Disable polling; // Sync with other widgets
    
    public static function canView(): bool
    {
        return auth()->check() && auth()->user()->role?->name === 'paramedis';
    }
    
    protected function getStats(): array
    {
        $user = auth()->user();
        $today = AccurateTimeHelper::today();
        
        // Check today's attendance (using accurate time)
        $todayAttendance = Attendance::where('user_id', $user->id)
            ->where('date', $today)
            ->first();
        
        $now = AccurateTimeHelper::now();
        
        // Status and action for check-in
        if (!$todayAttendance) {
            $checkinStat = Stat::make('⏰ Belum Absen Masuk', 'Klik untuk Absen')
                ->description('Silakan lakukan presensi masuk')
                ->descriptionIcon('heroicon-m-play-circle')
                ->color('warning')
                ->url('#')
                ->extraAttributes([
                    'onclick' => 'alert("Fitur absen masuk akan segera tersedia")',
                    'style' => 'cursor: pointer; border: 2px dashed #f59e0b;'
                ]);
        } else {
            $timeIn = Carbon::parse($todayAttendance->time_in);
            $checkinStat = Stat::make('✅ Sudah Absen Masuk', $timeIn->format('H:i'))
                ->description('Status: ' . ($todayAttendance->status === 'late' ? '⚠️ Terlambat' : '✅ Tepat Waktu'))
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success');
        }
        
        // Status and action for check-out
        if ($todayAttendance && !$todayAttendance->time_out) {
            $workDuration = $now->diffForHumans(Carbon::parse($todayAttendance->time_in), true);
            $checkoutStat = Stat::make('🚪 Belum Absen Pulang', 'Klik untuk Pulang')
                ->description("Durasi kerja: {$workDuration}")
                ->descriptionIcon('heroicon-m-stop-circle')
                ->color('info')
                ->url('#')
                ->extraAttributes([
                    'onclick' => 'alert("Fitur absen pulang akan segera tersedia")',
                    'style' => 'cursor: pointer; border: 2px dashed #3b82f6;'
                ]);
        } elseif ($todayAttendance && $todayAttendance->time_out) {
            $timeOut = Carbon::parse($todayAttendance->time_out);
            $duration = $timeOut->diffForHumans(Carbon::parse($todayAttendance->time_in), true);
            $checkoutStat = Stat::make('🏠 Sudah Absen Pulang', $timeOut->format('H:i'))
                ->description("Durasi kerja: {$duration}")
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success');
        } else {
            $checkoutStat = Stat::make('⏸️ Absen Masuk Dulu', 'Tidak tersedia')
                ->description('Lakukan absen masuk terlebih dahulu')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('gray');
        }
        
        // Current time stat (using accurate time)
        $currentTimeStat = Stat::make('🕐 Waktu Sekarang', $now->format('H:i:s'))
            ->description($now->format('l, d F Y'))
            ->descriptionIcon('heroicon-m-clock')
            ->color('primary');
        
        return [
            $currentTimeStat,
            $checkinStat,
            $checkoutStat,
        ];
    }
    
    protected function getColumns(): int
    {
        return 3;
    }
    
    protected int | string | array $columnSpan = 'full';
}