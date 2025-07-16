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
        Schema::create('jenis_tindakan', function (Blueprint $table) {
            $table->id();
            $table->string('kode')->unique();
            $table->string('nama');
            $table->text('deskripsi')->nullable();
            $table->decimal('tarif', 15, 2);
            $table->decimal('jasa_dokter', 15, 2)->default(0);
            $table->decimal('jasa_paramedis', 15, 2)->default(0);
            $table->decimal('jasa_non_paramedis', 15, 2)->default(0);
            $table->enum('kategori', ['konsultasi', 'pemeriksaan', 'tindakan', 'obat', 'lainnya']);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index('kode');
            $table->index('nama');
            $table->index('kategori');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jenis_tindakan');
    }
};
