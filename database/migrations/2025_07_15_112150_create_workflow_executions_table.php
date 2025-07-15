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
        Schema::create('workflow_executions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('execution_id')->unique(); // UUID for tracking
            $table->string('trigger_source')->nullable(); // manual, scheduled, event, webhook
            $table->json('trigger_data')->nullable(); // Data that triggered the workflow
            $table->enum('status', ['pending', 'running', 'completed', 'failed', 'cancelled', 'timeout'])->default('pending');
            $table->json('current_step')->nullable(); // Current step being executed
            $table->json('step_results')->nullable(); // Results from each step
            $table->json('context_data')->nullable(); // Shared data between steps
            $table->longText('execution_log')->nullable(); // Detailed execution log
            $table->text('error_message')->nullable();
            $table->json('error_details')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('execution_time')->nullable(); // milliseconds
            $table->integer('memory_usage')->nullable(); // bytes
            $table->integer('steps_completed')->default(0);
            $table->integer('total_steps')->default(0);
            $table->integer('retry_count')->default(0);
            $table->integer('warnings_count')->default(0);
            $table->json('performance_metrics')->nullable();
            $table->json('output_data')->nullable(); // Final output
            $table->string('priority', 10)->default('normal'); // low, normal, high, urgent
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['workflow_id', 'status']);
            $table->index(['user_id', 'status']);
            $table->index(['status', 'created_at']);
            $table->index(['trigger_source', 'status']);
            $table->index(['execution_id']);
            $table->index(['started_at', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workflow_executions');
    }
};