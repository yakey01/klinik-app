<?php

namespace App\Filament\Pages\Auth;

use Filament\Pages\Auth\Login as BaseLogin;
use Illuminate\Http\RedirectResponse;

class CustomLogin extends BaseLogin
{
    public function mount(): void
    {
        parent::mount();
    }
    
    protected function getRedirectUrl(): string
    {
        return '/admin';
    }
}