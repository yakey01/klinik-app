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

class BendaharaPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('bendahara')
            ->path('bendahara')
            ->login(CustomLogin::class)
            ->brandName('Bendahara Dashboard')
            ->colors([
                'primary' => Color::Amber,
                'success' => Color::Green,
                'warning' => Color::Orange,
                'danger' => Color::Red,
                'info' => Color::Blue,
            ])
            ->sidebarCollapsibleOnDesktop()
            ->pages([
                \App\Filament\Bendahara\Pages\BendaharaDashboard::class,
            ])
            ->resources([
                // RESTORED: Unified Validation Centers (PURE FILAMENT)
                \App\Filament\Bendahara\Resources\ValidationCenterResource::class,
                \App\Filament\Bendahara\Resources\UnifiedFinancialValidationResource::class,
                
                // RESTORED: Legacy Financial Management (PURE FILAMENT)
                \App\Filament\Bendahara\Resources\ValidasiPendapatanResource::class,
                \App\Filament\Bendahara\Resources\ValidasiPengeluaranResource::class,
                
                // RESTORED: Manajemen Jaspel Group (PURE FILAMENT)
                \App\Filament\Bendahara\Resources\ValidasiJaspelResource::class,
                // \App\Filament\Bendahara\Resources\BudgetPlanningResource::class, // SKIP: Missing budget_plans table
                
                // RESTORED: Laporan Keuangan Group (PURE FILAMENT)
                \App\Filament\Bendahara\Resources\LaporanKeuanganResource::class,
                
                // RESTORED: Audit & Kontrol Group (PURE FILAMENT)
                \App\Filament\Bendahara\Resources\AuditTrailResource::class,
                \App\Filament\Bendahara\Resources\FinancialAlertResource::class,
                
                // RESTORED: Validasi Data Group (PURE FILAMENT)
                \App\Filament\Bendahara\Resources\ValidasiJumlahPasienResource::class,
            ])
            ->widgets([
                \App\Filament\Bendahara\Widgets\InteractiveDashboardWidget::class,
                \App\Filament\Bendahara\Widgets\BudgetTrackingWidget::class,
                \App\Filament\Bendahara\Widgets\LanguageSwitcherWidget::class,
            ])
            ->navigationGroups([
                NavigationGroup::make('Dashboard')
                    ->collapsed(false),
                // RESTORED: All navigation groups (PURE FILAMENT)
                NavigationGroup::make('Validasi Transaksi')
                    ->collapsed(true)
                    ->collapsible(),
                NavigationGroup::make('Manajemen Jaspel')
                    ->collapsed(true)
                    ->collapsible(),
                NavigationGroup::make('Laporan Keuangan')
                    ->collapsed(true)
                    ->collapsible(),
                NavigationGroup::make('Audit & Kontrol')
                    ->collapsed(true)
                    ->collapsible(),
                NavigationGroup::make('Validasi Data')
                    ->collapsed(true)
                    ->collapsible(),
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                \App\Http\Middleware\SessionCleanupMiddleware::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                \App\Http\Middleware\RefreshCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->authGuard('web');
    }
}