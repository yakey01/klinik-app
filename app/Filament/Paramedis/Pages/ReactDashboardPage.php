<?php

namespace App\Filament\Paramedis\Pages;

use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;

class ReactDashboardPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationLabel = 'Dashboard React';
    protected static ?int $navigationSort = 1;
    protected static string $view = 'filament.paramedis.pages.react-dashboard';
    protected static string $routePath = '/react-dashboard';
    
    // Hide from main navigation initially - can be accessed via direct URL
    protected static bool $shouldRegisterNavigation = false;
    
    public function getTitle(): string|Htmlable
    {
        return 'React Dashboard';
    }
    
    public function getHeading(): string|Htmlable
    {
        return '';
    }

    public function mount(): void
    {
        // Ensure user is logged in
        if (!Auth::check()) {
            abort(403, 'Please login first.');
        }
        
        // Log debug info
        $user = Auth::user();
        \Log::info('ReactDashboard access attempt', [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'user_role' => $user->role?->name ?? 'no_role',
            'role_id' => $user->role_id ?? 'null'
        ]);
        
        // Allow access for now (remove strict paramedis check for debugging)
        // if ($user->role?->name !== 'paramedis') {
        //     abort(403, 'Access denied. Paramedis role required.');
        // }
    }
}