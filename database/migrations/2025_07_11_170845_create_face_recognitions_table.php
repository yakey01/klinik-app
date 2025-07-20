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
        Schema::create('face_recognitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('face_encoding')->nullable(); // Base64 encoded face features
            $table->json('face_landmarks')->nullable(); // Face landmark coordinates
            $table->string('face_image_path')->nullable(); // Reference photo path
            $table->decimal('confidence_score', 5, 4)->default(0.0000); // Recognition confidence
            $table->string('encoding_algorithm')->default('dlib'); // Algorithm used
            $table->boolean('is_active')->default(true);
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users');
            $table->json('metadata')->nullable(); // Additional face data
            $table->softDeletes();
            $table->timestamps();

            // Indexes for performance
            $table->index('user_id');
            $table->index(['user_id', 'is_active']);
            $table->index('confidence_score');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('face_recognitions');
    }
};
