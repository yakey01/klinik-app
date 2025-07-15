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
        Schema::create('workflows', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('trigger_type'); // manual, scheduled, event, webhook
            $table->json('trigger_config')->nullable(); // Trigger configuration
            $table->json('steps'); // Workflow steps configuration
            $table->json('conditions')->nullable(); // Conditional logic
            $table->enum('status', ['draft', 'active', 'paused', 'archived'])->default('draft');
            $table->string('category')->default('general'); // general, data, notifications, reports
            $table->json('tags')->nullable();
            $table->integer('priority')->default(1); // 1-5 priority levels
            $table->integer('timeout')->default(300); // Seconds
            $table->boolean('is_public')->default(false);
            $table->boolean('is_template')->default(false);
            $table->integer('max_retries')->default(3);
            $table->json('retry_config')->nullable();
            $table->json('notification_config')->nullable();
            $table->json('error_handling')->nullable();
            $table->timestamp('last_executed_at')->nullable();
            $table->integer('execution_count')->default(0);
            $table->integer('success_count')->default(0);
            $table->integer('failure_count')->default(0);
            $table->decimal('avg_execution_time', 8, 2)->nullable(); // milliseconds
            $table->json('performance_stats')->nullable();
            $table->timestamp('next_run_at')->nullable();
            $table->string('schedule_frequency')->nullable(); // cron expression
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['user_id', 'status']);
            $table->index(['trigger_type', 'status']);
            $table->index(['category', 'status']);
            $table->index(['is_enabled', 'status']);
            $table->index(['next_run_at', 'status']);
            $table->index(['is_public', 'is_template']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workflows');
    }
};