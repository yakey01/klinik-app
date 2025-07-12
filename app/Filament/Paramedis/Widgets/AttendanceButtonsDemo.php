<?php

namespace App\Filament\Paramedis\Widgets;

use App\Models\Attendance;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Carbon\Carbon;

class AttendanceButtonsDemo extends BaseWidget
{
    public static function canView(): bool
    {
        return auth()->check() && auth()->user()->role?->name === 'paramedis';
    }
    
    protected function getStats(): array
    {
        $user = auth()->user();
        $today = Carbon::today();
        
        // Check today's attendance
        $todayAttendance = Attendance::where('user_id', $user->id)
            ->where('date', $today)
            ->first();
            
        // Create demo buttons as clickable stats
        $stats = [];
        
        // Check-in button
        if (!$todayAttendance) {
            $stats[] = Stat::make('🟢 TOMBOL CHECK IN', 'Klik untuk Absen Masuk')
                ->description('Face Recognition + GPS Required')
                ->descriptionIcon('heroicon-m-camera')
                ->color('success')
                ->url('#')
                ->extraAttributes([
                    'onclick' => 'showAttendanceLab("checkin")',
                    'style' => 'cursor: pointer; border: 3px solid #22c55e; background: linear-gradient(135deg, #22c55e, #16a34a);'
                ]);
        } else {
            $stats[] = Stat::make('✅ Sudah Check In', Carbon::parse($todayAttendance->time_in)->format('H:i'))
                ->description('Status: ' . ($todayAttendance->status === 'late' ? '⚠️ Terlambat' : '✅ Tepat Waktu'))
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success');
        }
        
        // Check-out button
        if ($todayAttendance && !$todayAttendance->time_out) {
            $workDuration = Carbon::now()->diffForHumans(Carbon::parse($todayAttendance->time_in), true);
            $stats[] = Stat::make('🟠 TOMBOL CHECK OUT', 'Klik untuk Absen Pulang')
                ->description("Durasi kerja: {$workDuration}")
                ->descriptionIcon('heroicon-m-camera')
                ->color('warning')
                ->url('#')
                ->extraAttributes([
                    'onclick' => 'showAttendanceLab("checkout")',
                    'style' => 'cursor: pointer; border: 3px solid #f59e0b; background: linear-gradient(135deg, #f59e0b, #d97706);'
                ]);
        } elseif ($todayAttendance && $todayAttendance->time_out) {
            $timeOut = Carbon::parse($todayAttendance->time_out);
            $duration = $timeOut->diffForHumans(Carbon::parse($todayAttendance->time_in), true);
            $stats[] = Stat::make('✅ Sudah Check Out', $timeOut->format('H:i'))
                ->description("Durasi kerja: {$duration}")
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success');
        } else {
            $stats[] = Stat::make('⏸️ Check In Dulu', 'Tidak Tersedia')
                ->description('Lakukan check-in terlebih dahulu')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('gray');
        }
        
        // Status info
        $stats[] = Stat::make('📱 Attendance Lab', 'Face + GPS Ready')
            ->description('Teknologi: Face-API.js + Geolocation')
            ->descriptionIcon('heroicon-m-star')
            ->color('info')
            ->extraAttributes([
                'style' => 'border: 2px dashed #3b82f6;'
            ]);
        
        return $stats;
    }
    
    protected function getColumns(): int
    {
        return 3;
    }
    
    protected int | string | array $columnSpan = 'full';
}