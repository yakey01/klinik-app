<?php

namespace App\Filament\Resources\GpsSpoofingSettingResource\Pages;

use App\Filament\Resources\GpsSpoofingSettingResource;
use App\Models\GpsSpoofingSetting;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;
use Filament\Notifications\Notification;

class ManageGpsSpoofingSettings extends ManageRecords
{
    protected static string $resource = GpsSpoofingSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('🆕 New Configuration')
                ->modalHeading('📝 Create GPS Anti-Spoofing Configuration')
                ->visible(fn () => GpsSpoofingSetting::count() === 0)
                ->mutateFormDataUsing(function (array $data): array {
                    $data['created_by'] = auth()->id();
                    $data['updated_by'] = auth()->id();
                    $data['last_updated_at'] = now();
                    return $data;
                })
                ->after(function () {
                    Notification::make()
                        ->title('✅ GPS Anti-Spoofing Configuration Created')
                        ->body('The GPS spoofing detection system is now configured and ready to use.')
                        ->success()
                        ->send();
                }),
                
            Actions\Action::make('test_detection')
                ->label('🧪 Test Detection')
                ->icon('heroicon-o-beaker')
                ->color('warning')
                ->action(function () {
                    $settings = GpsSpoofingSetting::current();
                    
                    // Sample test data simulating spoofed GPS
                    $testData = [
                        'latitude' => -6.2088,
                        'longitude' => 106.8238,
                        'accuracy' => 0.5, // Perfect accuracy (suspicious)
                        'device_fingerprint' => [
                            'mock_location_enabled' => true,
                            'developer_mode_enabled' => true,
                            'installed_apps' => ['com.lexa.fakegps'],
                        ]
                    ];
                    
                    $service = app(\App\Services\GpsSpoofingDetectionService::class);
                    $result = $service->analyzeGpsData(auth()->user(), $testData);
                    
                    $color = match($result['risk_level']) {
                        'low' => 'success',
                        'medium' => 'warning', 
                        'high' => 'danger',
                        'critical' => 'danger',
                        default => 'info'
                    };
                    
                    Notification::make()
                        ->title('🧪 Detection Test Results')
                        ->body("
                            **Risk Score:** {$result['risk_score']}%
                            **Risk Level:** " . ucfirst($result['risk_level']) . "
                            **Action:** " . ucfirst($result['action_taken']) . "
                            **Detected Methods:** " . implode(', ', $result['detection_methods'])
                        )
                        ->color($color)
                        ->persistent()
                        ->send();
                })
                ->visible(fn () => GpsSpoofingSetting::count() > 0),
                
            Actions\Action::make('reset_to_defaults')
                ->label('🔄 Reset to Defaults')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->requiresConfirmation()
                ->modalHeading('🔄 Reset Configuration to Defaults')
                ->modalDescription('This will reset all settings to factory defaults. Any custom configuration will be lost.')
                ->action(function () {
                    $settings = GpsSpoofingSetting::current();
                    
                    $defaults = [
                        'is_enabled' => true,
                        'name' => 'GPS Anti-Spoofing Configuration',
                        'description' => 'Konfigurasi sistem deteksi GPS spoofing untuk keamanan presensi',
                        'mock_location_score' => 25,
                        'fake_gps_app_score' => 30,
                        'developer_mode_score' => 20,
                        'impossible_travel_score' => 35,
                        'coordinate_anomaly_score' => 15,
                        'device_integrity_score' => 25,
                        'low_risk_threshold' => 30,
                        'medium_risk_threshold' => 60,
                        'high_risk_threshold' => 80,
                        'warning_threshold' => 50,
                        'flagged_threshold' => 60,
                        'blocked_threshold' => 80,
                        'detect_mock_location' => true,
                        'detect_fake_gps_apps' => true,
                        'detect_developer_mode' => true,
                        'detect_impossible_travel' => true,
                        'detect_coordinate_anomaly' => true,
                        'detect_device_integrity' => true,
                        'max_travel_speed_kmh' => 120.00,
                        'min_time_between_locations' => 30,
                        'accuracy_threshold' => 1.0,
                        'send_email_alerts' => true,
                        'send_realtime_alerts' => true,
                        'send_critical_only' => false,
                        'auto_block_enabled' => true,
                        'block_duration_hours' => 24,
                        'require_admin_unblock' => true,
                        'log_all_attempts' => true,
                        'log_low_risk_only' => false,
                        'retention_days' => 90,
                        'updated_by' => auth()->id(),
                        'last_updated_at' => now(),
                    ];
                    
                    $settings->update($defaults);
                    
                    Notification::make()
                        ->title('🔄 Configuration Reset')
                        ->body('All settings have been reset to factory defaults.')
                        ->success()
                        ->send();
                })
                ->visible(fn () => GpsSpoofingSetting::count() > 0),
        ];
    }

    public function getTitle(): string
    {
        return '⚙️ GPS Spoofing Settings';
    }

    public function getSubheading(): string
    {
        $settings = GpsSpoofingSetting::current();
        $status = $settings->is_enabled ? '🟢 Active' : '🔴 Inactive';
        $enabledMethods = count($settings->getEnabledMethods());
        
        return "Status: {$status} | Detection Methods: {$enabledMethods}/6 | Block Threshold: {$settings->blocked_threshold}%";
    }
}
