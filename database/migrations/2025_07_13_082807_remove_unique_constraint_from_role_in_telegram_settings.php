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
        Schema::table('telegram_settings', function (Blueprint $table) {
            // Drop the existing unique constraint on role
            $table->dropUnique(['role']);
            
            // Add composite unique constraint to prevent duplicate combinations
            $table->unique(['role', 'user_id', 'role_type'], 'telegram_settings_role_user_type_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('telegram_settings', function (Blueprint $table) {
            // Drop the composite unique constraint
            $table->dropUnique('telegram_settings_role_user_type_unique');
            
            // Restore the original unique constraint on role
            $table->unique('role');
        });
    }
};
