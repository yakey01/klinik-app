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
        Schema::create('dokter_umum_jaspels', function (Blueprint $table) {
            $table->id();
            $table->enum('jenis_shift', ['Pagi', 'Sore', 'Hari Libur Besar']);
            $table->integer('ambang_pasien')->default(0); // Threshold pasien
            $table->decimal('fee_pasien_umum', 15, 2)->default(0); // Fee pasien umum
            $table->decimal('fee_pasien_bpjs', 15, 2)->default(0); // Fee pasien BPJS
            $table->boolean('status_aktif')->default(true);
            $table->text('keterangan')->nullable(); // Catatan tambahan
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for performance
            $table->index(['jenis_shift', 'status_aktif']);
            $table->index('status_aktif');
            $table->index('created_at');
            
            // Unique constraint untuk prevent duplicate shift
            $table->unique('jenis_shift');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dokter_umum_jaspels');
    }
};
