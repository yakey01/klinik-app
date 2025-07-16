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
            ->login(CustomLogin::class)
            ->brandName('📊 Manajer Dashboard')
            ->viteTheme('resources/css/filament/manajer/theme.css')
            ->colors([
                'primary' => Color::Blue,
                'success' => Color::Green,
                'warning' => Color::Amber,
                'danger' => Color::Red,
                'info' => Color::Cyan,
            ])
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
                Pages\Dashboard::class,
                \App\Filament\Manajer\Pages\ExecutiveDashboard::class,
            ])
            ->widgets([
                Widgets\AccountWidget::class,
                // Executive Dashboard Widgets
                \App\Filament\Manajer\Widgets\ExecutiveKPIWidget::class,
                \App\Filament\Manajer\Widgets\FinancialOverviewWidget::class,
                \App\Filament\Manajer\Widgets\TeamPerformanceWidget::class,
                \App\Filament\Manajer\Widgets\OperationsDashboardWidget::class,
                \App\Filament\Manajer\Widgets\ApprovalQueueWidget::class,
                \App\Filament\Manajer\Widgets\StrategicInsightsWidget::class,
            ])
            ->navigationGroups([
                NavigationGroup::make('👥 Personnel Management')
                    ->collapsed(false),
                NavigationGroup::make('📊 Strategic Planning')
                    ->collapsed(false),
                NavigationGroup::make('🏥 Operations Analytics')
                    ->collapsed(true),
                NavigationGroup::make('💰 Financial Oversight')
                    ->collapsed(true),
                NavigationGroup::make('⚡ Workflow Management')
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
                'manajer',
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}