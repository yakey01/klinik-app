<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Navigation\NavigationGroup;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use App\Filament\Pages\Auth\CustomLogin;

class ParamedisPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('paramedis')
            ->path('paramedis')
            ->login(false)
            ->brandName('🩺 Paramedis Mobile')
            ->brandLogo(asset('images/logo-paramedis.png'))
            ->favicon(asset('favicon.ico'))
            ->viteTheme([
                'resources/css/filament/paramedis/theme.css',
                'resources/js/filament/paramedis-gps-attendance.js',
            ])
            ->colors([
                'primary' => Color::Emerald,
                'secondary' => Color::Teal,
                'success' => Color::Green,
                'warning' => Color::Amber,
                'danger' => Color::Red,
                'info' => Color::Cyan,
            ])
            ->sidebarCollapsibleOnDesktop()
            ->resources([
                \App\Filament\Paramedis\Resources\AttendanceResource::class,
                \App\Filament\Paramedis\Resources\AttendanceHistoryResource::class,
                \App\Filament\Resources\AttendanceRecapResource::class,
            ])
            ->pages([
                \App\Filament\Paramedis\Pages\RedirectToMobileApp::class,
            ])
            ->widgets([
                \App\Filament\Paramedis\Widgets\AttendanceHistoryStatsWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                \App\Http\Middleware\SessionCleanupMiddleware::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                \App\Http\Middleware\RefreshCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                \App\Http\Middleware\RedirectToUnifiedAuth::class,
                Authenticate::class,
                \App\Http\Middleware\ParamedisMiddleware::class,
            ])
            ->authGuard('web')
            ->userMenuItems([
                'mobile-app' => \Filament\Navigation\MenuItem::make()
                    ->label('Mobile App')
                    ->url(fn (): string => route('paramedis.mobile-app'))
                    ->icon('heroicon-o-device-phone-mobile'),
            ]);
    }
}