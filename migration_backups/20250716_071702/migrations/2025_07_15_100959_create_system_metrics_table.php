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
        Schema::create('system_metrics', function (Blueprint $table) {
            $table->id();
            $table->string('metric_type', 50)->index();
            $table->string('metric_name', 100)->index();
            $table->decimal('metric_value', 10, 2);
            $table->json('metric_data')->nullable();
            $table->decimal('alert_threshold', 10, 2)->nullable();
            $table->enum('status', ['healthy', 'warning', 'critical', 'unknown'])->default('healthy');
            $table->timestamp('recorded_at')->index();
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['metric_type', 'metric_name']);
            $table->index(['status', 'recorded_at']);
            $table->index(['recorded_at', 'metric_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_metrics');
    }
};
