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
        Schema::create('tindakan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pasien_id')->constrained('pasien')->onDelete('cascade');
            $table->foreignId('jenis_tindakan_id')->constrained('jenis_tindakan')->onDelete('cascade');
            $table->foreignId('dokter_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('paramedis_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('non_paramedis_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('shift_id')->constrained('shifts')->onDelete('cascade');
            $table->dateTime('tanggal_tindakan');
            $table->decimal('tarif', 15, 2);
            $table->decimal('jasa_dokter', 15, 2)->default(0);
            $table->decimal('jasa_paramedis', 15, 2)->default(0);
            $table->decimal('jasa_non_paramedis', 15, 2)->default(0);
            $table->text('catatan')->nullable();
            $table->enum('status', ['pending', 'selesai', 'batal'])->default('pending');
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('tanggal_tindakan');
            $table->index('status');
            $table->index(['pasien_id', 'tanggal_tindakan']);
            $table->index(['dokter_id', 'tanggal_tindakan']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tindakan');
    }
};
