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
        Schema::create('performance_logs', function (Blueprint $table) {
            $table->id();
            $table->string('operation')->index();
            $table->decimal('duration', 10, 3);
            $table->bigInteger('memory_usage');
            $table->bigInteger('memory_peak');
            $table->json('metrics')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->text('url')->nullable();
            $table->string('method')->nullable();
            $table->timestamps();
            
            $table->index(['operation', 'created_at']);
            $table->index(['duration', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('performance_logs');
    }
};