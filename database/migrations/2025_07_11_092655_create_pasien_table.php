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
        Schema::create('pasien', function (Blueprint $table) {
            $table->id();
            $table->string('no_rekam_medis')->unique();
            $table->string('nama');
            $table->date('tanggal_lahir');
            $table->enum('jenis_kelamin', ['L', 'P']);
            $table->text('alamat')->nullable();
            $table->string('no_telepon')->nullable();
            $table->string('email')->nullable();
            $table->string('pekerjaan')->nullable();
            $table->enum('status_pernikahan', ['belum_menikah', 'menikah', 'janda', 'duda'])->nullable();
            $table->string('kontak_darurat_nama')->nullable();
            $table->string('kontak_darurat_telepon')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('no_rekam_medis');
            $table->index('nama');
            $table->index('tanggal_lahir');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pasien');
    }
};
