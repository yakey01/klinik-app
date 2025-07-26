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
        Schema::create('di_paramedis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pegawai_id')->constrained('pegawais')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('jadwal_jaga_id')->nullable()->constrained('jadwal_jagas')->onDelete('set null');
            $table->date('tanggal');
            $table->time('jam_mulai');
            $table->time('jam_selesai')->nullable();
            $table->string('shift')->nullable();
            $table->string('lokasi_tugas');
            
            // Patient care activities
            $table->integer('jumlah_pasien_dilayani')->default(0);
            $table->integer('jumlah_tindakan_medis')->default(0);
            $table->integer('jumlah_observasi_pasien')->default(0);
            
            // Medical procedures
            $table->json('tindakan_medis')->nullable(); // Array of medical procedures performed
            $table->json('obat_diberikan')->nullable(); // Array of medications administered
            $table->json('alat_medis_digunakan')->nullable(); // Array of medical equipment used
            
            // Emergency response
            $table->integer('jumlah_kasus_emergency')->default(0);
            $table->text('catatan_kasus_emergency')->nullable();
            
            // Documentation
            $table->text('laporan_kegiatan')->nullable();
            $table->text('kendala_hambatan')->nullable();
            $table->text('saran_perbaikan')->nullable();
            
            // Status and validation
            $table->enum('status', ['draft', 'submitted', 'approved', 'rejected'])->default('draft');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            
            // Additional fields
            $table->string('signature_path')->nullable(); // Digital signature
            $table->json('attachments')->nullable(); // Supporting documents
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['pegawai_id', 'tanggal']);
            $table->index(['status', 'tanggal']);
            $table->index('jadwal_jaga_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('di_paramedis');
    }
};
