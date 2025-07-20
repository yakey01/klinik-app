<?php

namespace App\Filament\Paramedis\Widgets;

use App\Models\Schedule;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class MobileScheduleWidget extends Widget
{
    protected static string $view = 'filament.paramedis.widgets.mobile-schedule-widget';
    protected static ?int $sort = 2;
    
    public function getViewData(): array
    {
        $user = Auth::user();
        $today = now();
        
        // Get this week's schedule
        $schedules = Schedule::where('user_id', $user->id)
            ->whereBetween('date', [
                $today->startOfWeek(),
                $today->endOfWeek()
            ])
            ->with('shift')
            ->orderBy('date')
            ->get();
            
        // Get today's schedule
        $todaySchedule = $schedules->firstWhere('date', $today->toDateString());
        
        return [
            'schedules' => $schedules,
            'todaySchedule' => $todaySchedule,
            'currentDay' => $today->dayOfWeek,
        ];
    }
}