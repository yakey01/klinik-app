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

class PetugasPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('petugas')
            ->path('petugas')
            ->login(CustomLogin::class)
            ->brandName('Petugas Dashboard')
            ->viteTheme('resources/css/filament/petugas/theme.css')
            ->colors([
                'primary' => Color::Amber,
                'success' => Color::Green,
                'warning' => Color::Orange,
                'danger' => Color::Red,
                'info' => Color::Blue,
            ])
            ->sidebarCollapsibleOnDesktop()
            ->resources([
                // Patient Management Group
                \App\Filament\Petugas\Resources\PasienResource::class,
                \App\Filament\Petugas\Resources\TindakanResource::class,
                
                // Daily Data Entry Group  
                \App\Filament\Petugas\Resources\PendapatanHarianResource::class,
                \App\Filament\Petugas\Resources\PengeluaranHarianResource::class,
                \App\Filament\Petugas\Resources\JumlahPasienHarianResource::class,
                
                // Transaction Management Group
                \App\Filament\Petugas\Resources\ValidasiPendapatanResource::class,
            ])
            ->pages([
                \App\Filament\Petugas\Pages\PetugasDashboard::class,
            ])
            ->widgets([
                \Filament\Widgets\AccountWidget::class,
            ])
            ->navigationGroups([
                NavigationGroup::make('Dashboard')
                    ->collapsed(false),
                NavigationGroup::make('Patient Management')
                    ->collapsed(true)
                    ->collapsible(),
                NavigationGroup::make('Daily Data Entry')
                    ->collapsed(true)
                    ->collapsible(),
                NavigationGroup::make('Transaction Management')
                    ->collapsed(true)
                    ->collapsible(),
                NavigationGroup::make('Reports & Analytics')
                    ->collapsed(true)
                    ->collapsible(),
                NavigationGroup::make('Settings')
                    ->collapsed(true)
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
                \App\Http\Middleware\PetugasMiddleware::class,
            ])
            ->authGuard('web');
    }
}