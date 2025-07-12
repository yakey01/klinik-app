<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class ParamedisPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('paramedis')
            ->path('paramedis')
            ->login()
            ->brandName('Dokterku - Paramedis')
            ->favicon(asset('favicon.ico'))
            ->colors([
                'primary' => Color::Green,
            ])
            ->darkMode()
            ->discoverResources(in: app_path('Filament/Paramedis/Resources'), for: 'App\\Filament\\Paramedis\\Resources')
            ->discoverPages(in: app_path('Filament/Paramedis/Pages'), for: 'App\\Filament\\Paramedis\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Paramedis/Widgets'), for: 'App\\Filament\\Paramedis\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                \App\Filament\Paramedis\Widgets\WelcomeWidget::class,
                \App\Filament\Paramedis\Widgets\AttendanceStatusWidget::class,
                \App\Filament\Paramedis\Widgets\JaspelHighlightWidget::class,
                \App\Filament\Paramedis\Widgets\LocationStatusWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->authGuard('web')
            ->databaseNotifications()
            ->tenant(null);
    }
}
