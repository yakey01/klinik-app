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

class ManajerPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('manajer')
            ->path('manajer')
            ->login(false)
            ->brandName('📊 Executive Dashboard')
            ->viteTheme('resources/css/filament/manajer/theme.css')
            ->colors([
                'primary' => Color::hex('#6366F1'), // Professional Indigo
                'secondary' => Color::hex('#8B5CF6'), // Purple accent
                'success' => Color::hex('#10B981'), // Success green
                'warning' => Color::hex('#F59E0B'), // Warning amber
                'danger' => Color::hex('#EF4444'), // Error red
                'info' => Color::hex('#06B6D4'), // Info cyan
            ])
            ->darkMode(true)
            ->resources([
                // 👥 Personnel Management Group
                \App\Filament\Manajer\Resources\EmployeePerformanceResource::class,
                \App\Filament\Manajer\Resources\LeaveApprovalResource::class,
                
                // 📊 Strategic Planning Group
                \App\Filament\Manajer\Resources\StrategicPlanningResource::class,
                
                // 🏥 Operations Analytics Group
                \App\Filament\Manajer\Resources\OperationalAnalyticsResource::class,
                
                // 💰 Financial Oversight Group
                \App\Filament\Manajer\Resources\FinancialOversightResource::class,
                
                // ⚡ Workflow Management Group
                \App\Filament\Manajer\Resources\ApprovalWorkflowResource::class,
            ])
            ->pages([
                \App\Filament\Manajer\Pages\ExecutiveDashboard::class,
                \App\Filament\Manajer\Pages\EnhancedManajerDashboard::class,
            ])
            ->widgets([
                Widgets\AccountWidget::class,
                // Executive Dashboard Widgets - Enhanced
                \App\Filament\Manajer\Widgets\ManajerHeroStatsWidget::class,
                \App\Filament\Manajer\Widgets\ManajerFinancialInsightsWidget::class,
                \App\Filament\Manajer\Widgets\ManajerTeamPerformanceWidget::class,
                \App\Filament\Manajer\Widgets\ManajerOperationalDashboardWidget::class,
                \App\Filament\Manajer\Widgets\ManajerStrategicMetricsWidget::class,
                \App\Filament\Manajer\Widgets\ManajerApprovalWorkflowWidget::class,
                
                // Legacy Widgets (for backwards compatibility)
                \App\Filament\Manajer\Widgets\ExecutiveKPIWidget::class,
                \App\Filament\Manajer\Widgets\FinancialOverviewWidget::class,
                \App\Filament\Manajer\Widgets\TeamPerformanceWidget::class,
            ])
            ->navigationGroups([
                NavigationGroup::make('📊 Executive Overview')
                    ->collapsed(false),
                NavigationGroup::make('💼 Strategic Planning')
                    ->collapsed(false),
                NavigationGroup::make('👥 Team Management')
                    ->collapsed(true),
                NavigationGroup::make('💰 Financial Control')
                    ->collapsed(true),
                NavigationGroup::make('⚡ Workflow Automation')
                    ->collapsed(true),
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
                \App\Http\Middleware\RedirectToUnifiedAuth::class,
                Authenticate::class,
                \App\Http\Middleware\ManajerMiddleware::class,
            ])
            ->authGuard('web');
    }
}