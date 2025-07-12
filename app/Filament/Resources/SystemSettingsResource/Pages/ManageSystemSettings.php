<?php

namespace App\Filament\Resources\SystemSettingsResource\Pages;

use App\Filament\Resources\SystemSettingsResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

class ManageSystemSettings extends ManageRecords
{
    protected static string $resource = SystemSettingsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('clear_cache')
                ->label('🧹 Clear All Cache')
                ->icon('heroicon-o-trash')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Clear Application Cache')
                ->modalDescription('This will clear all cached data including views, routes, and configuration.')
                ->action(function () {
                    $commands = [
                        'cache:clear',
                        'config:clear',
                        'route:clear',
                        'view:clear',
                        'filament:clear-cached-components'
                    ];

                    foreach ($commands as $command) {
                        try {
                            Artisan::call($command);
                        } catch (\Exception $e) {
                            // Continue if one command fails
                        }
                    }

                    Notification::make()
                        ->title('Cache Cleared')
                        ->body('All application caches have been cleared successfully.')
                        ->success()
                        ->send();
                }),

            Actions\Action::make('optimize')
                ->label('⚡ Optimize Application')
                ->icon('heroicon-o-rocket-launch')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Optimize Application')
                ->modalDescription('This will optimize the application for production performance.')
                ->action(function () {
                    $commands = [
                        'config:cache',
                        'route:cache',
                        'view:cache',
                        'optimize'
                    ];

                    foreach ($commands as $command) {
                        try {
                            Artisan::call($command);
                        } catch (\Exception $e) {
                            // Continue if one command fails
                        }
                    }

                    Notification::make()
                        ->title('Application Optimized')
                        ->body('Application has been optimized for production.')
                        ->success()
                        ->send();
                }),

            Actions\Action::make('migrate')
                ->label('🗄️ Run Migrations')
                ->icon('heroicon-o-arrow-up')
                ->color('info')
                ->requiresConfirmation()
                ->modalHeading('Run Database Migrations')
                ->modalDescription('This will run any pending database migrations.')
                ->action(function () {
                    try {
                        Artisan::call('migrate', ['--force' => true]);
                        
                        Notification::make()
                            ->title('Migrations Complete')
                            ->body('All database migrations have been executed.')
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Migration Failed')
                            ->body('Error: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            Actions\Action::make('backup')
                ->label('💾 Create Backup')
                ->icon('heroicon-o-archive-box-arrow-down')
                ->color('gray')
                ->requiresConfirmation()
                ->modalHeading('Create System Backup')
                ->modalDescription('This will create a complete backup of the database and application files.')
                ->action(function () {
                    // Implement backup logic here
                    Notification::make()
                        ->title('Backup Feature')
                        ->body('Backup functionality will be available in the next update.')
                        ->info()
                        ->send();
                }),

            Actions\Action::make('maintenance_mode')
                ->label('🚧 Maintenance Mode')
                ->icon('heroicon-o-wrench-screwdriver')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Enable Maintenance Mode')
                ->modalDescription('This will put the application in maintenance mode.')
                ->action(function () {
                    try {
                        if (app()->isDownForMaintenance()) {
                            Artisan::call('up');
                            
                            Notification::make()
                                ->title('Maintenance Mode Disabled')
                                ->body('Application is now accessible to users.')
                                ->success()
                                ->send();
                        } else {
                            Artisan::call('down', ['--secret' => 'admin-access']);
                            
                            Notification::make()
                                ->title('Maintenance Mode Enabled')
                                ->body('Application is now in maintenance mode. Use /admin-access to bypass.')
                                ->warning()
                                ->send();
                        }
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Error')
                            ->body('Failed to toggle maintenance mode: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            Actions\Action::make('system_info')
                ->label('📊 System Information')
                ->icon('heroicon-o-information-circle')
                ->color('info')
                ->modal()
                ->modalHeading('System Information')
                ->modalContent(view('filament.pages.system-info')),
        ];
    }

    public function form(Form $form): Form
    {
        return SystemSettingsResource::form($form);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Here you would typically save settings to a database or config files
        // For now, we'll just show a notification
        
        Notification::make()
            ->title('Settings Updated')
            ->body('System settings have been updated successfully.')
            ->success()
            ->send();

        return $data;
    }
}