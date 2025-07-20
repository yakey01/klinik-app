<?php

namespace App\Filament\Paramedis\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\Support\Htmlable;
use App\Models\Attendance;
use App\Models\WorkLocation;
use Carbon\Carbon;

class ModernMobileDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-device-phone-mobile';
    protected static ?string $navigationLabel = 'Dashboard Mobile';
    protected static ?int $navigationSort = 1;
    protected static bool $shouldRegisterNavigation = false;
    protected static string $view = 'filament.paramedis.pages.modern-mobile-dashboard';
    protected static string $routePath = '/mobile-dashboard';
    
    public function getTitle(): string|Htmlable
    {
        return 'Dashboard Mobile';
    }
    
    public function getHeading(): string|Htmlable
    {
        return '';
    }
    
    public $user;
    public $todayAttendance;
    public $monthlyHours;
    public $weeklyJaspel;
    public $monthlyJaspel;
    public $upcomingSchedule;
    public $recentActivities;
    public $healthMetrics;

    public function mount(): void
    {
        $this->user = Auth::user();
        $this->loadDashboardData();
    }
    
    private function loadDashboardData(): void
    {
        $now = Carbon::now('Asia/Jakarta');
        $startOfMonth = $now->copy()->startOfMonth();
        $startOfWeek = $now->copy()->startOfWeek();
        
        // Today's attendance
        $this->todayAttendance = Attendance::where('user_id', $this->user->id)
            ->whereDate('check_in_time', $now->toDateString())
            ->first();
        
        // Monthly hours calculation
        $monthlyAttendances = Attendance::where('user_id', $this->user->id)
            ->whereBetween('check_in_time', [$startOfMonth, $now])
            ->get();
        
        $this->monthlyHours = $monthlyAttendances->sum(function ($attendance) {
            if ($attendance->check_out_time) {
                return Carbon::parse($attendance->check_out_time)
                    ->diffInHours(Carbon::parse($attendance->check_in_time));
            }
            return 0;
        });
        
        // Jaspel calculations (demo data for now)
        $this->weeklyJaspel = rand(4500000, 6500000);
        $this->monthlyJaspel = rand(18000000, 25000000);
        
        // Upcoming schedule (demo)
        $this->upcomingSchedule = [
            'date' => $now->copy()->addDay()->format('Y-m-d'),
            'shift' => 'Pagi',
            'time' => '07:00 - 15:00',
            'location' => 'Ruang IGD'
        ];
        
        // Recent activities
        $this->recentActivities = [
            [
                'type' => 'attendance',
                'title' => 'Check-in berhasil',
                'time' => $now->copy()->subHours(2)->format('H:i'),
                'icon' => 'clock',
                'color' => 'green'
            ],
            [
                'type' => 'jaspel',
                'title' => 'Jaspel bulan lalu dibayar',
                'time' => $now->copy()->subDays(3)->format('d M'),
                'icon' => 'currency-dollar',
                'color' => 'blue'
            ],
            [
                'type' => 'schedule',
                'title' => 'Jadwal minggu depan tersedia',
                'time' => $now->copy()->subDays(1)->format('d M'),
                'icon' => 'calendar',
                'color' => 'purple'
            ]
        ];
        
        // Health metrics (demo for healthcare focus)
        $this->healthMetrics = [
            'stress_level' => rand(1, 5),
            'energy_level' => rand(3, 5),
            'work_satisfaction' => rand(3, 5)
        ];
    }
    
    public function refreshData(): void
    {
        $this->loadDashboardData();
        $this->notify('success', 'Data berhasil diperbarui');
    }
}