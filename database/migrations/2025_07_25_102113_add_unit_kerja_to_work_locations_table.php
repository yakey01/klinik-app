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
        Schema::table('work_locations', function (Blueprint $table) {
            if (!Schema::hasColumn('work_locations', 'unit_kerja')) {
                $table->string('unit_kerja')->nullable()->after('location_type')
                      ->comment('Unit kerja yang terkait dengan lokasi ini');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('work_locations', function (Blueprint $table) {
            $table->dropColumn('unit_kerja');
        });
    }
};
