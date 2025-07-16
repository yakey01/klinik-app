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
            $table->string('jenis_shift')->index();
            $table->integer('ambang_pasien')->default(0);
            $table->decimal('fee_pasien_umum', 15, 2)->default(0);
            $table->decimal('fee_pasien_bpjs', 15, 2)->default(0);
            $table->boolean('status_aktif')->default(true);
            $table->text('keterangan')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Foreign keys
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            
            // Indexes
            $table->index(['jenis_shift', 'status_aktif']);
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
