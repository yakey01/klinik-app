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
        Schema::create('location_validations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Location Data
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->decimal('accuracy', 8, 2)->nullable();
            
            // Geofencing Validation
            $table->integer('work_zone_radius'); // in meters
            $table->boolean('is_within_zone')->default(false);
            $table->decimal('distance_from_zone', 10, 2)->nullable(); // in meters
            
            // Validation Context
            $table->timestamp('validation_time');
            $table->enum('attendance_type', ['check_in', 'check_out']);
            $table->text('notes')->nullable();
            
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['user_id', 'validation_time']);
            $table->index(['attendance_type', 'is_within_zone']);
            $table->index('validation_time');
            $table->index('is_within_zone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('location_validations');
    }
};
