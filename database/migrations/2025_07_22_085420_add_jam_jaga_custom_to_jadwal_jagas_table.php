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
        Schema::table('jadwal_jagas', function (Blueprint $table) {
            $table->time('jam_jaga_custom')->nullable()->after('keterangan')
                ->comment('Custom start time override - if null, use shift template time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jadwal_jagas', function (Blueprint $table) {
            $table->dropColumn('jam_jaga_custom');
        });
    }
};
