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
        Schema::create('user_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_device_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('session_id', 255)->unique();
            $table->string('access_token_id', 255)->nullable(); // Sanctum token ID
            $table->string('client_type', 50)->default('mobile_app'); // mobile_app, web_app, api_client
            $table->json('session_data')->nullable(); // Custom session data
            $table->timestamp('started_at');
            $table->timestamp('last_activity_at');
            $table->timestamp('expires_at')->nullable();
            $table->ipAddress('ip_address');
            $table->string('user_agent', 500)->nullable();
            $table->string('location_country', 100)->nullable();
            $table->string('location_city', 100)->nullable();
            $table->decimal('location_latitude', 10, 8)->nullable();
            $table->decimal('location_longitude', 11, 8)->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('force_logout')->default(false);
            $table->timestamp('ended_at')->nullable();
            $table->string('ended_reason', 100)->nullable(); // logout, timeout, revoked, device_change
            $table->json('security_flags')->nullable(); // Suspicious activity markers
            $table->timestamps();

            // Indexes for performance
            $table->index(['user_id', 'is_active']);
            $table->index(['session_id']);
            $table->index(['last_activity_at']);
            $table->index(['expires_at']);
            $table->index(['force_logout']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_sessions');
    }
};
