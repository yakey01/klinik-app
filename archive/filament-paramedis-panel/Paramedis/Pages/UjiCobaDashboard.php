<?php

namespace App\Filament\Paramedis\Pages;

use Filament\Pages\Dashboard;
use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\Support\Htmlable;

class UjiCobaDashboard extends Dashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';
    protected static ?string $navigationLabel = 'Dashboard';
    protected static ?int $navigationSort = 1;
    protected static string $view = 'paramedis.dashboards.ujicoba-dashboard';
    
    public function getTitle(): string|Htmlable
    {
        return 'UjiCoba - Experimental Dashboard';
    }
    
    public function getHeading(): string|Htmlable
    {
        return '';
    }
    
    protected function getViewData(): array
    {
        $user = Auth::user();
        
        // Sample dashboard data
        $dashboardStats = [
            'totalJaspel' => 8720000,
            'weeklyAttendance' => 5,
            'activeShifts' => 3,
            'totalTindakan' => 47,
            'monthlyTarget' => 10464000,
            'completionRate' => 83,
        ];
        
        // Chart data for the last 30 days
        $chartData = [
            'jaspenTrend' => [
                'labels' => collect(range(29, 0))->map(fn($days) => now()->subDays($days)->format('M d'))->toArray(),
                'data' => collect(range(1, 30))->map(fn() => rand(200000, 500000))->toArray(),
            ],
            'shiftComparison' => [
                'labels' => ['Pagi', 'Siang', 'Malam'],
                'data' => [12, 8, 6],
            ],
        ];
        
        return [
            'user' => $user,
            'dashboardStats' => $dashboardStats,
            'chartData' => $chartData,
        ];
    }
}