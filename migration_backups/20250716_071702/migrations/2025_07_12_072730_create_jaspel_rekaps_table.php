<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jaspel_rekaps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dokter_id')->constrained('dokters')->onDelete('cascade');
            $table->unsignedSmallInteger('bulan');
            $table->unsignedSmallInteger('tahun');
            $table->decimal('total_umum', 15, 2)->default(0);
            $table->decimal('total_bpjs', 15, 2)->default(0);
            $table->unsignedInteger('total_tindakan')->default(0);
            $table->enum('status_pembayaran', ['pending', 'dibayar', 'ditolak'])->default('pending');
            $table->timestamps();
            
            // Ensure one record per dokter per month
            $table->unique(['dokter_id', 'bulan', 'tahun']);
            
            // Index for performance
            $table->index(['dokter_id', 'tahun', 'bulan']);
            $table->index('status_pembayaran');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jaspel_rekaps');
    }
};