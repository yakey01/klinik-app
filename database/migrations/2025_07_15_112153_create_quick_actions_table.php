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
        Schema::create('quick_actions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('action_type'); // workflow, command, url, function
            $table->json('action_config'); // Configuration for the action
            $table->string('icon')->nullable();
            $table->string('color')->default('primary');
            $table->string('keyboard_shortcut')->nullable();
            $table->string('category')->default('general');
            $table->json('permissions')->nullable(); // Role/permission requirements
            $table->json('context_filters')->nullable(); // When to show this action
            $table->integer('sort_order')->default(0);
            $table->boolean('is_enabled')->default(true);
            $table->boolean('is_public')->default(false);
            $table->boolean('show_in_toolbar')->default(true);
            $table->boolean('show_in_menu')->default(true);
            $table->boolean('confirm_before_execute')->default(false);
            $table->string('confirmation_message')->nullable();
            $table->integer('usage_count')->default(0);
            $table->timestamp('last_used_at')->nullable();
            $table->json('usage_stats')->nullable();
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['user_id', 'is_enabled']);
            $table->index(['action_type', 'is_enabled']);
            $table->index(['category', 'is_enabled']);
            $table->index(['is_public', 'is_enabled']);
            $table->index(['show_in_toolbar', 'is_enabled']);
            $table->index(['keyboard_shortcut']);
            $table->index(['sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quick_actions');
    }
};