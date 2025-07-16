<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jadwal_jagas', function (Blueprint $table) {
            // Drop old unique constraint
            $table->dropUnique('unique_schedule');
            
            // Update unit_instalasi field to enum
            $table->enum('unit_kerja', ['Pendaftaran', 'Pelayanan', 'Dokter Jaga'])->after('pegawai_id');
            
            // Add new unique constraint - allow multiple shifts per day but prevent same shift duplicates
            $table->unique(['tanggal_jaga', 'pegawai_id', 'shift_template_id'], 'unique_staff_shift_per_day');
            
            // Add index for better performance
            $table->index(['tanggal_jaga', 'unit_kerja']);
        });
    }

    public function down(): void
    {
        Schema::table('jadwal_jagas', function (Blueprint $table) {
            // Drop new constraints and fields
            $table->dropUnique('unique_staff_shift_per_day');
            $table->dropIndex(['tanggal_jaga', 'unit_kerja']);
            $table->dropColumn('unit_kerja');
            
            // Restore old unique constraint
            $table->unique(['tanggal_jaga', 'pegawai_id', 'shift_template_id'], 'unique_schedule');
        });
    }
};