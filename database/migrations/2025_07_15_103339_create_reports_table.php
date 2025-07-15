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
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('report_type', ['table', 'chart', 'dashboard', 'export', 'kpi'])->default('table');
            $table->enum('category', ['financial', 'operational', 'medical', 'administrative', 'security', 'performance', 'custom'])->default('custom');
            $table->json('query_config');
            $table->json('chart_config')->nullable();
            $table->json('filters')->nullable();
            $table->json('columns')->nullable();
            $table->boolean('is_public')->default(false);
            $table->boolean('is_scheduled')->default(false);
            $table->json('schedule_config')->nullable();
            $table->timestamp('last_generated_at')->nullable();
            $table->integer('cache_duration')->default(300);
            $table->enum('status', ['active', 'inactive', 'draft', 'archived'])->default('active');
            $table->json('tags')->nullable();
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['user_id', 'status']);
            $table->index(['report_type', 'category']);
            $table->index(['is_public', 'status']);
            $table->index(['is_scheduled', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
