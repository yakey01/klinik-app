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
        Schema::create('feature_flags', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_enabled')->default(false);
            $table->json('conditions')->nullable(); // JSON conditions for enabling (roles, users, etc.)
            $table->string('environment')->nullable(); // production, staging, development, etc.
            $table->timestamp('starts_at')->nullable(); // When the feature should start
            $table->timestamp('ends_at')->nullable(); // When the feature should end
            $table->json('meta')->nullable(); // Additional metadata
            $table->boolean('is_permanent')->default(false); // Cannot be disabled via UI
            $table->timestamps();
            
            $table->index(['key', 'is_enabled']);
            $table->index('environment');
            $table->index('is_permanent');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feature_flags');
    }
};
