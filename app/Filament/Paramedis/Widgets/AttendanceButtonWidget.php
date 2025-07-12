<?php

namespace App\Filament\Paramedis\Widgets;

use App\Models\Attendance;
use Filament\Widgets\Widget;
use Filament\Notifications\Notification;
use Carbon\Carbon;
use App\Helpers\AccurateTimeHelper;

class AttendanceButtonWidget extends Widget
{
    protected static string $view = 'filament.paramedis.widgets.attendance-button-clean';
    
    protected static ?string $pollingInterval = '30s'; // Auto refresh every 30 seconds
    
    public static function canView(): bool
    {
        return auth()->check() && auth()->user()->role?->name === 'paramedis';
    }
    
    protected int | string | array $columnSpan = 'full';
    
    public function getViewData(): array
    {
        $user = auth()->user();
        $today = AccurateTimeHelper::today();
        $currentTime = AccurateTimeHelper::now();
        
        // Check today's attendance
        $todayAttendance = Attendance::where('user_id', $user->id)
            ->where('date', $today)
            ->first();
            
        return [
            'todayAttendance' => $todayAttendance,
            'user' => $user,
            'canCheckin' => !$todayAttendance,
            'canCheckout' => $todayAttendance && !$todayAttendance->time_out,
            'currentTime' => $currentTime,
        ];
    }
    
    public function checkin()
    {
        $user = auth()->user();
        $today = AccurateTimeHelper::today();
        
        // Check if already checked in
        $existingAttendance = Attendance::where('user_id', $user->id)
            ->where('date', $today)
            ->first();
            
        if ($existingAttendance) {
            Notification::make()
                ->title('Sudah Absen Masuk')
                ->body('Anda sudah melakukan absen masuk hari ini')
                ->warning()
                ->send();
            return;
        }
        
        $now = AccurateTimeHelper::now();
        $workStartTime = Carbon::createFromTime(8, 0, 0);
        $status = $now->gt($workStartTime) ? 'late' : 'present';
        
        // Create attendance record
        Attendance::create([
            'user_id' => $user->id,
            'date' => $today,
            'time_in' => $now->format('H:i:s'),
            'status' => $status,
            'latlon_in' => '-6.2088,106.8456', // Jakarta coordinates (demo)
            'location_name_in' => 'Klinik Dokterku',
            'device_info' => 'Dashboard Paramedis',
            'device_id' => 'WEB_' . $user->id,
            'device_fingerprint' => 'web_dashboard',
            'notes' => 'Check-in via Dashboard Paramedis pada ' . $now->format('d/m/Y H:i:s')
        ]);
        
        Notification::make()
            ->title('✅ Absen Masuk Berhasil!')
            ->body("Waktu masuk: {$now->format('H:i')} | Status: " . 
                   ($status === 'late' ? 'Terlambat' : 'Tepat Waktu'))
            ->success()
            ->send();
    }
    
    public function checkout()
    {
        $user = auth()->user();
        $today = AccurateTimeHelper::today();
        
        // Get today's attendance
        $attendance = Attendance::where('user_id', $user->id)
            ->where('date', $today)
            ->whereNotNull('time_in')
            ->whereNull('time_out')
            ->first();
            
        if (!$attendance) {
            Notification::make()
                ->title('Tidak Ada Data Masuk')
                ->body('Anda belum melakukan absen masuk hari ini')
                ->danger()
                ->send();
            return;
        }
        
        $now = AccurateTimeHelper::now();
        $timeIn = Carbon::parse($attendance->time_in);
        $workDuration = $now->diffForHumans($timeIn, true);
        
        // Update attendance record
        $attendance->update([
            'time_out' => $now->format('H:i:s'),
            'latlon_out' => '-6.2088,106.8456', // Jakarta coordinates (demo)
            'location_name_out' => 'Klinik Dokterku',
            'notes' => $attendance->notes . "\nCheck-out via Dashboard Paramedis pada " . $now->format('d/m/Y H:i:s')
        ]);
        
        Notification::make()
            ->title('✅ Absen Pulang Berhasil!')
            ->body("Waktu pulang: {$now->format('H:i')} | Durasi kerja: {$workDuration}")
            ->success()
            ->send();
    }
}