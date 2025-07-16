<?php

namespace App\Filament\Manajer\Pages;

use Filament\Pages\Page;

class ExecutiveDashboard extends Page
{
    protected static ?string $navigationIcon = null;

    protected static string $view = 'filament.manajer.pages.executive-dashboard';
    
    protected static ?string $navigationLabel = 'Executive Dashboard';
    
    protected static ?string $navigationGroup = '📊 Dashboard & Analytics';

    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('manajer') ?? false;
    }
}