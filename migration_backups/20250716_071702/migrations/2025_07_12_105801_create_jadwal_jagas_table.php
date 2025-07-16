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
        Schema::create('jadwal_jagas', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal_jaga');
            $table->foreignId('shift_template_id')->constrained()->onDelete('cascade');
            $table->foreignId('pegawai_id')->constrained('users')->onDelete('cascade');
            $table->string('unit_instalasi')->nullable(); // Simplified for now
            $table->enum('peran', ['Paramedis', 'NonParamedis', 'Dokter']);
            $table->enum('status_jaga', ['Aktif', 'Cuti', 'Izin', 'OnCall'])->default('Aktif');
            $table->text('keterangan')->nullable();
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['tanggal_jaga', 'pegawai_id']);
            $table->index(['tanggal_jaga', 'status_jaga']);
            $table->index(['pegawai_id', 'status_jaga']);
            
            // Unique constraint to prevent double booking
            $table->unique(['tanggal_jaga', 'pegawai_id', 'shift_template_id'], 'unique_schedule');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jadwal_jagas');
    }
};
