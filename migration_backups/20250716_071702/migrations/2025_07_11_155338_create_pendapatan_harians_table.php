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
        Schema::create('pendapatan_harians', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal_input');
            $table->enum('shift', ['Pagi', 'Sore']);
            $table->foreignId('jenis_transaksi_id')->constrained('jenis_transaksis')->onDelete('cascade');
            $table->decimal('nominal', 15, 2);
            $table->text('deskripsi')->nullable();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();
            
            $table->index(['tanggal_input', 'shift']);
            $table->index(['user_id', 'tanggal_input']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pendapatan_harians');
    }
};
