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
        Schema::table('user_devices', function (Blueprint $table) {
            // Enhanced security and biometric support
            $table->json('biometric_capabilities')->nullable()->after('device_specs'); // Device biometric capabilities
            $table->boolean('biometric_enabled')->default(false)->after('biometric_capabilities');
            $table->json('biometric_types')->nullable()->after('biometric_enabled'); // Enabled biometric types
            $table->timestamp('biometric_enrolled_at')->nullable()->after('biometric_types');
            $table->integer('biometric_verification_count')->default(0)->after('biometric_enrolled_at');
            $table->timestamp('last_biometric_verification_at')->nullable()->after('biometric_verification_count');
            
            // Enhanced session management
            $table->string('refresh_token_hash', 255)->nullable()->after('last_biometric_verification_at');
            $table->timestamp('refresh_token_expires_at')->nullable()->after('refresh_token_hash');
            $table->json('session_metadata')->nullable()->after('refresh_token_expires_at');
            
            // Security enhancements
            $table->integer('security_score')->default(100)->after('session_metadata'); // 0-100 security score
            $table->json('security_violations')->nullable()->after('security_score'); // Track violations
            $table->boolean('requires_admin_approval')->default(false)->after('security_violations');
            $table->timestamp('admin_approved_at')->nullable()->after('requires_admin_approval');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null')->after('admin_approved_at');
            
            // Add indexes
            $table->index(['biometric_enabled']);
            $table->index(['security_score']);
            $table->index(['requires_admin_approval']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_devices', function (Blueprint $table) {
            $table->dropIndex(['biometric_enabled']);
            $table->dropIndex(['security_score']);
            $table->dropIndex(['requires_admin_approval']);
            
            $table->dropForeign(['approved_by']);
            $table->dropColumn([
                'biometric_capabilities',
                'biometric_enabled',
                'biometric_types',
                'biometric_enrolled_at',
                'biometric_verification_count',
                'last_biometric_verification_at',
                'refresh_token_hash',
                'refresh_token_expires_at',
                'session_metadata',
                'security_score',
                'security_violations',
                'requires_admin_approval',
                'admin_approved_at',
                'approved_by'
            ]);
        });
    }
};
