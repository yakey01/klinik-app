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
            
        // Generate sample recent activities with Lucide icons
        $recentActivities = [
            [
                'title' => 'Jaspel Tindakan',
                'time' => 'Hari ini, ' . now()->format('H:i'),
                'amount' => rand(150000, 300000),
                'color' => '#10B981',
                'lucide_icon' => 'banknote'
            ],
            [
                'title' => 'Check-in Berhasil',
                'time' => 'Hari ini, 07:30',
                'color' => '#0EA5E9',
                'lucide_icon' => 'check-circle'
            ],
            [
                'title' => 'Tindakan Medis',
                'time' => 'Kemarin, 14:25',
                'amount' => rand(200000, 400000),
                'color' => '#8B5CF6',
                'lucide_icon' => 'clipboard-list'
            ],
            [
                'title' => 'Jadwal Jaga',
                'time' => 'Besok, 08:00',
                'color' => '#F59E0B',
                'lucide_icon' => 'calendar-clock'
            ]
        ];
            
        return [
            'user' => $user,
            'monthlyJaspel' => $monthlyJaspel,
            'monthlyHours' => $monthlyHours,
            'recentActivities' => $recentActivities
        ];
    }
}