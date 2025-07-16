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
        Schema::create('gps_spoofing_configs', function (Blueprint $table) {
            $table->id();
            
            // Basic Settings
            $table->string('config_name')->default('default');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            
            // Travel Detection Thresholds
            $table->decimal('max_travel_speed_kmh', 8, 2)->default(120.00); // Max reasonable travel speed
            $table->integer('min_time_diff_seconds')->default(300); // 5 minutes minimum
            $table->decimal('max_distance_km', 8, 2)->default(50.00); // Max distance in timeframe
            
            // GPS Accuracy Thresholds
            $table->decimal('min_gps_accuracy_meters', 8, 2)->default(50.00); // Minimum acceptable accuracy
            $table->decimal('max_gps_accuracy_meters', 8, 2)->default(1000.00); // Maximum acceptable accuracy
            
            // Risk Scoring Weights (0-100)
            $table->integer('mock_location_weight')->default(40);
            $table->integer('fake_gps_app_weight')->default(35);
            $table->integer('developer_mode_weight')->default(15);
            $table->integer('impossible_travel_weight')->default(50);
            $table->integer('coordinate_anomaly_weight')->default(25);
            $table->integer('device_integrity_weight')->default(30);
            
            // Risk Level Thresholds (0-100)
            $table->integer('low_risk_threshold')->default(20);
            $table->integer('medium_risk_threshold')->default(40);
            $table->integer('high_risk_threshold')->default(70);
            $table->integer('critical_risk_threshold')->default(85);
            
            // Auto-Action Settings
            $table->boolean('auto_block_critical')->default(true);
            $table->boolean('auto_block_high_risk')->default(false);
            $table->boolean('auto_flag_medium_risk')->default(true);
            $table->boolean('auto_warning_low_risk')->default(false);
            
            // Detection Feature Toggles
            $table->boolean('enable_mock_location_detection')->default(true);
            $table->boolean('enable_fake_gps_detection')->default(true);
            $table->boolean('enable_developer_mode_detection')->default(true);
            $table->boolean('enable_impossible_travel_detection')->default(true);
            $table->boolean('enable_coordinate_anomaly_detection')->default(true);
            $table->boolean('enable_device_integrity_check')->default(true);
            
            // Monitoring Settings
            $table->integer('data_retention_days')->default(90);
            $table->integer('polling_interval_seconds')->default(15);
            $table->boolean('enable_real_time_alerts')->default(true);
            $table->boolean('enable_email_notifications')->default(false);
            $table->string('notification_email')->nullable();
            
            // Whitelist Settings
            $table->json('whitelisted_ips')->nullable(); // Whitelisted IP addresses
            $table->json('whitelisted_devices')->nullable(); // Whitelisted device IDs
            $table->json('trusted_locations')->nullable(); // Trusted office locations
            
            // Advanced Settings
            $table->integer('max_failed_attempts_per_hour')->default(5);
            $table->integer('temporary_block_duration_minutes')->default(30);
            $table->boolean('require_admin_review_for_unblock')->default(true);
            
            // Audit Trail
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            
            $table->timestamps();
            
            // Indexes
            $table->index(['is_active', 'config_name']);
            $table->index('created_by');
            $table->index('updated_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gps_spoofing_configs');
    }
};
