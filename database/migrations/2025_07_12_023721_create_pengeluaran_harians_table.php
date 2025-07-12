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
        Schema::create('pengeluaran_harians', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal_input');
            $table->enum('shift', ['Pagi', 'Sore']);
            $table->unsignedBigInteger('pengeluaran_id');
            $table->decimal('nominal', 15, 2);
            $table->text('deskripsi')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->string('status_validasi')->default('pending');
            $table->unsignedBigInteger('validasi_by')->nullable();
            $table->timestamp('validasi_at')->nullable();
            $table->text('catatan_validasi')->nullable();
            $table->timestamps();

            $table->foreign('pengeluaran_id')->references('id')->on('pengeluaran')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('validasi_by')->references('id')->on('users')->onDelete('set null');
            
            $table->index(['status_validasi', 'tanggal_input']);
            $table->index(['user_id', 'tanggal_input']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pengeluaran_harians');
    }
};
