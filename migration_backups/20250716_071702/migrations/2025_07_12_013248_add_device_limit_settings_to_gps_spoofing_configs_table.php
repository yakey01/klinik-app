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
        Schema::table('gps_spoofing_configs', function (Blueprint $table) {
            // Device Management Settings
            $table->boolean('auto_register_devices')->default(true)->after('require_admin_review_for_unblock');
            $table->integer('max_devices_per_user')->default(1)->after('auto_register_devices');
            $table->boolean('require_admin_approval_for_new_devices')->default(false)->after('max_devices_per_user');
            $table->enum('device_limit_policy', ['strict', 'warn', 'flexible'])->default('strict')->after('require_admin_approval_for_new_devices');
            $table->integer('device_auto_cleanup_days')->default(30)->after('device_limit_policy');
            $table->boolean('auto_revoke_excess_devices')->default(true)->after('device_auto_cleanup_days');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gps_spoofing_configs', function (Blueprint $table) {
            $table->dropColumn([
                'auto_register_devices',
                'max_devices_per_user', 
                'require_admin_approval_for_new_devices',
                'device_limit_policy',
                'device_auto_cleanup_days',
                'auto_revoke_excess_devices'
            ]);
        });
    }
};
