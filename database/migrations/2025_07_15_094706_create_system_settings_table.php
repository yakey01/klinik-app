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
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('string'); // string, boolean, integer, float, array, object
            $table->string('group')->default('general'); // general, security, notification, system, etc.
            $table->string('label');
            $table->text('description')->nullable();
            $table->boolean('is_public')->default(false); // can be accessed without authentication
            $table->boolean('is_readonly')->default(false); // cannot be modified via UI
            $table->json('validation_rules')->nullable(); // JSON of validation rules
            $table->json('meta')->nullable(); // Additional metadata (options, min, max, etc.)
            $table->timestamps();
            
            $table->index(['group', 'key']);
            $table->index('is_public');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};
