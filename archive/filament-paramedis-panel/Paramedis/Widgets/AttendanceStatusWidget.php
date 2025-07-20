<?php

namespace App\Filament\Paramedis\Widgets;

use Filament\Widgets\Widget;
use Carbon\Carbon;
use App\Models\Attendance;

class AttendanceStatusWidget extends Widget
{
    protected static string $view = 'filament.paramedis.widgets.attendance-status-compact';
    
    protected static ?string $pollingInterval = null;
    
    public static function canView(): bool
    {
        return auth()->check() && auth()->user()->role?->name === 'paramedis';
    }
    
    protected int | string | array $columnSpan = 'full';
    
    // Removed check-in/out methods - will be handled in separate Presensi page
    
    public function getViewData(): array
    {
        $user = auth()->user();
        $today = Carbon::now('Asia/Jakarta')->startOfDay();
        
        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('date', $today)
            ->first();
            
        $hasCheckedIn = $attendance && $attendance->check_in;
        $hasCheckedOut = $attendance && $attendance->check_out;
        
        // Status calculation
        if (!$hasCheckedIn) {
            $status = 'Belum Presensi';
            $statusColor = 'text-red-600';
            $statusIcon = '❌';
        } elseif ($hasCheckedIn && !$hasCheckedOut) {
            $status = 'Sedang Bekerja';
            $statusColor = 'text-green-600';
            $statusIcon = '✅';
        } else {
            $status = 'Presensi Selesai';
            $statusColor = 'text-blue-600';
            $statusIcon = '🏁';
        }
        
        return [
            'attendance' => $attendance,
            'hasCheckedIn' => $hasCheckedIn,
            'hasCheckedOut' => $hasCheckedOut,
            'status' => $status,
            'statusColor' => $statusColor,
            'statusIcon' => $statusIcon,
            'checkinTime' => $hasCheckedIn ? $attendance->check_in->format('H:i') : null,
            'checkoutTime' => $hasCheckedOut ? $attendance->check_out->format('H:i') : null,
            'currentTime' => Carbon::now('Asia/Jakarta')->format('H:i:s'),
        ];
    }
}