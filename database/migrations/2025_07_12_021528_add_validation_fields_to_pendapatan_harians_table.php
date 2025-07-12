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
        Schema::table('pendapatan_harians', function (Blueprint $table) {
            $table->string('status_validasi')->default('pending')->after('deskripsi');
            $table->unsignedBigInteger('validasi_by')->nullable()->after('status_validasi');
            $table->timestamp('validasi_at')->nullable()->after('validasi_by');
            $table->text('catatan_validasi')->nullable()->after('validasi_at');
            
            $table->foreign('validasi_by')->references('id')->on('users')->onDelete('set null');
            $table->index(['status_validasi', 'tanggal_input']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pendapatan_harians', function (Blueprint $table) {
            $table->dropForeign(['validasi_by']);
            $table->dropIndex(['status_validasi', 'tanggal_input']);
            $table->dropColumn(['status_validasi', 'validasi_by', 'validasi_at', 'catatan_validasi']);
        });
    }
};
