<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MERGED MIGRATION: Pegawais Table Enhancements
 * 
 * This migration consolidates the following original migrations:
 * - 2025_07_11_230305_create_pegawais_table.php
 * - 2025_07_11_233203_update_pegawais_table_make_nik_required.php
 * - 2025_07_13_000205_add_user_id_to_pegawais_table.php
 * - 2025_07_13_075245_add_login_fields_to_pegawais_table.php
 * 
 * Risk Level: LOW
 * Dependencies: users table must exist
 */
return new class extends Migration
{
    public function up(): void
    {
        // Create pegawais table with all fields
        Schema::create('pegawais', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('nik')->unique(); // Made required from update migration
            $table->enum('jenis_kelamin', ['laki-laki', 'perempuan']);
            $table->date('tanggal_lahir');
            $table->text('alamat');
            $table->string('no_telepon', 20)->nullable();
            $table->string('jabatan');
            $table->date('tanggal_mulai_kerja');
            $table->boolean('status_aktif')->default(true);
            $table->unsignedBigInteger('input_by');
            
            // From: add_user_id_to_pegawais_table.php
            $table->unsignedBigInteger('user_id')->nullable()->unique();
            
            // From: add_login_fields_to_pegawais_table.php
            $table->string('username')->nullable()->unique();
            $table->string('password')->nullable();
            $table->rememberToken();
            $table->timestamp('last_login_at')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index('nama');
            $table->index('nik');
            $table->index('status_aktif');
            $table->index('user_id');
            $table->index('username');
            
            // Foreign keys
            $table->foreign('input_by')->references('id')->on('users');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pegawais');
    }
};