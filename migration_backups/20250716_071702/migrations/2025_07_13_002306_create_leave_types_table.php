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
        Schema::create('leave_types', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->integer('alokasi_hari')->nullable()->comment('Jumlah hari yang dialokasikan per tahun, null = tidak terbatas');
            $table->boolean('active')->default(true);
            $table->string('description')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index(['active', 'nama']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_types');
    }
};