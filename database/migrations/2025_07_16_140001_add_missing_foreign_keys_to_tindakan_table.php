<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add missing foreign keys to tindakan table
 * 
 * This migration adds foreign keys that couldn't be added during tindakan table creation
 * because the referenced tables (dokters, pegawais) were created later.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tindakan', function (Blueprint $table) {
            // Add foreign keys for dokters and pegawais tables
            $table->foreign('dokter_id')->references('id')->on('dokters')->onDelete('set null');
            $table->foreign('input_by')->references('id')->on('pegawais')->onDelete('set null');
            $table->foreign('validated_by')->references('id')->on('pegawais')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('tindakan', function (Blueprint $table) {
            $table->dropForeign(['dokter_id']);
            $table->dropForeign(['input_by']);
            $table->dropForeign(['validated_by']);
        });
    }
};