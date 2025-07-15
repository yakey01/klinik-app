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
use Cheesegrits\FilamentGoogleMaps\FilamentGoogleMapsPlugin;
use App\Filament\Pages\Auth\CustomLogin;

class ParamedisPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('paramedis')
            ->path('paramedis')
            ->login(false)
            ->default(\App\Filament\Paramedis\Pages\UjiCobaDashboard::class)
            ->brandName('Dokterku - Paramedis')
            ->favicon(asset('favicon.ico'))
            ->colors([
                'primary' => Color::Green,
            ])
            ->darkMode()
            ->resources([
                // \App\Filament\Paramedis\Resources\JaspelResource::class, // Disabled - using React page instead
            ])
            ->pages([
                // \App\Filament\Paramedis\Pages\PremiumDashboard::class, // Temporarily disabled - causing route conflicts
                \App\Filament\Paramedis\Pages\UjiCobaDashboard::class,
                \App\Filament\Paramedis\Pages\PresensiPage::class,
                \App\Filament\Paramedis\Pages\JaspelPremiumPage::class,
                \App\Filament\Paramedis\Pages\JadwalJagaPage::class,
                // \App\Filament\Paramedis\Pages\PresensiMobilePage::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Paramedis/Widgets'), for: 'App\\Filament\\Paramedis\\Widgets')
            ->widgets([
                \App\Filament\Paramedis\Widgets\MobileAttendanceWidget::class,
                \App\Filament\Paramedis\Widgets\MobileScheduleWidget::class,
                \App\Filament\Paramedis\Widgets\MobilePerformanceWidget::class,
                \App\Filament\Paramedis\Widgets\MobileNotificationWidget::class,
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
            ->renderHook(
                'panels::head.end',
                fn () => view('filament.paramedis.partials.mobile-meta')
            )
            ->renderHook(
                'panels::body.start',
                fn () => '<style>
                    .fi-sidebar, .fi-sidebar-backdrop, .fi-sidebar-overlay, .fi-topbar, 
                    .fi-sidebar-nav, .fi-sidebar-group, .fi-sidebar-item, [data-sidebar],
                    aside.fi-sidebar, nav.fi-sidebar-nav, [x-data*="sidebar"], 
                    [x-data*="navigation"], .filament-sidebar, .filament-navigation,
                    .fi-navigation, .fi-nav, body > div > aside, body > div > div > aside {
                        display: none !important;
                        visibility: hidden !important;
                        opacity: 0 !important;
                        width: 0 !important;
                        height: 0 !important;
                        position: absolute !important;
                        left: -9999px !important;
                    }
                    .fi-main { margin-left: 0 !important; width: 100% !important; }
                    .fi-main .fi-page { padding: 0 !important; margin: 0 !important; }
                </style>'
            )
            ->databaseNotifications()
            ->tenant(null)
            ->homeUrl('/paramedis')
            ->sidebarCollapsibleOnDesktop()
            ->collapsedSidebarWidth('0px')
            ->sidebarFullyCollapsibleOnDesktop()
            ->navigationGroups([]);
    }

    public function canAccessPanel(\Illuminate\Contracts\Auth\Authenticatable $user): bool
    {
        \Log::info('ParamedisPanel: canAccessPanel check', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'has_role' => $user->role ? 'YES' : 'NO',
            'role_name' => $user->role?->name ?: 'NULL',
            'role_id' => $user->role_id ?: 'NULL',
            'is_active' => $user->is_active ?? 'NULL'
        ]);
        
        $hasAccess = $user->role && $user->role->name === 'paramedis';
        
        \Log::info('ParamedisPanel: access decision', [
            'user_id' => $user->id,
            'has_access' => $hasAccess ? 'GRANTED' : 'DENIED',
            'reason' => $hasAccess ? 'User has paramedis role' : 'User does not have paramedis role or role is null'
        ]);
        
        return $hasAccess;
    }
}
