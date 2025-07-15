<?php

namespace App\Filament\Dokter\Pages;

use Filament\Pages\Dashboard;
use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\Support\Htmlable;

class DashboardDokter extends Dashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';
    protected static ?string $navigationLabel = 'Dashboard';
    protected static ?int $navigationSort = 1;
    protected static string $view = 'dokter.dashboards.dashboard-dokter';
    
    public function getTitle(): string|Htmlable
    {
        return 'Dashboard Dokter - Sistem Manajemen Klinik';
    }
    
    public function getHeading(): string|Htmlable
    {
        return '';
    }
    
    protected function getViewData(): array
    {
        $user = Auth::user();
        
        // Sample dashboard data for dokter
        $dashboardStats = [
            'totalJaspel' => 12450000,
            'weeklyAttendance' => 6,
            'activeShifts' => 4,
            'totalTindakan' => 63,
            'monthlyTarget' => 15600000,
            'completionRate' => 80,
        ];
        
        // Chart data for the last 30 days
        $chartData = [
            'jaspenTrend' => [
                'labels' => collect(range(29, 0))->map(fn($days) => now()->subDays($days)->format('M d'))->toArray(),
                'data' => collect(range(1, 30))->map(fn() => rand(300000, 700000))->toArray(),
            ],
            'shiftComparison' => [
                'labels' => ['Pagi', 'Siang', 'Malam'],
                'data' => [15, 12, 8],
            ],
        ];
        
        return [
            'user' => $user,
            'dashboardStats' => $dashboardStats,
            'chartData' => $chartData,
        ];
    }
}