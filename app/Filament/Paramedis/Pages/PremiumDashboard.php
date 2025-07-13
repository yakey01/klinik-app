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
    protected static string $view = 'premium-paramedis-dashboard-simple';
    
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
}