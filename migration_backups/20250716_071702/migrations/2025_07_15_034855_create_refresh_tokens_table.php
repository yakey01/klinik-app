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
        Schema::create('refresh_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_device_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('token_hash', 255)->unique();
            $table->string('client_type', 50)->default('mobile_app'); // mobile_app, web_app, api_client
            $table->json('scopes')->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('last_used_at')->nullable();
            $table->ipAddress('last_used_ip')->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->boolean('is_revoked')->default(false);
            $table->timestamp('revoked_at')->nullable();
            $table->string('revoked_reason', 255)->nullable();
            $table->json('metadata')->nullable(); // Additional client info
            $table->timestamps();

            // Indexes for performance
            $table->index(['user_id', 'client_type']);
            $table->index(['expires_at']);
            $table->index(['is_revoked']);
            $table->index(['token_hash']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('refresh_tokens');
    }
};
