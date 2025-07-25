<?php

namespace App\Filament\Pages\Auth;

use Filament\Pages\Auth\Login as BaseLogin;
use Illuminate\Http\RedirectResponse;
use Filament\Forms\Components\Component;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Illuminate\Contracts\Support\Htmlable;

class CustomLogin extends BaseLogin
{
    public function mount(): void
    {
        parent::mount();
    }
    
    protected function getRedirectUrl(): string
    {
        $user = auth()->user();
        
        // Role-based redirect for unified login
        if ($user?->hasRole('admin')) {
            return '/admin';
        } elseif ($user?->hasRole('dokter')) {
            return '/dokter';
        } elseif ($user?->hasRole('paramedis')) {
            return '/paramedis';
        } elseif ($user?->hasRole('petugas')) {
            return '/petugas';
        } elseif ($user?->hasRole('manajer')) {
            return '/manajer';
        } elseif ($user?->hasRole('bendahara')) {
            return '/bendahara';
        } elseif ($user?->hasRole('non_paramedis')) {
            return route('nonparamedis.dashboard');
        }
        
        // Default fallback
        return '/dashboard';
    }

    protected function getForgotPasswordUrl(): string
    {
        return route('password.request');
    }

    public function getTitle(): string|Htmlable
    {
        return 'Login Admin';
    }

    public function getHeading(): string|Htmlable
    {
        return 'Masuk ke Dashboard Admin';
    }

    protected function hasFullWidthFormActions(): bool
    {
        return true;
    }
}