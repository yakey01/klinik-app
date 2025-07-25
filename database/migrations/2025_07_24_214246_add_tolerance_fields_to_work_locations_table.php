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
        Schema::table('work_locations', function (Blueprint $table) {
            // Individual tolerance fields for better performance and clearer configuration
            $table->integer('late_tolerance_minutes')->default(15)->comment('Toleransi keterlambatan check-in (menit)');
            $table->integer('early_departure_tolerance_minutes')->default(15)->comment('Toleransi check-out lebih awal (menit)');
            $table->integer('break_time_minutes')->default(60)->comment('Durasi istirahat standar (menit)');
            $table->integer('overtime_threshold_minutes')->default(480)->comment('Batas jam kerja untuk overtime (menit)');
            
            // Additional check-in/out tolerance fields
            $table->integer('checkin_before_shift_minutes')->default(30)->comment('Berapa menit sebelum shift bisa check-in');
            $table->integer('checkout_after_shift_minutes')->default(60)->comment('Berapa menit setelah shift masih bisa check-out');
            
            // Indexes for quick lookups
            $table->index(['late_tolerance_minutes']);
            $table->index(['early_departure_tolerance_minutes']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('work_locations', function (Blueprint $table) {
            $table->dropIndex(['late_tolerance_minutes']);
            $table->dropIndex(['early_departure_tolerance_minutes']);
            $table->dropColumn([
                'late_tolerance_minutes',
                'early_departure_tolerance_minutes', 
                'break_time_minutes',
                'overtime_threshold_minutes',
                'checkin_before_shift_minutes',
                'checkout_after_shift_minutes'
            ]);
        });
    }
};