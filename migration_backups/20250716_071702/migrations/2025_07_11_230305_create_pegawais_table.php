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
        Schema::create('pegawais', function (Blueprint $table) {
            $table->id();
            $table->string('nik')->nullable()->unique();
            $table->string('nama_lengkap');
            $table->date('tanggal_lahir')->nullable();
            $table->enum('jenis_kelamin', ['Laki-laki', 'Perempuan'])->nullable();
            $table->string('jabatan');
            $table->enum('jenis_pegawai', ['Paramedis', 'Non-Paramedis'])->default('Non-Paramedis');
            $table->boolean('aktif')->default(true);
            $table->string('foto')->nullable();
            $table->unsignedBigInteger('input_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['jenis_pegawai', 'aktif']);
            $table->index('nama_lengkap');
            $table->index('jabatan');

            $table->foreign('input_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pegawais');
    }
};
