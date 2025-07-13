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
            $table->string('nik')->unique()->nullable(); // NIK dokter
            $table->string('nama_lengkap');
            $table->date('tanggal_lahir')->nullable();
            $table->enum('jenis_kelamin', ['Laki-laki', 'Perempuan'])->nullable();
            $table->string('jabatan');
            $table->string('nomor_sip')->nullable();
            $table->string('email')->nullable();
            $table->boolean('aktif')->default(true);
            $table->string('spesialisasi')->nullable();
            $table->text('alamat')->nullable();
            $table->string('no_telepon')->nullable();
            $table->date('tanggal_bergabung')->nullable();
            $table->string('foto')->nullable();
            $table->text('keterangan')->nullable();
            $table->unsignedBigInteger('input_by')->nullable();
            
            // Auth management fields  
            $table->string('username')->unique()->nullable();
            $table->string('password')->nullable();
            $table->enum('status_akun', ['aktif', 'nonaktif', 'suspend'])->default('aktif');
            $table->timestamp('password_changed_at')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->unsignedBigInteger('password_reset_by')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['aktif']);
            $table->index(['nama_lengkap']);
            $table->index(['nik']);
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