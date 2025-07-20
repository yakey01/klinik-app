<?php

namespace App\Filament\Paramedis\Widgets;

use App\Models\Attendance;
use Filament\Widgets\Widget;
use Carbon\Carbon;

class AttendanceLabWidget extends Widget
{
    protected static string $view = 'filament.paramedis.widgets.attendance-lab';
    
    public static function canView(): bool
    {
        return auth()->check() && auth()->user()->role?->name === 'paramedis';
    }
    
    protected int | string | array $columnSpan = 'full';
    
    public function getViewData(): array
    {
        $user = auth()->user();
        $today = Carbon::today();
        
        // Check today's attendance
        $todayAttendance = Attendance::where('user_id', $user->id)
            ->where('date', $today)
            ->first();
            
        return [
            'todayAttendance' => $todayAttendance,
            'user' => $user,
            'canCheckin' => !$todayAttendance,
            'canCheckout' => $todayAttendance && !$todayAttendance->time_out,
        ];
    }
}