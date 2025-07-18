<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This migration cleans up dummy data that might appear in bendahara dashboard
     */
    public function up(): void
    {
        // 1. Clean up dummy pendapatan from FinancialTestSeeder
        DB::table('pendapatan')
            ->whereIn('nama_pendapatan', [
                'Konsultasi Umum',
                'Pemeriksaan Gigi', 
                'Tindakan Medis',
                'Laboratorium',
                'Radiologi'
            ])
            ->where('input_by', 1) // Admin user
            ->delete();

        // 2. Clean up dummy pengeluaran from FinancialTestSeeder
        DB::table('pengeluaran')
            ->whereIn('nama_pengeluaran', [
                'Alat Tulis Kantor',
                'Obat-obatan',
                'Maintenance AC'
            ])
            ->where('input_by', 1) // Admin user
            ->delete();

        // 3. Clean up master pengeluaran templates with nominal = 0
        DB::table('pengeluaran')
            ->where('nominal', 0)
            ->where('input_by', 1) // Admin user
            ->delete();

        // 4. Clean up dummy tindakan from DokterTindakanSeeder (optional)
        // Only remove if you want to clean all dummy medical actions
        /*
        DB::table('tindakan')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('pasien')
                    ->whereColumn('pasien.id', 'tindakan.pasien_id')
                    ->where('pasien.nama_lengkap', 'NOT LIKE', 'Pasien Test%')
                    ->where('pasien.nama_lengkap', 'NOT LIKE', 'Dummy%');
            })
            ->delete();
        */
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration is not reversible since we're deleting dummy data
        // If you need the dummy data back, re-run the specific seeders
    }
};