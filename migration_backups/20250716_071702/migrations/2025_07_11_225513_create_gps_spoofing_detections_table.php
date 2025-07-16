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
        Schema::create('gps_spoofing_detections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('device_id')->nullable();
            $table->string('ip_address');
            $table->string('user_agent')->nullable();
            
            // Location Data
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->decimal('accuracy', 8, 2)->nullable();
            $table->decimal('altitude', 8, 2)->nullable();
            $table->decimal('speed', 8, 2)->nullable();
            $table->decimal('heading', 8, 2)->nullable();
            
            // Detection Results
            $table->json('detection_results');
            $table->enum('risk_level', ['low', 'medium', 'high', 'critical'])->default('low');
            $table->integer('risk_score')->default(0); // 0-100
            $table->boolean('is_spoofed')->default(false);
            $table->boolean('is_blocked')->default(false);
            
            // Detection Methods
            $table->boolean('mock_location_detected')->default(false);
            $table->boolean('fake_gps_app_detected')->default(false);
            $table->boolean('developer_mode_detected')->default(false);
            $table->boolean('impossible_travel_detected')->default(false);
            $table->boolean('coordinate_anomaly_detected')->default(false);
            $table->boolean('device_integrity_failed')->default(false);
            
            // Spoofing Indicators
            $table->json('spoofing_indicators')->nullable();
            $table->string('detected_fake_apps')->nullable();
            $table->decimal('travel_speed_kmh', 8, 2)->nullable();
            $table->integer('time_diff_seconds')->nullable();
            $table->decimal('distance_from_last_km', 8, 2)->nullable();
            
            // Action Taken
            $table->enum('action_taken', ['none', 'warning', 'blocked', 'flagged'])->default('none');
            $table->text('admin_notes')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users');
            
            // Additional Context
            $table->string('attendance_type')->nullable(); // check_in, check_out
            $table->timestamp('attempted_at');
            $table->string('location_source')->nullable(); // gps, network, passive
            $table->json('device_fingerprint')->nullable();
            
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['user_id', 'attempted_at']);
            $table->index(['is_spoofed', 'risk_level']);
            $table->index(['ip_address', 'device_id']);
            $table->index('attempted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gps_spoofing_detections');
    }
};
