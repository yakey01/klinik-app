<?php

namespace App\Filament\Dokter\Resources\JadwalJagaResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\JadwalJaga;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class ScheduleOverviewWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $user = Auth::user();
        
        $today = Carbon::today();
        $thisWeek = Carbon::now()->startOfWeek();
        $nextWeek = Carbon::now()->addWeek();
        $thisMonth = Carbon::now()->startOfMonth();
        
        // Today's schedule
        $todaySchedule = JadwalJaga::where('pegawai_id', $user->id)
            ->whereDate('tanggal_jaga', $today)
            ->first();
            
        // This week's schedules
        $thisWeekCount = JadwalJaga::where('pegawai_id', $user->id)
            ->whereBetween('tanggal_jaga', [$thisWeek, Carbon::now()->endOfWeek()])
            ->count();
            
        // Next 7 days schedules
        $upcomingCount = JadwalJaga::where('pegawai_id', $user->id)
            ->where('tanggal_jaga', '>', $today)
            ->where('tanggal_jaga', '<=', $nextWeek)
            ->count();
            
        // This month total
        $thisMonthCount = JadwalJaga::where('pegawai_id', $user->id)
            ->where('tanggal_jaga', '>=', $thisMonth)
            ->count();
            
        // Next schedule
        $nextSchedule = JadwalJaga::where('pegawai_id', $user->id)
            ->where('tanggal_jaga', '>', $today)
            ->orderBy('tanggal_jaga')
            ->first();

        return [
            Stat::make('Jadwal Hari Ini', $todaySchedule ? 'Ya' : 'Tidak')
                ->description($todaySchedule ? 
                    ($todaySchedule->shiftTemplate?->nama_shift ?? 'Shift') . ' - ' . ($todaySchedule->unit_kerja ?? 'Unit kerja')
                    : 'Tidak ada jadwal hari ini'
                )
                ->color($todaySchedule ? 'primary' : 'gray')
                ->icon($todaySchedule ? 'heroicon-o-check-circle' : 'heroicon-o-moon'),
                
            Stat::make('Jadwal Minggu Ini', $thisWeekCount)
                ->description('Total shift minggu ini')
                ->color($thisWeekCount > 0 ? 'success' : 'gray')
                ->icon('heroicon-o-calendar-days'),
                
            Stat::make('7 Hari Ke Depan', $upcomingCount)
                ->description('Jadwal dalam seminggu')
                ->color($upcomingCount > 0 ? 'warning' : 'gray')
                ->icon('heroicon-o-clock'),
                
            Stat::make('Total Bulan Ini', $thisMonthCount)
                ->description($nextSchedule ? 
                    'Berikutnya: ' . $nextSchedule->tanggal_jaga->format('d M')
                    : 'Tidak ada jadwal mendatang'
                )
                ->color('info')
                ->icon('heroicon-o-chart-bar'),
        ];
    }
}