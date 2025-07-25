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
        Schema::table('location_validations', function (Blueprint $table) {
            // Security analysis fields
            $table->enum('risk_level', ['low', 'medium', 'high', 'critical'])->default('low')->after('notes');
            $table->integer('risk_score')->default(0)->after('risk_level');
            $table->boolean('is_spoofed')->default(false)->after('risk_score');
            $table->boolean('is_blocked')->default(false)->after('is_spoofed');
            
            // Action and detection results
            $table->enum('action_taken', ['none', 'warning', 'flagged', 'blocked'])->default('none')->after('is_blocked');
            $table->json('detection_results')->nullable()->after('action_taken');
            $table->json('spoofing_indicators')->nullable()->after('detection_results');
            $table->json('device_fingerprint')->nullable()->after('spoofing_indicators');
            
            // Admin review fields
            $table->timestamp('reviewed_at')->nullable()->after('device_fingerprint');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->after('reviewed_at');
            
            // Indexes for performance
            $table->index(['risk_level', 'is_spoofed']);
            $table->index(['action_taken', 'reviewed_at']);
            $table->index(['user_id', 'risk_level']);
            $table->index('is_blocked');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('location_validations', function (Blueprint $table) {
            $table->dropForeign(['reviewed_by']);
            $table->dropIndex(['risk_level', 'is_spoofed']);
            $table->dropIndex(['action_taken', 'reviewed_at']);
            $table->dropIndex(['user_id', 'risk_level']);
            $table->dropIndex(['is_blocked']);
            
            $table->dropColumn([
                'risk_level',
                'risk_score', 
                'is_spoofed',
                'is_blocked',
                'action_taken',
                'detection_results',
                'spoofing_indicators',
                'device_fingerprint',
                'reviewed_at',
                'reviewed_by'
            ]);
        });
    }
};