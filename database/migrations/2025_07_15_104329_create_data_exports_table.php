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
        Schema::create('data_exports', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('source_model'); // Model class name
            $table->string('export_format'); // csv, excel, json, xml, pdf
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->string('file_size')->nullable();
            $table->json('query_config')->nullable(); // Filters, joins, etc.
            $table->json('column_config'); // Column selection and formatting
            $table->json('format_config')->nullable(); // Format-specific settings
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'cancelled'])->default('pending');
            $table->integer('total_rows')->nullable();
            $table->integer('exported_rows')->default(0);
            $table->integer('progress_percentage')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('execution_time')->nullable(); // milliseconds
            $table->integer('memory_usage')->nullable(); // bytes
            $table->json('error_details')->nullable();
            $table->boolean('is_scheduled')->default(false);
            $table->string('schedule_frequency')->nullable();
            $table->timestamp('next_run_at')->nullable();
            $table->json('notification_settings')->nullable();
            $table->boolean('compress_output')->default(false);
            $table->string('compression_format')->nullable(); // zip, gzip
            $table->boolean('encrypt_output')->default(false);
            $table->string('encryption_key')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->integer('download_count')->default(0);
            $table->timestamp('last_downloaded_at')->nullable();
            $table->json('access_permissions')->nullable(); // User/role access control
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['user_id', 'status']);
            $table->index(['status', 'created_at']);
            $table->index(['source_model', 'status']);
            $table->index(['is_scheduled', 'next_run_at']);
            $table->index(['expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('data_exports');
    }
};