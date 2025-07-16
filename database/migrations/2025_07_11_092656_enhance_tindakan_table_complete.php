<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MERGED MIGRATION: Tindakan Table Enhancements
 * 
 * This migration consolidates the following original migrations:
 * - 2025_07_11_092656_create_tindakan_table.php
 * - 2025_07_11_123000_add_input_by_to_tindakan_table.php
 * - 2025_07_13_100339_add_validation_fields_to_tindakan_table.php
 * - 2025_07_13_100412_fix_foreign_keys_in_tindakan_table.php
 * - 2025_07_13_100434_make_dokter_id_nullable_in_tindakan_table.php
 * 
 * Risk Level: MEDIUM (due to FK changes)
 * Dependencies: jenis_tindakan, pasien, dokters, pegawais tables must exist
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tindakan', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('jenis_tindakan_id');
            $table->unsignedBigInteger('pasien_id');
            $table->unsignedBigInteger('dokter_id')->nullable(); // Made nullable
            $table->text('keterangan')->nullable();
            $table->decimal('harga', 10, 2);
            
            // From: add_input_by_to_tindakan_table.php
            $table->unsignedBigInteger('input_by')->nullable();
            
            // From: add_validation_fields_to_tindakan_table.php
            $table->unsignedBigInteger('validated_by')->nullable();
            $table->timestamp('validated_at')->nullable();
            $table->enum('validation_status', ['pending', 'validated', 'rejected'])->default('pending');
            $table->text('validation_notes')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index('jenis_tindakan_id');
            $table->index('pasien_id');
            $table->index('dokter_id');
            $table->index('input_by');
            $table->index('validated_by');
            $table->index('validation_status');
            $table->index('created_at');
            
            // Foreign keys (updated to reference correct tables)
            $table->foreign('jenis_tindakan_id')->references('id')->on('jenis_tindakan');
            $table->foreign('pasien_id')->references('id')->on('pasien');
            $table->foreign('dokter_id')->references('id')->on('dokters')->onDelete('set null');
            $table->foreign('input_by')->references('id')->on('pegawais')->onDelete('set null');
            $table->foreign('validated_by')->references('id')->on('pegawais')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tindakan');
    }
};