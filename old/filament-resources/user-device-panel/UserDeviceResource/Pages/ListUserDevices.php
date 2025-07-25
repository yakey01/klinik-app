<?php

namespace App\Filament\Resources\UserDeviceResource\Pages;

use App\Filament\Resources\UserDeviceResource;
use App\Models\UserDevice;
use App\Models\GpsSpoofingConfig;
use App\Filament\Widgets\DeviceManagementStatsWidget;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Notifications\Notification;

class ListUserDevices extends ListRecords
{
    protected static string $resource = UserDeviceResource::class;
    
    public function getTitle(): string
    {
        return '📱 Device Management';
    }

    
    protected function getHeaderWidgets(): array
    {
        return [
            DeviceManagementStatsWidget::class,
        ];
    }
    
    protected function getHeaderActions(): array
    {
        $config = GpsSpoofingConfig::getActiveConfig();
        
        return [
            Actions\Action::make('device_policy_info')
                ->label('📋 Device Policy (Admin Configurable)')
                ->icon('heroicon-o-information-circle')
                ->color('info')
                ->action(function () use ($config) {
                    if (!$config) {
                        Notification::make()
                            ->title('⚠️ No Device Policy Configuration')
                            ->body('No GPS spoofing configuration is currently active. Device management may not work properly. Please configure device settings through GPS Security Settings.')
                            ->warning()
                            ->persistent()
                            ->actions([
                                \Filament\Notifications\Actions\Action::make('configure')
                                    ->label('🔧 Configure Now')
                                    ->url(route('filament.admin.resources.gps-spoofing-configs.index'))
                                    ->button()
                            ])
                            ->send();
                        return;
                    }
                    
                    $policyIcon = match($config->device_limit_policy) {
                        'strict' => '🔒',
                        'warn' => '⚠️',
                        'flexible' => '🔄',
                        default => '❓'
                    };
                    
                    $approvalIcon = $config->require_admin_approval_for_new_devices ? '👥 Required' : '🚫 Not Required';
                    $autoRegisterIcon = $config->auto_register_devices ? '✅ Enabled' : '❌ Disabled';
                    
                    Notification::make()
                        ->title('📋 Current Device Policy Configuration (Admin Configurable)')
                        ->body("
                            Current Settings:
                            🔄 Auto-Registration: {$autoRegisterIcon}
                            📏 Max Devices per User: {$config->max_devices_per_user} devices (1-10 devices)
                            {$policyIcon} Device Limit Policy: " . ucfirst($config->device_limit_policy) . " (Strict/Warn/Flexible)
                            👥 Admin Approval: {$approvalIcon}
                            🧹 Auto-Cleanup: {$config->device_auto_cleanup_days} days (1-365 days)
                            
                            ⚙️ Admin dapat mengubah semua pengaturan melalui:
                            GPS Security Settings → Device Management tab
                            
                            📋 Pengaturan yang dapat dikonfigurasi:
                            • Max devices per user (1-10)
                            • Device limit policy (3 mode)
                            • Admin approval requirement
                            • Auto-cleanup period (1-365 days)
                            • Auto-revoke excess devices
                        ")
                        ->info()
                        ->persistent()
                        ->actions([
                            \Filament\Notifications\Actions\Action::make('configure')
                                ->label('🔧 Configure Settings')
                                ->url(route('filament.admin.resources.gps-spoofing-configs.index'))
                                ->button()
                        ])
                        ->send();
                })
                ->badge(function () use ($config) {
                    if (!$config) return 'No Config';
                    return ucfirst($config->device_limit_policy);
                })
                ->badgeColor(function () use ($config) {
                    if (!$config) return 'danger';
                    return match($config->device_limit_policy) {
                        'strict' => 'danger',
                        'warn' => 'warning', 
                        'flexible' => 'success',
                        default => 'gray'
                    };
                }),
                
            Actions\Action::make('device_statistics')
                ->label('📊 Device Statistics')
                ->icon('heroicon-o-chart-bar')
                ->color('info')
                ->action(function () {
                    $stats = [
                        'total_devices' => UserDevice::count(),
                        'active_devices' => UserDevice::where('is_active', true)->count(),
                        'verified_devices' => UserDevice::whereNotNull('verified_at')->count(),
                        'users_with_multiple' => UserDevice::select('user_id')
                            ->where('is_active', true)
                            ->groupBy('user_id')
                            ->havingRaw('COUNT(*) > 1')
                            ->count(),
                    ];
                    
                    Notification::make()
                        ->title('📊 Device Statistics')
                        ->body("Total: {$stats['total_devices']} | Active: {$stats['active_devices']} | Verified: {$stats['verified_devices']} | Multiple devices: {$stats['users_with_multiple']} users")
                        ->info()
                        ->persistent()
                        ->send();
                }),
                
            Actions\Action::make('cleanup_inactive')
                ->label('🧹 Cleanup Inactive Devices')
                ->icon('heroicon-o-trash')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Cleanup Inactive Devices')
                ->modalDescription('This will permanently delete devices that have been inactive for more than 30 days.')
                ->action(function () {
                    $deleted = UserDevice::where('is_active', false)
                        ->where('updated_at', '<', now()->subDays(30))
                        ->forceDelete();
                        
                    Notification::make()
                        ->title('🧹 Cleanup completed')
                        ->body("Deleted {$deleted} inactive devices older than 30 days")
                        ->success()
                        ->send();
                }),
                
            Actions\CreateAction::make()
                ->label('Add Device')
                ->icon('heroicon-m-plus')
                ->color('primary'),
        ];
    }
}
