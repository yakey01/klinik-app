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
        Schema::table('pasien', function (Blueprint $table) {
            $table->enum('status', ['pending', 'verified', 'rejected'])->default('pending')->after('input_by');
            $table->timestamp('verified_at')->nullable()->after('status');
            $table->unsignedBigInteger('verified_by')->nullable()->after('verified_at');
            $table->text('verification_notes')->nullable()->after('verified_by');
            
            $table->foreign('verified_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pasien', function (Blueprint $table) {
            $table->dropForeign(['verified_by']);
            $table->dropColumn(['status', 'verified_at', 'verified_by', 'verification_notes']);
        });
    }
};