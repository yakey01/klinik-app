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
        Schema::create('pendapatan', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal');
            $table->string('keterangan');
            $table->decimal('nominal', 15, 2);
            $table->enum('kategori', ['tindakan', 'konsultasi', 'obat', 'administrasi', 'lainnya']);
            $table->foreignId('tindakan_id')->nullable()->constrained('tindakan')->onDelete('set null');
            $table->foreignId('input_by')->constrained('users')->onDelete('cascade');
            $table->enum('status_validasi', ['pending', 'disetujui', 'ditolak'])->default('pending');
            $table->foreignId('validasi_by')->nullable()->constrained('users')->onDelete('set null');
            $table->dateTime('validasi_at')->nullable();
            $table->text('catatan_validasi')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('tanggal');
            $table->index('kategori');
            $table->index('status_validasi');
            $table->index(['tanggal', 'kategori']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pendapatan');
    }
};
