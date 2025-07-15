<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class SettingsPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    
    protected static ?string $navigationGroup = 'System Administration';
    
    protected static ?string $navigationLabel = 'Pengaturan';
    
    protected static ?string $title = 'Pengaturan Sistem';
    
    protected static ?int $navigationSort = 999;

    protected static string $view = 'filament.pages.settings-page';
    
    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('admin') ?? false;
    }
}
