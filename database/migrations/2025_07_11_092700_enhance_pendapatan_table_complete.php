<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MERGED MIGRATION: Pendapatan Table Enhancements
 * 
 * This migration consolidates the following original migrations:
 * - 2025_07_11_092700_create_pendapatan_table.php
 * - 2025_07_11_125444_add_new_fields_to_pendapatan_table.php
 * - 2025_07_11_125722_update_pendapatan_table_nullable_fields.php
 * - 2025_07_11_160519_add_is_aktif_to_pendapatan_table.php
 * 
 * Risk Level: LOW
 * Dependencies: users table must exist
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pendapatan', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal');
            $table->string('nama');
            $table->enum('kategori', ['pasien', 'rental']);
            $table->decimal('jumlah', 10, 2);
            $table->string('keterangan')->nullable();
            $table->unsignedBigInteger('input_by');
            
            // From: add_new_fields_to_pendapatan_table.php & update_pendapatan_table_nullable_fields.php
            $table->string('jenis')->nullable();
            $table->string('sumber')->nullable();
            $table->enum('status_pembayaran', ['lunas', 'belum_lunas', 'sebagian'])->default('lunas');
            $table->string('referensi')->nullable();
            $table->text('catatan')->nullable();
            
            // From: add_is_aktif_to_pendapatan_table.php
            $table->boolean('is_aktif')->default(true);
            
            $table->timestamps();
            
            // Indexes
            $table->index('tanggal');
            $table->index('kategori');
            $table->index('status_pembayaran');
            $table->index('is_aktif');
            $table->index('input_by');
            
            // Foreign keys
            $table->foreign('input_by')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pendapatan');
    }
};