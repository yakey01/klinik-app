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
use Filament\Navigation\NavigationGroup;
use Filament\Support\Enums\ThemeMode;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use App\Filament\Pages\Auth\CustomLogin;
use Hasnayeen\Themes\ThemesPlugin;
use Saade\FilamentFullCalendar\FilamentFullCalendarPlugin;

class BendaharaPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('bendahara')
            ->path('bendahara')
            ->login(CustomLogin::class)
            ->brandName('💰 Dokterku - Bendahara')
            ->brandLogo(asset('images/logo.png'))
            ->brandLogoHeight('2rem')
            ->favicon(asset('favicon.ico'))
            ->colors([
                'primary' => Color::rgb('rgb(251, 189, 35)'), // Gold theme for treasury
                'secondary' => Color::rgb('rgb(118, 75, 162)'),
                'success' => Color::rgb('rgb(16, 185, 129)'),
                'warning' => Color::rgb('rgb(251, 189, 35)'),
                'danger' => Color::rgb('rgb(239, 68, 68)'),
                'info' => Color::rgb('rgb(58, 191, 248)'),
                'gray' => Color::Gray,
            ])
            ->darkMode()
            // ->defaultThemeMode(ThemeMode::Light)
            ->spa()
            ->globalSearch()
            ->globalSearchKeyBindings(['command+k', 'ctrl+k'])
            ->sidebarCollapsibleOnDesktop()
            ->sidebarFullyCollapsibleOnDesktop()
            ->maxContentWidth('full')
            ->breadcrumbs(false)
            ->topNavigation(false)
            ->resources([
                \App\Filament\Bendahara\Resources\ValidasiPendapatanHarianResource::class,
                \App\Filament\Bendahara\Resources\ValidasiPengeluaranHarianResource::class,
                \App\Filament\Bendahara\Resources\ValidasiTindakanResource::class,
                \App\Filament\Bendahara\Resources\ValidasiJumlahPasienResource::class,
                \App\Filament\Bendahara\Resources\ValidasiPendapatanResource::class,
            ])
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Bendahara/Widgets'), for: 'App\\Filament\\Bendahara\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                Widgets\FilamentInfoWidget::class,
                \App\Filament\Bendahara\Widgets\BendaharaStatsWidget::class,
                \App\Filament\Bendahara\Widgets\ValidasiChartWidget::class,
                \App\Filament\Bendahara\Widgets\PendapatanMingguanWidget::class,
                \App\Filament\Bendahara\Widgets\PengeluaranMingguanWidget::class,
            ])
            ->navigationGroups([
                NavigationGroup::make('📊 Dashboard')
                    ->icon('heroicon-o-home')
                    ->collapsed(false),
                NavigationGroup::make('✅ Validasi Transaksi')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->collapsed(false),
                NavigationGroup::make('💰 Manajemen Jaspel')
                    ->icon('heroicon-o-currency-dollar')
                    ->collapsed(true),
                NavigationGroup::make('📈 Laporan Keuangan')
                    ->icon('heroicon-o-document-chart-bar')
                    ->collapsed(true),
                NavigationGroup::make('📋 Audit & Kontrol')
                    ->icon('heroicon-o-shield-check')
                    ->collapsed(true),
                NavigationGroup::make('⚙️ Pengaturan')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->collapsed(true),
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
                \App\Http\Middleware\EnhancedRoleMiddleware::class,
            ])
            ->authGuard('web')
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s')
            ->plugins([
                ThemesPlugin::make()
                    ->canViewThemesPage(fn () => auth()->user()?->hasRole('bendahara'))
                    ->registerTheme([
                        'name' => 'Treasury Gold',
                        'id' => 'treasury-gold',
                        'vite' => 'resources/css/filament/bendahara/theme.css',
                    ]),
                FilamentFullCalendarPlugin::make()
                    ->timezone(config('app.timezone'))
                    ->locale(config('app.locale')),
            ])
            ->font('Inter')
            ->tenant(null)
            ->profile()
            ->registration(false)
            ->passwordReset(false)
            ->emailVerification(false)
            ->viteTheme('resources/css/filament/bendahara/theme.css')
            ->renderHook(
                'panels::body.start',
                fn (): string => '<div class="bg-gradient-to-r from-yellow-50 to-amber-50 dark:from-yellow-900/20 dark:to-amber-900/20"></div>'
            );
    }

    public function boot(): void
    {
        // Initialize any additional services for bendahara panel
        if (app()->environment('production')) {
            // Enable additional security measures in production
            config(['session.secure' => true]);
            config(['session.http_only' => true]);
            config(['session.same_site' => 'strict']);
        }
    }
}