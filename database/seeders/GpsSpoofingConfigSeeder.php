<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\GpsSpoofingConfig;
use App\Models\User;

class GpsSpoofingConfigSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminUser = User::where('email', 'admin@dokterku.com')->first();
        
        GpsSpoofingConfig::create([
            'config_name' => 'Default Security Config',
            'description' => 'Konfigurasi keamanan GPS default untuk sistem Dokterku. Menggunakan pengaturan standar untuk deteksi GPS spoofing.',
            'is_active' => true,
            
            // Travel Detection Thresholds
            'max_travel_speed_kmh' => 120.00,
            'min_time_diff_seconds' => 300, // 5 minutes
            'max_distance_km' => 50.00,
            
            // GPS Accuracy Thresholds
            'min_gps_accuracy_meters' => 50.00,
            'max_gps_accuracy_meters' => 1000.00,
            
            // Risk Scoring Weights (0-100)
            'mock_location_weight' => 40,
            'fake_gps_app_weight' => 35,
            'developer_mode_weight' => 15,
            'impossible_travel_weight' => 50,
            'coordinate_anomaly_weight' => 25,
            'device_integrity_weight' => 30,
            
            // Risk Level Thresholds (0-100)
            'low_risk_threshold' => 20,
            'medium_risk_threshold' => 40,
            'high_risk_threshold' => 70,
            'critical_risk_threshold' => 85,
            
            // Auto-Action Settings
            'auto_block_critical' => true,
            'auto_block_high_risk' => false,
            'auto_flag_medium_risk' => true,
            'auto_warning_low_risk' => false,
            
            // Detection Feature Toggles
            'enable_mock_location_detection' => true,
            'enable_fake_gps_detection' => true,
            'enable_developer_mode_detection' => true,
            'enable_impossible_travel_detection' => true,
            'enable_coordinate_anomaly_detection' => true,
            'enable_device_integrity_check' => true,
            
            // Monitoring Settings
            'data_retention_days' => 90,
            'polling_interval_seconds' => 15,
            'enable_real_time_alerts' => true,
            'enable_email_notifications' => false,
            'notification_email' => null,
            
            // Whitelist Settings
            'whitelisted_ips' => [
                [
                    'ip' => '192.168.1.1',
                    'description' => 'Office Router'
                ],
                [
                    'ip' => '10.0.0.1',
                    'description' => 'Internal Network Gateway'
                ]
            ],
            'whitelisted_devices' => [],
            'trusted_locations' => [
                [
                    'name' => 'Kantor Pusat Dokterku',
                    'latitude' => -6.2088,
                    'longitude' => 106.8456,
                    'radius' => 100
                ]
            ],
            
            // Advanced Settings
            'max_failed_attempts_per_hour' => 5,
            'temporary_block_duration_minutes' => 30,
            'require_admin_review_for_unblock' => true,
            
            // Device Management Settings
            'auto_register_devices' => true,
            'max_devices_per_user' => 1,
            'require_admin_approval_for_new_devices' => false,
            'device_limit_policy' => 'strict',
            'device_auto_cleanup_days' => 30,
            'auto_revoke_excess_devices' => true,
            
            'created_by' => $adminUser?->id,
            'updated_by' => $adminUser?->id,
        ]);
        
        // Create a high security config (inactive by default)
        GpsSpoofingConfig::create([
            'config_name' => 'High Security Config',
            'description' => 'Konfigurasi keamanan tinggi dengan deteksi yang lebih ketat dan tindakan otomatis yang lebih agresif.',
            'is_active' => false,
            
            // Travel Detection Thresholds (more strict)
            'max_travel_speed_kmh' => 80.00,
            'min_time_diff_seconds' => 600, // 10 minutes
            'max_distance_km' => 30.00,
            
            // GPS Accuracy Thresholds (more strict)
            'min_gps_accuracy_meters' => 20.00,
            'max_gps_accuracy_meters' => 500.00,
            
            // Risk Scoring Weights (higher weights)
            'mock_location_weight' => 50,
            'fake_gps_app_weight' => 45,
            'developer_mode_weight' => 25,
            'impossible_travel_weight' => 60,
            'coordinate_anomaly_weight' => 35,
            'device_integrity_weight' => 40,
            
            // Risk Level Thresholds (lower thresholds)
            'low_risk_threshold' => 15,
            'medium_risk_threshold' => 30,
            'high_risk_threshold' => 50,
            'critical_risk_threshold' => 70,
            
            // Auto-Action Settings (more aggressive)
            'auto_block_critical' => true,
            'auto_block_high_risk' => true,
            'auto_flag_medium_risk' => true,
            'auto_warning_low_risk' => true,
            
            // Detection Feature Toggles (all enabled)
            'enable_mock_location_detection' => true,
            'enable_fake_gps_detection' => true,
            'enable_developer_mode_detection' => true,
            'enable_impossible_travel_detection' => true,
            'enable_coordinate_anomaly_detection' => true,
            'enable_device_integrity_check' => true,
            
            // Monitoring Settings (more frequent)
            'data_retention_days' => 180,
            'polling_interval_seconds' => 10,
            'enable_real_time_alerts' => true,
            'enable_email_notifications' => true,
            'notification_email' => 'admin@dokterku.com',
            
            // Whitelist Settings (empty for high security)
            'whitelisted_ips' => [],
            'whitelisted_devices' => [],
            'trusted_locations' => [
                [
                    'name' => 'Kantor Pusat Dokterku',
                    'latitude' => -6.2088,
                    'longitude' => 106.8456,
                    'radius' => 50 // Smaller radius for high security
                ]
            ],
            
            // Advanced Settings (more strict)
            'max_failed_attempts_per_hour' => 3,
            'temporary_block_duration_minutes' => 60,
            'require_admin_review_for_unblock' => true,
            
            // Device Management Settings (stricter)
            'auto_register_devices' => true,
            'max_devices_per_user' => 1,
            'require_admin_approval_for_new_devices' => true,
            'device_limit_policy' => 'strict',
            'device_auto_cleanup_days' => 14,
            'auto_revoke_excess_devices' => true,
            
            'created_by' => $adminUser?->id,
            'updated_by' => $adminUser?->id,
        ]);
    }
}
