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

class DokterPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('dokter')
            ->path('dokter')
            ->login(false)
            ->brandName('🩺 Dashboard Dokter')
            ->brandLogo(asset('images/logo-dokter.png'))
            ->favicon(asset('favicon.ico'))
            ->viteTheme('resources/css/filament/dokter/theme.css')
            ->colors([
                'primary' => Color::Blue,
                'secondary' => Color::Cyan,
                'success' => Color::Green,
                'warning' => Color::Amber,
                'danger' => Color::Red,
                'info' => Color::Indigo,
            ])
            ->sidebarCollapsibleOnDesktop()
            ->resources([
                \App\Filament\Resources\AttendanceRecapResource::class,
                \App\Filament\Resources\DokterResource::class,
            ])
            ->pages([
                // Dashboard page will be added later if needed
            ])
            ->widgets([
                // Add dokter-specific widgets here if needed
            ])
            ->navigationGroups([
                NavigationGroup::make('📊 Laporan & Analisis')
                    ->icon('heroicon-o-chart-bar'),
                NavigationGroup::make('👨‍⚕️ Data Dokter')
                    ->icon('heroicon-o-users'),
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
                \App\Http\Middleware\DokterMiddleware::class,
            ])
            ->authGuard('web')
            ->userMenuItems([
                'dashboard' => \Filament\Navigation\MenuItem::make()
                    ->label('Dashboard')
                    ->url(fn (): string => '/dokter')
                    ->icon('heroicon-o-home'),
                'attendance' => \Filament\Navigation\MenuItem::make()
                    ->label('Rekap Kehadiran')
                    ->url(fn (): string => route('filament.dokter.resources.attendance-recaps.index'))
                    ->icon('heroicon-o-chart-bar-square'),
            ]);
    }
}