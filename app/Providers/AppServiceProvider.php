<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register GPS validation service
        $this->app->singleton(\App\Services\GpsValidationService::class);
        
        // Register session manager service
        $this->app->singleton(\App\Services\SessionManager::class);
        
        // Register token service
        $this->app->singleton(\App\Services\TokenService::class);
        
        // Register biometric service
        $this->app->singleton(\App\Services\BiometricService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Add CSRF token to Filament views
        \Filament\Support\Facades\FilamentView::registerRenderHook(
            'panels::head.end',
            fn (): string => '<meta name="csrf-token" content="' . csrf_token() . '">'
        );
        
        // Register audit observer for automated logging
        $this->registerAuditObserver();
    }

    /**
     * Register audit observer for automatic model logging
     */
    private function registerAuditObserver(): void
    {
        $auditableModels = [
            \App\Models\User::class,
            \App\Models\SystemSetting::class,
            \App\Models\FeatureFlag::class,
            \App\Models\Pasien::class,
            \App\Models\Tindakan::class,
            \App\Models\Pendapatan::class,
            \App\Models\Pengeluaran::class,
            \App\Models\Role::class,
            \App\Models\Pegawai::class,
            \App\Models\Dokter::class,
            \App\Models\TelegramSetting::class,
        ];

        foreach ($auditableModels as $model) {
            if (class_exists($model)) {
                $model::observe(\App\Observers\AuditObserver::class);
            }
        }
    }
}
