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
        Schema::table('tindakan', function (Blueprint $table) {
            // Drop existing foreign key constraints
            $table->dropForeign(['dokter_id']);
            $table->dropForeign(['paramedis_id']);
            $table->dropForeign(['non_paramedis_id']);
            
            // Add correct foreign key constraints
            $table->foreign('dokter_id')->references('id')->on('dokters')->onDelete('cascade');
            $table->foreign('paramedis_id')->references('id')->on('pegawais')->onDelete('set null');
            $table->foreign('non_paramedis_id')->references('id')->on('pegawais')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tindakan', function (Blueprint $table) {
            // Drop new foreign key constraints
            $table->dropForeign(['dokter_id']);
            $table->dropForeign(['paramedis_id']);
            $table->dropForeign(['non_paramedis_id']);
            
            // Restore original foreign key constraints
            $table->foreign('dokter_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('paramedis_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('non_paramedis_id')->references('id')->on('users')->onDelete('set null');
        });
    }
};
