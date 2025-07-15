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
        Schema::create('data_transformations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('transformation_type'); // cleanup, validation, enrichment, aggregation
            $table->string('source_model'); // Model class name
            $table->string('target_model')->nullable(); // If different from source
            $table->json('transformation_rules'); // Rules and operations
            $table->json('field_mappings')->nullable(); // Field transformations
            $table->json('validation_rules')->nullable(); // Data validation
            $table->json('cleanup_rules')->nullable(); // Data cleanup operations
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'cancelled'])->default('pending');
            $table->integer('total_records')->nullable();
            $table->integer('processed_records')->default(0);
            $table->integer('transformed_records')->default(0);
            $table->integer('failed_records')->default(0);
            $table->integer('skipped_records')->default(0);
            $table->json('transformation_stats')->nullable(); // Detailed statistics
            $table->longText('error_log')->nullable();
            $table->integer('progress_percentage')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('execution_time')->nullable(); // milliseconds
            $table->integer('memory_usage')->nullable(); // bytes
            $table->boolean('is_scheduled')->default(false);
            $table->string('schedule_frequency')->nullable();
            $table->timestamp('next_run_at')->nullable();
            $table->json('notification_settings')->nullable();
            $table->boolean('backup_before_transform')->default(true);
            $table->string('backup_file_path')->nullable();
            $table->boolean('dry_run')->default(false); // Test mode
            $table->json('dry_run_results')->nullable();
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['user_id', 'status']);
            $table->index(['status', 'created_at']);
            $table->index(['source_model', 'status']);
            $table->index(['transformation_type', 'status']);
            $table->index(['is_scheduled', 'next_run_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('data_transformations');
    }
};