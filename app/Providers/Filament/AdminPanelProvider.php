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
use Filament\Support\Enums\ThemeMode;
use Filament\Navigation\NavigationGroup;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\Support\Facades\FilamentView;
use Cheesegrits\FilamentGoogleMaps\FilamentGoogleMapsPlugin;
use Saade\FilamentFullCalendar\FilamentFullCalendarPlugin;
use App\Filament\Pages\Auth\CustomLogin;
use Hasnayeen\Themes\ThemesPlugin;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login(CustomLogin::class)
            ->brandName('🏥 Dokterku Admin')
            ->colors([
                'primary' => Color::rgb('rgb(102, 126, 234)'),
                'secondary' => Color::rgb('rgb(118, 75, 162)'),
                'success' => Color::rgb('rgb(16, 185, 129)'),
                'warning' => Color::rgb('rgb(251, 189, 35)'),
                'danger' => Color::rgb('rgb(239, 68, 68)'),
                'info' => Color::rgb('rgb(58, 191, 248)'),
            ])
            ->darkMode()
            ->spa()
            ->globalSearch()
            ->globalSearchKeyBindings(['command+k', 'ctrl+k'])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                \App\Filament\Widgets\AdminOverviewWidget::class,
                \App\Filament\Widgets\SystemHealthWidget::class,
                \App\Filament\Widgets\ClinicStatsWidget::class,
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
            ->plugins([
                FilamentFullCalendarPlugin::make(),
            ])
            ->tenant(null) // Disable multi-tenancy for now
            ->navigationGroups([
                NavigationGroup::make('👥 User Management')
                    ->icon('heroicon-o-users')
                    ->collapsible(),
                NavigationGroup::make('📋 Medical Records')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->collapsible(),
                NavigationGroup::make('💰 Financial Management')
                    ->icon('heroicon-o-currency-dollar')
                    ->collapsible(),
                NavigationGroup::make('📊 Reports & Analytics')
                    ->icon('heroicon-o-chart-bar')
                    ->collapsible(),
                NavigationGroup::make('⚙️ System Administration')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->collapsible(),
            ]);
    }
}