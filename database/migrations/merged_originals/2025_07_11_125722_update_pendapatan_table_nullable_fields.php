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
        Schema::table('pendapatan', function (Blueprint $table) {
            // Make non-essential fields nullable
            $table->text('keterangan')->nullable()->change();
            $table->decimal('nominal', 15, 2)->nullable()->change();
            $table->string('kategori')->nullable()->change();
            $table->unsignedBigInteger('tindakan_id')->nullable()->change();
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
        Schema::table('pendapatan', function (Blueprint $table) {
            // Revert changes (be careful with this in production)
            $table->text('keterangan')->nullable(false)->change();
            $table->decimal('nominal', 15, 2)->nullable(false)->change();
            $table->string('kategori')->nullable(false)->change();
        });
    }
};
