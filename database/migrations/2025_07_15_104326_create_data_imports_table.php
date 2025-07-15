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
        Schema::create('data_imports', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('source_type'); // file, api, database, csv, excel, json, xml
            $table->string('target_model'); // Model class name
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->string('file_size')->nullable();
            $table->string('mime_type')->nullable();
            $table->json('source_config')->nullable(); // API endpoints, database connections, etc.
            $table->json('mapping_config'); // Field mappings and transformations
            $table->json('validation_rules')->nullable(); // Custom validation rules
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'cancelled'])->default('pending');
            $table->integer('total_rows')->nullable();
            $table->integer('processed_rows')->default(0);
            $table->integer('successful_rows')->default(0);
            $table->integer('failed_rows')->default(0);
            $table->integer('skipped_rows')->default(0);
            $table->json('error_details')->nullable(); // Detailed error information
            $table->longText('validation_errors')->nullable(); // Validation failures
            $table->integer('progress_percentage')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('execution_time')->nullable(); // milliseconds
            $table->integer('memory_usage')->nullable(); // bytes
            $table->boolean('is_scheduled')->default(false);
            $table->string('schedule_frequency')->nullable(); // daily, weekly, monthly
            $table->timestamp('next_run_at')->nullable();
            $table->json('notification_settings')->nullable();
            $table->boolean('backup_before_import')->default(true);
            $table->string('backup_file_path')->nullable();
            $table->json('preview_data')->nullable(); // Sample data for preview
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['user_id', 'status']);
            $table->index(['status', 'created_at']);
            $table->index(['target_model', 'status']);
            $table->index(['is_scheduled', 'next_run_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('data_imports');
    }
};