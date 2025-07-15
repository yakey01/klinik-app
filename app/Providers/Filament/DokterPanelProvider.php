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
use App\Filament\Pages\Auth\CustomLogin;

class DokterPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('dokter')
            ->path('dokter')
            ->login(false)
            ->brandName('Dokterku - Dashboard Dokter')
            ->brandLogoHeight('2rem')
            ->favicon(asset('favicon.ico'))
            ->colors([
                'primary' => Color::Blue,
            ])
            ->darkMode()
            ->sidebarCollapsibleOnDesktop()
            ->userMenuItems([
                'profile' => \Filament\Navigation\MenuItem::make()
                    ->label(fn () => 'Selamat datang, ' . auth()->user()?->name)
                    ->icon('heroicon-o-user-circle')
                    ->url('#')
                    ->sort(-1),
            ])
            ->navigationGroups([
                'Dashboard',
                'Presensi & Jaspel',
                'Pengaturan'
            ])
            ->discoverResources(in: app_path('Filament/Dokter/Resources'), for: 'App\\Filament\\Dokter\\Resources')
            ->discoverPages(in: app_path('Filament/Dokter/Pages'), for: 'App\\Filament\\Dokter\\Pages')
            ->pages([
                \App\Filament\Dokter\Pages\DashboardDokter::class,
                \App\Filament\Dokter\Pages\PresensiMobilePage::class,
                \App\Filament\Dokter\Pages\JaspelMobilePage::class,
            ])
            ->default(\App\Filament\Dokter\Pages\DashboardDokter::class)
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
            ->renderHook(
                'panels::head.end',
                fn () => view('filament.dokter.partials.mobile-meta')
            )
            ->plugin(\Leandrocfe\FilamentApexCharts\FilamentApexChartsPlugin::make());
    }

    public function canAccessPanel(\Illuminate\Contracts\Auth\Authenticatable $user): bool
    {
        \Log::info('DokterPanel: canAccessPanel check', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'has_role' => $user->role ? 'YES' : 'NO',
            'role_name' => $user->role?->name ?: 'NULL',
            'role_id' => $user->role_id ?: 'NULL',
            'is_active' => $user->is_active ?? 'NULL'
        ]);
        
        $hasAccess = $user->role && $user->role->name === 'dokter';
        
        \Log::info('DokterPanel: access decision', [
            'user_id' => $user->id,
            'has_access' => $hasAccess ? 'GRANTED' : 'DENIED',
            'reason' => $hasAccess ? 'User has dokter role' : 'User does not have dokter role or role is null'
        ]);
        
        // If user doesn't have access but has a different role, redirect them to their correct panel
        if (!$hasAccess && $user->role) {
            $roleName = $user->role->name;
            $redirectUrl = match($roleName) {
                'admin' => '/admin',
                'petugas' => '/petugas', 
                'manajer' => '/manager/dashboard',
                'bendahara' => '/bendahara',
                'paramedis' => '/paramedis',
                'non_paramedis' => '/non-paramedic/dashboard',
                default => '/login'
            };
            
            \Log::info('DokterPanel: Redirecting user to correct panel', [
                'user_id' => $user->id,
                'role' => $roleName,
                'redirect_to' => $redirectUrl
            ]);
            
            // Store the redirect URL in session to be handled by middleware
            session(['role_redirect' => $redirectUrl]);
        }
        
        return $hasAccess;
    }
}
