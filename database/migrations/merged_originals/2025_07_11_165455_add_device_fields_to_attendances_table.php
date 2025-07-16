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
        Schema::table('attendances', function (Blueprint $table) {
            // Add device binding fields
            $table->string('device_id')->nullable()->after('device_info'); 
            $table->string('device_fingerprint')->nullable()->after('device_id');
            
            // Add index for device validation
            $table->index(['user_id', 'device_id']);
            $table->index('device_fingerprint');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'device_id']);
            $table->dropIndex(['device_fingerprint']);
            $table->dropColumn(['device_id', 'device_fingerprint']);
        });
    }
};