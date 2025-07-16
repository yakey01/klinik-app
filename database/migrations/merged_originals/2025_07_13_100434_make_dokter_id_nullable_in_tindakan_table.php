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
            // Make dokter_id nullable since dokter is optional
            $table->unsignedBigInteger('dokter_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tindakan', function (Blueprint $table) {
            // Make dokter_id not nullable (restore original)
            $table->unsignedBigInteger('dokter_id')->nullable(false)->change();
        });
    }
};
