<?php

namespace App\Filament\Dokter\Widgets;

use Filament\Widgets\Widget;
use App\Models\JadwalJaga;
use App\Models\Dokter;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class ScheduleWidget extends Widget
{
    protected static string $view = 'filament.dokter.widgets.schedule-widget';
    protected static ?int $sort = 3;
    
    protected function getViewData(): array
    {
        $user = Auth::user();
        
        // Cache schedule data for 5 minutes
        $cacheKey = "dokter_schedule_{$user->id}";
        $scheduleData = Cache::remember($cacheKey, 300, function () use ($user) {
            $today = Carbon::today();
            $nextWeek = Carbon::today()->addWeek();
            
            // Get upcoming schedules
            $upcomingSchedules = JadwalJaga::where('pegawai_id', $user->id)
                ->where('tanggal_jaga', '>=', $today)
                ->where('tanggal_jaga', '<=', $nextWeek)
                ->with(['shiftTemplate'])
                ->orderBy('tanggal_jaga')
                ->get();
            
            // Get today's schedule
            $todaySchedule = JadwalJaga::where('pegawai_id', $user->id)
                ->whereDate('tanggal_jaga', $today)
                ->with(['shiftTemplate'])
                ->first();
            
            // Get next schedule
            $nextSchedule = JadwalJaga::where('pegawai_id', $user->id)
                ->where('tanggal_jaga', '>', $today)
                ->with(['shiftTemplate'])
                ->orderBy('tanggal_jaga')
                ->first();
            
            // Get this week's schedule count
            $thisWeekCount = JadwalJaga::where('pegawai_id', $user->id)
                ->whereBetween('tanggal_jaga', [
                    Carbon::now()->startOfWeek(),
                    Carbon::now()->endOfWeek()
                ])
                ->count();
            
            return [
                'upcoming_schedules' => $upcomingSchedules,
                'today_schedule' => $todaySchedule,
                'next_schedule' => $nextSchedule,
                'this_week_count' => $thisWeekCount,
            ];
        });
        
        return $scheduleData;
    }
}