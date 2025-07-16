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
use Filament\Support\Enums\ThemeMode;
use Filament\Navigation\NavigationGroup;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\Support\Facades\FilamentView;
use Cheesegrits\FilamentGoogleMaps\FilamentGoogleMapsPlugin;
use Saade\FilamentFullCalendar\FilamentFullCalendarPlugin;
use App\Filament\Pages\Auth\CustomLogin;
use Hasnayeen\Themes\ThemesPlugin;

class AdminPanelProvider extends PanelProvider
{
    public function canAccessPanel(): bool
    {
        return auth()->user()?->hasRole('admin') ?? false;
    }

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login(CustomLogin::class)
            ->brandName('🏥 Dokterku Admin')
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->colors([
                'primary' => Color::rgb('rgb(102, 126, 234)'),
                'secondary' => Color::rgb('rgb(118, 75, 162)'),
                'success' => Color::rgb('rgb(16, 185, 129)'),
                'warning' => Color::rgb('rgb(251, 189, 35)'),
                'danger' => Color::rgb('rgb(239, 68, 68)'),
                'info' => Color::rgb('rgb(58, 191, 248)'),
            ])
            ->darkMode()
            ->spa()
            ->globalSearch()
            ->globalSearchKeyBindings(['command+k', 'ctrl+k'])
            ->resources([
                // 👥 User Management Group
                \App\Filament\Resources\UserResource::class,
                \App\Filament\Resources\RoleResource::class,
                \App\Filament\Resources\PegawaiResource::class,
                
                // 📋 Medical Records Group  
                \App\Filament\Resources\DokterResource::class,
                \App\Filament\Resources\PasienResource::class,
                \App\Filament\Resources\TindakanResource::class,
                \App\Filament\Resources\JenisTindakanResource::class,
                
                // 💰 Financial Management Group
                \App\Filament\Resources\PendapatanResource::class,
                \App\Filament\Resources\PengeluaranResource::class,
                \App\Filament\Resources\DokterUmumJaspelResource::class,
                
                // 📊 Reports & Analytics Group
                \App\Filament\Resources\ReportResource::class,
                \App\Filament\Resources\AuditLogResource::class,
                \App\Filament\Resources\BulkOperationResource::class,
                
                // ⚙️ System Administration Group
                \App\Filament\Resources\SystemSettingResource::class,
                \App\Filament\Resources\FeatureFlagResource::class,
                \App\Filament\Resources\TelegramSettingResource::class,
                \App\Filament\Resources\SecurityLogResource::class,
                \App\Filament\Resources\FaceRecognitionResource::class,
                \App\Filament\Resources\GpsSpoofingDetectionResource::class,
                \App\Filament\Resources\GpsSpoofingConfigResource::class,
                \App\Filament\Resources\ValidasiLokasiResource::class,
                \App\Filament\Resources\UserDeviceResource::class,
                \App\Filament\Resources\EmployeeCardResource::class,
                \App\Filament\Resources\WorkLocationResource::class,
                \App\Filament\Resources\KalenderKerjaResource::class,
                \App\Filament\Resources\JadwalJagaResource::class,
                \App\Filament\Resources\ShiftTemplateResource::class,
                \App\Filament\Resources\PermohonanCutiResource::class,
                \App\Filament\Resources\CutiPegawaiResource::class,
                \App\Filament\Resources\LeaveTypeResource::class,
                \App\Filament\Resources\AbsenceRequestResource::class,
            ])
            ->pages([
                Pages\Dashboard::class,
            ])
            ->widgets([
                Widgets\AccountWidget::class,
                \App\Filament\Widgets\AdminOverviewWidget::class,
                \App\Filament\Widgets\SystemHealthWidget::class,
                \App\Filament\Widgets\ClinicStatsWidget::class,
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
                \App\Http\Middleware\AdminMiddleware::class,
            ])
            ->authGuard('web')
            ->databaseNotifications()
            ->plugins([
                FilamentFullCalendarPlugin::make(),
            ])
            ->tenant(null) // Disable multi-tenancy for now
            ->navigationGroups([
                NavigationGroup::make('🔐 User Management')
                    ->collapsible(),
                NavigationGroup::make('🏥 Medical Records')
                    ->collapsible(),
                NavigationGroup::make('💳 Financial Management')
                    ->collapsible(),
                NavigationGroup::make('📈 Reports & Analytics')
                    ->collapsible(),
                NavigationGroup::make('⚙️ System Administration')
                    ->collapsible(),
            ]);
    }
}