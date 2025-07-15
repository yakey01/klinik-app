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
        Schema::create('non_paramedis_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('work_location_id')->nullable()->constrained()->nullOnDelete();
            
            // Check-in data
            $table->timestamp('check_in_time')->nullable();
            $table->decimal('check_in_latitude', 10, 8)->nullable();
            $table->decimal('check_in_longitude', 11, 8)->nullable();
            $table->decimal('check_in_accuracy', 8, 2)->nullable();
            $table->string('check_in_address')->nullable();
            $table->decimal('check_in_distance', 8, 2)->nullable();
            $table->boolean('check_in_valid_location')->default(false);
            
            // Check-out data
            $table->timestamp('check_out_time')->nullable();
            $table->decimal('check_out_latitude', 10, 8)->nullable();
            $table->decimal('check_out_longitude', 11, 8)->nullable();
            $table->decimal('check_out_accuracy', 8, 2)->nullable();
            $table->string('check_out_address')->nullable();
            $table->decimal('check_out_distance', 8, 2)->nullable();
            $table->boolean('check_out_valid_location')->default(false);
            
            // Work duration
            $table->integer('total_work_minutes')->nullable();
            $table->date('attendance_date');
            
            // Status and validation
            $table->enum('status', ['checked_in', 'checked_out', 'incomplete'])->default('incomplete');
            $table->text('notes')->nullable();
            
            // Device information
            $table->string('device_info')->nullable();
            $table->string('browser_info')->nullable();
            $table->string('ip_address')->nullable();
            
            // GPS spoofing detection
            $table->json('gps_metadata')->nullable();
            $table->boolean('suspected_spoofing')->default(false);
            
            // Approval workflow
            $table->enum('approval_status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_notes')->nullable();
            
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['user_id', 'attendance_date']);
            $table->index(['work_location_id', 'attendance_date']);
            $table->index(['status', 'attendance_date']);
            $table->index('approval_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('non_paramedis_attendances');
    }
};
