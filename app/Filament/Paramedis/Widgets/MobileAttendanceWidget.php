<?php

namespace App\Filament\Paramedis\Widgets;

use App\Models\Attendance;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class MobileAttendanceWidget extends Widget
{
    protected static string $view = 'filament.paramedis.widgets.mobile-attendance-widget';
    protected static ?int $sort = 1;
    
    public function getViewData(): array
    {
        $user = Auth::user();
        $today = now();
        
        $attendance = Attendance::where('user_id', $user->id)
            ->where('date', $today->toDateString())
            ->first();
            
        return [
            'attendance' => $attendance,
            'canCheckIn' => !$attendance,
            'canCheckOut' => $attendance && !$attendance->time_out,
            'currentTime' => now()->format('H:i'),
            'currentDate' => now()->format('l, d F Y'),
        ];
    }
}