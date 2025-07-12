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

class ManajerPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('manajer')
            ->path('manajer')
            ->login(CustomLogin::class)
            ->brandName('Dokterku - Manajer')
            ->favicon(asset('favicon.ico'))
            ->colors([
                'primary' => Color::Indigo,
            ])
            ->darkMode()
            ->resources([
                \App\Filament\Manajer\Resources\LaporanKeuanganResource::class,
                \App\Filament\Manajer\Resources\ManajemenKaryawanResource::class,
                \App\Filament\Manajer\Resources\AnalyticsKinerjaResource::class,
            ])
            ->pages([
                Pages\Dashboard::class,
            ])
            // ->discoverWidgets(in: app_path('Filament/Manajer/Widgets'), for: 'App\\Filament\\Manajer\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                \App\Filament\Manajer\Widgets\ManajerSummaryWidget::class,
                \App\Filament\Manajer\Widgets\FinancialTrendWidget::class,
                \App\Filament\Manajer\Widgets\PatientDistributionWidget::class,
                \App\Filament\Manajer\Widgets\EmployeePerformanceWidget::class,
                \App\Filament\Manajer\Widgets\TopServiceFeeWidget::class,
            ])
            ->navigationGroups([
                '📊 Dashboard Analytics',
                '💰 Financial Reports',
                '👥 Employee Management',
                '📈 Performance Analytics',
                '🛠️ Settings',
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