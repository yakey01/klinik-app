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
        Schema::create('attendance_recaps', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->integer('year');
            $table->integer('month');
            $table->integer('total_working_days');
            $table->integer('present_days');
            $table->integer('late_days');
            $table->integer('absent_days');
            $table->decimal('attendance_percentage', 5, 2);
            $table->integer('total_work_minutes');
            $table->integer('ranking')->nullable();
            $table->timestamps();
            
            // Add indexes for better performance
            $table->index(['user_id', 'year', 'month']);
            $table->index(['year', 'month']);
            $table->index('ranking');
            
            // Add foreign key constraint
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            
            // Ensure unique combination of user, year, month
            $table->unique(['user_id', 'year', 'month']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_recaps');
    }
};
