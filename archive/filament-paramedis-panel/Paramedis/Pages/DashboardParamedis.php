<?php

namespace App\Filament\Paramedis\Pages;

use Filament\Pages\Page;
use App\Models\User;
use App\Models\Tindakan;
use App\Models\JadwalJaga;
use App\Models\Jaspel;
use Illuminate\Support\Facades\Auth;

class DashboardParamedis extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-home';
    
    protected static ?string $title = 'Dashboard Paramedis';
    
    protected static ?string $navigationLabel = 'Dashboard';
    
    protected static ?int $navigationSort = 1;
    
    protected static bool $shouldRegisterNavigation = false;
    
    protected static string $view = 'filament.paramedis.pages.dashboard-paramedis';
    
    protected static string $routePath = '/';
    
    public function mount(): void
    {
        // Get current user data
        $user = Auth::user();
        $this->user = $user;
        
        // Calculate dashboard statistics
        $this->attendanceCount = $this->getAttendanceCount();
        $this->upcomingSchedules = $this->getUpcomingSchedules();
        $this->pendingTasks = $this->getPendingTasks();
        $this->monthlyJaspel = $this->getMonthlyJaspel();
    }
    
    public $user;
    public $attendanceCount;
    public $upcomingSchedules;
    public $pendingTasks;
    public $monthlyJaspel;
    
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
}