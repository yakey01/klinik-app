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
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use App\Filament\Pages\Auth\CustomLogin;
use Hasnayeen\Themes\ThemesPlugin;

class BendaharaPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('bendahara')
            ->path('bendahara')
            ->login(false)
            ->brandName('💰 Dokterku - Bendahara')
            ->favicon(asset('favicon.ico'))
            ->colors([
                'primary' => Color::rgb('rgb(251, 189, 35)'),
                'secondary' => Color::rgb('rgb(118, 75, 162)'),
                'success' => Color::rgb('rgb(16, 185, 129)'),
                'warning' => Color::rgb('rgb(251, 189, 35)'),
                'danger' => Color::rgb('rgb(239, 68, 68)'),
                'info' => Color::rgb('rgb(58, 191, 248)'),
            ])
            ->darkMode()
            ->resources([
                \App\Filament\Bendahara\Resources\ValidasiPendapatanHarianResource::class,
                \App\Filament\Bendahara\Resources\ValidasiPengeluaranHarianResource::class,
                \App\Filament\Bendahara\Resources\ValidasiTindakanResource::class,
            ])
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Bendahara/Widgets'), for: 'App\\Filament\\Bendahara\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                \App\Filament\Bendahara\Widgets\BendaharaStatsWidget::class,
                \App\Filament\Bendahara\Widgets\ValidasiChartWidget::class,
                \App\Filament\Bendahara\Widgets\PendapatanMingguanWidget::class,
                \App\Filament\Bendahara\Widgets\PengeluaranMingguanWidget::class,
            ])
            ->navigationGroups([
                NavigationGroup::make('🏠 Dashboard')
                    ->icon('heroicon-o-home')
                    ->collapsible(),
                NavigationGroup::make('💵 Validasi Transaksi')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->collapsible(),
                NavigationGroup::make('💰 Manajemen Jaspel')
                    ->icon('heroicon-o-currency-dollar')
                    ->collapsible(),
                NavigationGroup::make('🗄 Laporan Keuangan')
                    ->icon('heroicon-o-document-chart-bar')
                    ->collapsible(),
                NavigationGroup::make('🔧 Pengaturan')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->collapsible(),
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
                ThemesPlugin::make()
                    ->canViewThemesPage(fn () => auth()->user()?->hasRole('bendahara')),
            ])
            ->tenant(null);
    }
}