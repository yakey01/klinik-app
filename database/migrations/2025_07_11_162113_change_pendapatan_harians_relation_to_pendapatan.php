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
            // Drop foreign key constraint first
            $table->dropForeign(['jenis_transaksi_id']);
            
            // Rename column to pendapatan_id
            $table->renameColumn('jenis_transaksi_id', 'pendapatan_id');
        });
        
        // Add new foreign key constraint
        Schema::table('pendapatan_harians', function (Blueprint $table) {
            $table->foreign('pendapatan_id')->references('id')->on('pendapatan')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pendapatan_harians', function (Blueprint $table) {
            // Drop foreign key constraint
            $table->dropForeign(['pendapatan_id']);
            
            // Rename back to jenis_transaksi_id
            $table->renameColumn('pendapatan_id', 'jenis_transaksi_id');
        });
        
        // Restore original foreign key
        Schema::table('pendapatan_harians', function (Blueprint $table) {
            $table->foreign('jenis_transaksi_id')->references('id')->on('jenis_transaksis')->onDelete('cascade');
        });
    }
};
