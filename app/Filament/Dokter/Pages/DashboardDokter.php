<?php

namespace App\Filament\Dokter\Pages;

use Filament\Pages\Page;
use App\Models\User;
use App\Models\Tindakan;
use App\Models\JadwalJaga;
use App\Models\Jaspel;
use Illuminate\Support\Facades\Auth;

class DashboardDokter extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-home';
    
    protected static ?string $title = 'Dashboard Dokter';
    
    protected static ?string $navigationLabel = 'Dashboard';
    
    protected static ?int $navigationSort = 1;
    
    protected static string $view = 'filament.dokter.pages.dashboard-dokter';
    
    protected static string $routePath = '/';
    
    public function mount(): void
    {
        // Get current user data
        $user = Auth::user();
        $this->user = $user;
        
        // Calculate dashboard statistics
        $this->attendanceCount = $this->getAttendanceCount();
        $this->attendanceToday = $this->getAttendanceToday();
        $this->attendanceMonth = $this->getAttendanceMonth();
        $this->upcomingSchedules = $this->getUpcomingSchedules();
        $this->pendingTasks = $this->getPendingTasks();
        $this->monthlyJaspel = $this->getMonthlyJaspel();
        $this->jaspelPending = $this->getJaspelPending();
        $this->jaspelApproved = $this->getJaspelApproved();
        $this->todayAttendance = $this->getTodayAttendance();
    }
    
    public $user;
    public $attendanceCount;
    public $attendanceToday;
    public $attendanceMonth;
    public $upcomingSchedules;
    public $pendingTasks;
    public $monthlyJaspel;
    public $jaspelPending;
    public $jaspelApproved;
    public $todayAttendance;
    
    private function getAttendanceCount(): int
    {
        // Count attendance records for current month
        return 0; // TODO: Implement with actual attendance model
    }
    
    private function getUpcomingSchedules(): int
    {
        // Count upcoming schedules from jadwal_jaga
        return JadwalJaga::where('dokter_id', Auth::id())
            ->where('tanggal', '>=', now())
            ->count();
    }
    
    private function getPendingTasks(): int
    {
        // Count pending medical procedures
        return Tindakan::where('dokter_id', Auth::id())
            ->where('status', 'pending')
            ->count();
    }
    
    private function getMonthlyJaspel(): float
    {
        // Calculate current month jaspel amount
        return Jaspel::where('user_id', Auth::id())
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('nominal') ?? 0;
    }
    
    private function getAttendanceToday(): string
    {
        // Check if user has attendance today
        $hasAttendance = $this->getTodayAttendance();
        return $hasAttendance ? '✓' : '✗';
    }
    
    private function getAttendanceMonth(): int
    {
        // Count attendance days this month
        return 24; // TODO: Implement with actual attendance model
    }
    
    private function getJaspelPending(): int
    {
        return Jaspel::where('user_id', Auth::id())
            ->where('status_validasi', 'pending')
            ->count();
    }
    
    private function getJaspelApproved(): int
    {
        return Jaspel::where('user_id', Auth::id())
            ->where('status_validasi', 'disetujui')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
    }
    
    private function getTodayAttendance()
    {
        // Get today's attendance record
        // TODO: Replace with actual attendance model
        // For now, return a mock object
        return (object) [
            'jam_masuk' => '08:00:00',
            'jam_pulang' => null,
        ];
    }
}