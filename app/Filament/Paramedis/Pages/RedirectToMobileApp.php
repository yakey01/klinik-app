<?php

namespace App\Filament\Paramedis\Pages;

use Filament\Pages\Page;

class RedirectToMobileApp extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-device-phone-mobile';
    protected static string $view = 'filament.paramedis.pages.redirect-to-mobile-app';
    protected static ?string $title = 'Redirecting to Mobile App...';
    protected static ?string $navigationLabel = 'Mobile App';
    protected static ?string $slug = '/';

    public function mount(): void
    {
        redirect()->route('paramedis.mobile-app');
    }
}