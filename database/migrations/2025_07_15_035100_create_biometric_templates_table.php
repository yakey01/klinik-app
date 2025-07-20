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
        Schema::create('biometric_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_device_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('template_id', 255)->unique();
            $table->string('biometric_type', 50); // fingerprint, face, voice, iris
            $table->text('template_data'); // Encrypted biometric template
            $table->string('template_hash', 255); // Hash for quick lookup
            $table->json('template_metadata')->nullable(); // Quality scores, enrollment info
            $table->string('algorithm_version', 50)->nullable(); // For template versioning
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamp('enrolled_at');
            $table->timestamp('last_verified_at')->nullable();
            $table->integer('verification_count')->default(0);
            $table->integer('failed_attempts')->default(0);
            $table->timestamp('last_failed_at')->nullable();
            $table->boolean('is_compromised')->default(false);
            $table->timestamp('compromised_at')->nullable();
            $table->string('compromised_reason', 255)->nullable();
            $table->json('security_metadata')->nullable(); // Additional security info
            $table->timestamps();

            // Indexes for performance
            $table->index(['user_id', 'biometric_type']);
            $table->index(['template_hash']);
            $table->index(['is_active']);
            $table->index(['is_compromised']);
            $table->unique(['user_id', 'biometric_type', 'template_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('biometric_templates');
    }
};
