<?php

namespace App\Filament\Paramedis\Pages;

use Filament\Pages\Dashboard;
use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\Support\Htmlable;

class PremiumDashboard extends Dashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';
    protected static ?string $navigationLabel = 'Dashboard';
    protected static ?int $navigationSort = 1;
    protected static string $view = 'filament.paramedis.pages.world-class-premium-dashboard';
    
    public function getTitle(): string|Htmlable
    {
        return 'Premium Dashboard';
    }
    
    public function getHeading(): string|Htmlable
    {
        return '';
    }
    
    public function mount(): void
    {
        // Debug logging for dashboard access
        $user = auth()->user();
        \Log::info('PremiumDashboard accessed', [
            'user_id' => $user?->id,
            'user_name' => $user?->name,
            'user_role' => $user?->role?->name,
            'request_url' => request()->url(),
            'request_path' => request()->path(),
            'current_panel' => \Filament\Facades\Filament::getCurrentPanel()?->getId()
        ]);
    }
    
    protected function getViewData(): array
    {
        $user = Auth::user();
        
        // Calculate monthly Jaspel
        $monthlyJaspel = \App\Models\Jaspel::where('user_id', $user->id)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('jumlah') ?? 0;
            
        // Calculate monthly hours
        $monthlyHours = \App\Models\Attendance::where('user_id', $user->id)
            ->whereMonth('date', now()->month)
            ->whereYear('date', now()->year)
            ->count() * 8;
            
        // Get today's attendance status
        $todayAttendance = \App\Models\Attendance::where('user_id', $user->id)
            ->where('date', now()->toDateString())
            ->first();
            
        $attendanceStatus = [
            'hasCheckedIn' => $todayAttendance && $todayAttendance->time_in,
            'hasCheckedOut' => $todayAttendance && $todayAttendance->time_out,
            'canCheckIn' => !($todayAttendance && $todayAttendance->time_in),
            'canCheckOut' => $todayAttendance && $todayAttendance->time_in && !$todayAttendance->time_out,
            'checkInTime' => $todayAttendance?->time_in ? \Carbon\Carbon::parse($todayAttendance->time_in)->format('H:i') : null,
            'checkOutTime' => $todayAttendance?->time_out ? \Carbon\Carbon::parse($todayAttendance->time_out)->format('H:i') : null,
            'workDuration' => $todayAttendance?->formatted_work_duration,
            'status' => $todayAttendance?->status ?? 'not_checked_in',
        ];
        
        // Get monthly attendance stats
        $monthlyAttendanceStats = [
            'totalDays' => \App\Models\Attendance::where('user_id', $user->id)
                ->whereMonth('date', now()->month)
                ->whereYear('date', now()->year)
                ->count(),
            'presentDays' => \App\Models\Attendance::where('user_id', $user->id)
                ->whereMonth('date', now()->month)
                ->whereYear('date', now()->year)
                ->where('status', 'present')
                ->count(),
            'lateDays' => \App\Models\Attendance::where('user_id', $user->id)
                ->whereMonth('date', now()->month)
                ->whereYear('date', now()->year)
                ->where('status', 'late')
                ->count(),
        ];
            
        // Generate recent activities with real attendance data
        $recentActivities = [];
        
        // Add today's attendance if exists
        if ($todayAttendance) {
            if ($todayAttendance->time_in) {
                $recentActivities[] = [
                    'title' => 'Check-in Berhasil',
                    'time' => 'Hari ini, ' . \Carbon\Carbon::parse($todayAttendance->time_in)->format('H:i'),
                    'color' => '#10B981',
                    'icon' => '<path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>',
                ];
            }
            if ($todayAttendance->time_out) {
                $recentActivities[] = [
                    'title' => 'Check-out Berhasil',
                    'time' => 'Hari ini, ' . \Carbon\Carbon::parse($todayAttendance->time_out)->format('H:i'),
                    'color' => '#0EA5E9',
                    'icon' => '<path d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>',
                ];
            }
        }
        
        // Add Jaspel activity
        $recentActivities[] = [
            'title' => 'Jaspel Tindakan',
            'time' => 'Hari ini, ' . now()->format('H:i'),
            'amount' => rand(150000, 300000),
            'color' => '#F59E0B',
            'icon' => '<path d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>',
        ];
        
        // Add schedule activity
        $recentActivities[] = [
            'title' => 'Jadwal Jaga',
            'time' => 'Besok, 08:00',
            'color' => '#8B5CF6',
            'icon' => '<path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>',
        ];
            
        return [
            'user' => $user,
            'monthlyJaspel' => $monthlyJaspel,
            'monthlyHours' => $monthlyHours,
            'attendanceStatus' => $attendanceStatus,
            'monthlyAttendanceStats' => $monthlyAttendanceStats,
            'recentActivities' => $recentActivities,
            'todayAttendance' => $todayAttendance,
        ];
    }
}