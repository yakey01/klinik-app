<?php

namespace App\Filament\Paramedis\Pages;

use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;

class MobileDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-device-phone-mobile';
    protected static ?string $navigationLabel = 'Mobile Dashboard';
    protected static ?int $navigationSort = 1;
    protected static string $view = 'filament.paramedis.pages.mobile-dashboard';
    
    public function getTitle(): string|Htmlable
    {
        return 'Dashboard';
    }
    
    public function getHeading(): string|Htmlable
    {
        return '';
    }
    
    protected function getViewData(): array
    {
        $user = auth()->user();
        $today = now();
        
        // Get attendance data
        $attendance = \App\Models\Attendance::where('user_id', $user->id)
            ->where('date', $today->toDateString())
            ->first();
            
        // Get today's schedule
        $schedule = \App\Models\Schedule::where('user_id', $user->id)
            ->where('date', $today->toDateString())
            ->with('shift')
            ->first();
            
        // Get performance stats
        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();
        
        $procedureCount = \App\Models\Tindakan::where('user_id', $user->id)
            ->whereBetween('tanggal', [$startOfMonth, $endOfMonth])
            ->count();
            
        $totalJaspel = \App\Models\Jaspel::where('user_id', $user->id)
            ->whereBetween('tanggal', [$startOfMonth, $endOfMonth])
            ->where('status', 'approved')
            ->sum('jumlah');
            
        // Get recent notifications
        $notifications = $user->notifications()
            ->latest()
            ->limit(5)
            ->get();
            
        return [
            'user' => $user,
            'attendance' => $attendance,
            'schedule' => $schedule,
            'procedureCount' => $procedureCount,
            'totalJaspel' => $totalJaspel,
            'notifications' => $notifications,
            'canCheckIn' => !$attendance,
            'canCheckOut' => $attendance && !$attendance->time_out,
        ];
    }
}