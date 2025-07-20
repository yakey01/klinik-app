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
            ->login(CustomLogin::class)
            ->brandName('👨‍⚕️ Doctor Dashboard')
            ->brandLogo(asset('images/logo-dokter.png'))
            ->favicon(asset('favicon.ico'))
            ->colors([
                'primary' => Color::Blue,
                'secondary' => Color::Teal,
                'success' => Color::Green,
                'warning' => Color::Amber,
                'danger' => Color::Red,
                'info' => Color::Cyan,
            ])
            ->sidebarCollapsibleOnDesktop()
            ->resources([
                // Core Resources
                \App\Filament\Dokter\Resources\TindakanResource::class,
                \App\Filament\Dokter\Resources\JaspelResource::class,
                \App\Filament\Dokter\Resources\JadwalJagaResource::class,
                \App\Filament\Dokter\Resources\PasienResource::class,
            ])
            ->pages([
                \App\Filament\Dokter\Pages\DokterDashboard::class,
            ])
            ->widgets([
                \Filament\Widgets\AccountWidget::class,
                \App\Filament\Dokter\Widgets\TodayOverviewWidget::class,
                \App\Filament\Dokter\Widgets\MonthlyJaspelWidget::class,
                \App\Filament\Dokter\Widgets\ScheduleWidget::class,
                \App\Filament\Dokter\Widgets\PerformanceMetricsWidget::class,
            ])
            ->navigationGroups([
                NavigationGroup::make('📊 Dashboard')
                    ->collapsed(false),
                NavigationGroup::make('👥 Patient Management')
                    ->collapsed(true)
                    ->collapsible(),
                NavigationGroup::make('🩺 Medical Procedures')
                    ->collapsed(true)
                    ->collapsible(),
                NavigationGroup::make('💰 Earnings & Jaspel')
                    ->collapsed(true)
                    ->collapsible(),
                NavigationGroup::make('📅 Schedule Management')
                    ->collapsed(true)
                    ->collapsible(),
                NavigationGroup::make('📈 Reports & Analytics')
                    ->collapsed(true)
                    ->collapsible(),
                NavigationGroup::make('⚙️ Settings')
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
                \App\Http\Middleware\RoleMiddleware::class . ':dokter',
            ])
            ->authGuard('web')
            ->userMenuItems([
                'mobile-app' => \Filament\Navigation\MenuItem::make()
                    ->label('Mobile App')
                    ->url(fn (): string => route('dokter.mobile-app'))
                    ->icon('heroicon-o-device-phone-mobile')
                    ->openUrlInNewTab(),
                'profile' => \Filament\Navigation\MenuItem::make()
                    ->label('My Profile')
                    ->url(fn (): string => route('filament.dokter.pages.dokter-dashboard'))
                    ->icon('heroicon-o-user'),
            ]);
    }
}