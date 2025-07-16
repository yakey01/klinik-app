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
        Schema::table('pengeluaran', function (Blueprint $table) {
            $table->string('kode_pengeluaran', 20)->nullable()->after('id');
            $table->string('nama_pengeluaran', 100)->nullable()->after('kode_pengeluaran');
            
            // Make non-essential fields nullable
            $table->text('keterangan')->nullable()->change();
            $table->decimal('nominal', 15, 2)->nullable()->change();
            $table->string('kategori')->nullable()->change();
            $table->string('bukti_transaksi')->nullable()->change();
            $table->unsignedBigInteger('validasi_by')->nullable()->change();
            $table->datetime('validasi_at')->nullable()->change();
            $table->text('catatan_validasi')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pengeluaran', function (Blueprint $table) {
            $table->dropColumn(['kode_pengeluaran', 'nama_pengeluaran']);
        });
    }
};
