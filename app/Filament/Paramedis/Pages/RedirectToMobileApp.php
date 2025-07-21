<?php

namespace App\Filament\Paramedis\Pages;

use Filament\Pages\Page;

class RedirectToMobileApp extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-device-mobile';
    protected static string $view = 'filament.paramedis.pages.redirect-to-mobile-app';
    protected static ?string $title = 'Redirecting to Mobile App...';
    protected static ?string $navigationLabel = 'Mobile App';
    protected static ?string $slug = '/';

    public function mount(): void
    {
        try {
            // Use redirect method that works in Filament context
            $this->redirect(route('paramedis.mobile-app'));
        } catch (\Exception $e) {
            // If redirect fails, log error and fallback to JavaScript redirect
            \Log::error('RedirectToMobileApp mount() failed: ' . $e->getMessage());
            // The view has JavaScript redirect as fallback
        }
    }
}