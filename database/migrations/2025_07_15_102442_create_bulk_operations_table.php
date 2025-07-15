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
        Schema::create('bulk_operations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('operation_type', 50)->index();
            $table->string('model_type', 255)->index();
            $table->json('operation_data');
            $table->json('filters')->nullable();
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'cancelled', 'paused'])->default('pending');
            $table->integer('total_records')->default(0);
            $table->integer('processed_records')->default(0);
            $table->integer('successful_records')->default(0);
            $table->integer('failed_records')->default(0);
            $table->json('error_details')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('estimated_duration')->nullable();
            $table->decimal('progress_percentage', 5, 2)->default(0);
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['user_id', 'status']);
            $table->index(['operation_type', 'status']);
            $table->index(['created_at', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bulk_operations');
    }
};
