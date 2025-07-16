<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('gps_spoofing_settings', function (Blueprint $table) {
            $table->id();
            
            // General Settings
            $table->boolean('is_enabled')->default(true);
            $table->string('name')->default('GPS Anti-Spoofing Configuration');
            $table->text('description')->nullable();
            
            // Detection Thresholds
            $table->integer('mock_location_score')->default(25); // 0-100
            $table->integer('fake_gps_app_score')->default(30);
            $table->integer('developer_mode_score')->default(20);
            $table->integer('impossible_travel_score')->default(35);
            $table->integer('coordinate_anomaly_score')->default(15);
            $table->integer('device_integrity_score')->default(25);
            
            // Risk Level Thresholds
            $table->integer('low_risk_threshold')->default(30);      // 0-29 = Low
            $table->integer('medium_risk_threshold')->default(60);   // 30-59 = Medium
            $table->integer('high_risk_threshold')->default(80);     // 60-79 = High
            // 80-100 = Critical
            
            // Action Thresholds
            $table->integer('warning_threshold')->default(50);       // Send warning
            $table->integer('flagged_threshold')->default(60);       // Flag for review
            $table->integer('blocked_threshold')->default(80);       // Auto block
            
            // Detection Method Toggles
            $table->boolean('detect_mock_location')->default(true);
            $table->boolean('detect_fake_gps_apps')->default(true);
            $table->boolean('detect_developer_mode')->default(true);
            $table->boolean('detect_impossible_travel')->default(true);
            $table->boolean('detect_coordinate_anomaly')->default(true);
            $table->boolean('detect_device_integrity')->default(true);
            
            // Travel Analysis Settings
            $table->decimal('max_travel_speed_kmh', 8, 2)->default(120.00); // Max realistic speed
            $table->integer('min_time_between_locations')->default(30); // Seconds
            $table->decimal('accuracy_threshold', 8, 2)->default(1.0); // Suspiciously perfect accuracy
            
            // Notification Settings
            $table->boolean('send_email_alerts')->default(true);
            $table->boolean('send_realtime_alerts')->default(true);
            $table->boolean('send_critical_only')->default(false); // Only critical alerts
            $table->json('notification_recipients')->nullable(); // Email addresses
            
            // Blocking Settings
            $table->boolean('auto_block_enabled')->default(true);
            $table->integer('block_duration_hours')->default(24); // How long to block
            $table->boolean('require_admin_unblock')->default(true);
            
            // Whitelist Settings
            $table->json('whitelisted_ips')->nullable();
            $table->json('whitelisted_devices')->nullable();
            $table->json('trusted_locations')->nullable(); // Coordinates that are always allowed
            
            // Known Fake GPS Apps List
            $table->json('fake_gps_apps_database')->nullable();
            
            // Logging Settings
            $table->boolean('log_all_attempts')->default(true);
            $table->boolean('log_low_risk_only')->default(false);
            $table->integer('retention_days')->default(90); // Keep logs for X days
            
            // Admin Settings
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamp('last_updated_at')->nullable();
            
            $table->timestamps();
            
            // Only one settings record should exist
            $table->unique('id');
        });
        
        // Insert default settings
        DB::table('gps_spoofing_settings')->insert([
            'id' => 1,
            'is_enabled' => true,
            'name' => 'GPS Anti-Spoofing Configuration',
            'description' => 'Konfigurasi sistem deteksi GPS spoofing untuk keamanan presensi',
            'fake_gps_apps_database' => json_encode([
                'com.lexa.fakegps',
                'com.incorporateapps.fakegps',
                'com.blogspot.newapphorizons.fakegps',
                'com.app.fakegps',
                'fake.gps.location.spoof',
                'fake.gps.location.spoofer.free',
                'com.theappninjas.gpsjoystick',
                'com.flydroid.gpsjoystick',
                'com.gsmarena.gpsjoystick',
                'com.mock.locations.app',
                'com.mock.location.pro',
            ]),
            'notification_recipients' => json_encode([]),
            'whitelisted_ips' => json_encode([]),
            'whitelisted_devices' => json_encode([]),
            'trusted_locations' => json_encode([]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gps_spoofing_settings');
    }
};
