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
            $table->unsignedBigInteger('user_id')->nullable()->after('role');
            $table->string('user_name')->nullable()->after('user_id');
            $table->string('role_type')->nullable()->after('user_name')->comment('General role type: general, specific_user');
            
            // Add foreign key constraint
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            
            // Add index for performance
            $table->index(['role', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('telegram_settings', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropIndex(['role', 'user_id']);
            $table->dropColumn(['user_id', 'user_name', 'role_type']);
        });
    }
};
