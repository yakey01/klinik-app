<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MERGED MIGRATION: Attendances Table Enhancements
 * 
 * This migration consolidates the following original migrations:
 * - 2025_07_11_163901_create_attendances_table.php
 * - 2025_07_11_165455_add_device_fields_to_attendances_table.php
 * - 2025_07_14_010934_add_gps_fields_to_attendances_table.php
 * 
 * Risk Level: LOW
 * Dependencies: users, work_locations tables must exist
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('work_location_id')->constrained()->onDelete('cascade');
            $table->dateTime('check_in_time');
            $table->dateTime('check_out_time')->nullable();
            $table->string('status')->default('present');
            $table->text('notes')->nullable();
            
            // From: add_device_fields_to_attendances_table.php
            $table->string('device_id')->nullable();
            $table->string('device_name')->nullable();
            $table->string('device_type')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            
            // From: add_gps_fields_to_attendances_table.php
            $table->decimal('check_in_latitude', 10, 8)->nullable();
            $table->decimal('check_in_longitude', 11, 8)->nullable();
            $table->decimal('check_out_latitude', 10, 8)->nullable();
            $table->decimal('check_out_longitude', 11, 8)->nullable();
            $table->decimal('check_in_accuracy', 8, 2)->nullable();
            $table->decimal('check_out_accuracy', 8, 2)->nullable();
            $table->boolean('is_within_geofence')->default(false);
            $table->decimal('distance_from_location', 10, 2)->nullable();
            $table->string('location_provider')->nullable();
            $table->json('location_metadata')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['user_id', 'created_at']);
            $table->index('status');
            $table->index('work_location_id');
            $table->index('device_id');
            $table->index('is_within_geofence');
            $table->index(['check_in_latitude', 'check_in_longitude']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};