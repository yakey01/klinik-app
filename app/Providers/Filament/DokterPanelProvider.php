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
use Filament\Enums\ThemeMode;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class DokterPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('dokter')
            ->path('dokter')
            ->login()
            ->brandName('Dokterku - Dashboard Dokter')
            ->brandLogoHeight('2rem')
            ->favicon(asset('favicon.ico'))
            ->colors([
                'primary' => Color::Blue,
            ])
            ->darkMode()
            ->sidebarCollapsibleOnDesktop()
            ->navigationGroups([
                'Dashboard',
                'Presensi & Jaspel',
                'Pengaturan'
            ])
            ->discoverResources(in: app_path('Filament/Dokter/Resources'), for: 'App\\Filament\\Dokter\\Resources')
            ->discoverPages(in: app_path('Filament/Dokter/Pages'), for: 'App\\Filament\\Dokter\\Pages')
            ->pages([
                \App\Filament\Dokter\Pages\DashboardDokter::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Dokter/Widgets'), for: 'App\\Filament\\Dokter\\Widgets')
            ->widgets([
                \App\Filament\Dokter\Widgets\TindakanPerHariWidget::class,
                \App\Filament\Dokter\Widgets\JaspelComparisonWidget::class,
                \App\Filament\Dokter\Widgets\PresensiStatsWidget::class,
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
            ->plugin(\Leandrocfe\FilamentApexCharts\FilamentApexChartsPlugin::make());
    }

    public function canAccessPanel(\Illuminate\Contracts\Auth\Authenticatable $user): bool
    {
        return $user->role && $user->role->name === 'dokter';
    }
}
