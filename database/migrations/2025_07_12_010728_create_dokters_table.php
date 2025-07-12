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
        Schema::create('dokters', function (Blueprint $table) {
            $table->id();
            $table->string('nama_lengkap');
            $table->string('nik')->unique()->nullable(); // Optional UUID
            $table->date('tanggal_lahir')->nullable();
            $table->enum('jenis_kelamin', ['Laki-laki', 'Perempuan'])->nullable();
            $table->enum('jabatan', ['dokter_umum', 'dokter_gigi', 'dokter_spesialis'])->default('dokter_umum');
            $table->string('nomor_sip')->unique(); // Wajib untuk praktik
            $table->string('email')->unique()->nullable();
            $table->boolean('aktif')->default(true);
            $table->string('foto')->nullable(); // Path foto
            $table->text('keterangan')->nullable(); // Notes tambahan
            $table->foreignId('input_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for performance
            $table->index(['aktif', 'jabatan']);
            $table->index('nama_lengkap');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dokters');
    }
};
