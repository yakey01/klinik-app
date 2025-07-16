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
            $table->string('kode_pendapatan', 20)->nullable()->after('id');
            $table->string('nama_pendapatan', 100)->nullable()->after('kode_pendapatan');
            $table->enum('sumber_pendapatan', ['Umum', 'Gigi'])->nullable()->after('nama_pendapatan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pendapatan', function (Blueprint $table) {
            $table->dropColumn(['kode_pendapatan', 'nama_pendapatan', 'sumber_pendapatan']);
        });
    }
};
