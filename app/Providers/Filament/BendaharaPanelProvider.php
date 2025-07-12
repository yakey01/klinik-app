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
use App\Filament\Pages\Auth\CustomLogin;

class BendaharaPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('bendahara')
            ->path('bendahara')
            ->login(CustomLogin::class)
            ->brandName('Dokterku - Bendahara')
            ->favicon(asset('favicon.ico'))
            ->colors([
                'primary' => Color::Red,
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
                '🏠 Dashboard',
                '💵 Validasi Transaksi',
                '💰 Manajemen Jaspel',
                '🧾 Laporan Keuangan',
                '🛠️ Pengaturan',
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