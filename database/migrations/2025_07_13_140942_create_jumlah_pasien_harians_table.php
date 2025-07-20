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
        Schema::create('jumlah_pasien_harians', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal');
            $table->enum('poli', ['umum', 'gigi'])->default('umum');
            $table->integer('jumlah_pasien_umum')->default(0);
            $table->integer('jumlah_pasien_bpjs')->default(0);
            $table->foreignId('dokter_id')->constrained('dokters')->onDelete('cascade');
            $table->foreignId('input_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
            
            // Unique constraint untuk mencegah duplikasi data per tanggal/poli/dokter
            $table->unique(['tanggal', 'poli', 'dokter_id'], 'unique_daily_record');
            
            // Indexes untuk performa query
            $table->index('tanggal');
            $table->index('poli');
            $table->index('dokter_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jumlah_pasien_harians');
    }
};
