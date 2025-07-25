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
        Schema::create('assignment_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('work_location_id')->constrained()->onDelete('cascade');
            $table->foreignId('previous_work_location_id')->nullable()->constrained('work_locations')->onDelete('set null');
            $table->foreignId('assigned_by')->nullable()->constrained('users')->onDelete('set null');
            $table->string('assignment_method')->default('manual'); // smart_algorithm, manual, bulk, auto
            $table->json('assignment_reasons')->nullable(); // Array of reasons for the assignment
            $table->integer('assignment_score')->nullable(); // Score from 0-100
            $table->json('metadata')->nullable(); // Additional data like confidence level, etc.
            $table->text('notes')->nullable(); // Optional notes
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['user_id', 'created_at']);
            $table->index(['work_location_id', 'created_at']);
            $table->index('assignment_method');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assignment_histories');
    }
};
