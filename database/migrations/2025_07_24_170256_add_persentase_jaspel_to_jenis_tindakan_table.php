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
        Schema::table('jenis_tindakan', function (Blueprint $table) {
            $table->decimal('persentase_jaspel', 5, 2)->default(40.00)->after('jasa_non_paramedis');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jenis_tindakan', function (Blueprint $table) {
            $table->dropColumn('persentase_jaspel');
        });
    }
};
