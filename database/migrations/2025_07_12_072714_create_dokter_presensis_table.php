<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dokter_presensis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dokter_id')->constrained('dokters')->onDelete('cascade');
            $table->date('tanggal');
            $table->time('jam_masuk')->nullable();
            $table->time('jam_pulang')->nullable();
            $table->timestamps();
            
            // Ensure one record per dokter per day
            $table->unique(['dokter_id', 'tanggal']);
            
            // Index for performance
            $table->index(['dokter_id', 'tanggal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dokter_presensis');
    }
};