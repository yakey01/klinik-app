<?php

namespace App\Filament\Pages\Auth;

use Filament\Pages\Auth\Login as BaseLogin;
use Illuminate\Http\RedirectResponse;

class CustomLogin extends BaseLogin
{
    public function mount(): void
    {
        // If user is already authenticated, continue normally
        if (auth()->check()) {
            parent::mount();
            return;
        }
        
        // Otherwise, this will be handled by RedirectToUnifiedLogin middleware
        parent::mount();
    }
    
    protected function getRedirectUrl(): string
    {
        return '/';
    }
}