<?php

namespace Database\Seeders\Master;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TarifUangDudukSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // This seeder can be used to populate uang duduk tariff settings
        // For now, we'll use a simple configuration table approach
        
        $tarifUangDuduk = [
            [
                'key' => 'uang_duduk_dokter_pagi',
                'value' => '100000',
                'description' => 'Uang duduk dokter untuk shift pagi',
                'type' => 'currency',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'uang_duduk_dokter_siang',
                'value' => '100000',
                'description' => 'Uang duduk dokter untuk shift siang',
                'type' => 'currency',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'uang_duduk_dokter_malam',
                'value' => '150000',
                'description' => 'Uang duduk dokter untuk shift malam',
                'type' => 'currency',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'uang_duduk_paramedis_pagi',
                'value' => '50000',
                'description' => 'Uang duduk paramedis untuk shift pagi',
                'type' => 'currency',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'uang_duduk_paramedis_siang',
                'value' => '50000',
                'description' => 'Uang duduk paramedis untuk shift siang',
                'type' => 'currency',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'uang_duduk_paramedis_malam',
                'value' => '75000',
                'description' => 'Uang duduk paramedis untuk shift malam',
                'type' => 'currency',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'uang_duduk_non_paramedis_pagi',
                'value' => '30000',
                'description' => 'Uang duduk non-paramedis untuk shift pagi',
                'type' => 'currency',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'uang_duduk_non_paramedis_siang',
                'value' => '30000',
                'description' => 'Uang duduk non-paramedis untuk shift siang',
                'type' => 'currency',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'uang_duduk_non_paramedis_malam',
                'value' => '45000',
                'description' => 'Uang duduk non-paramedis untuk shift malam',
                'type' => 'currency',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'uang_duduk_approval_required',
                'value' => 'true',
                'description' => 'Uang duduk memerlukan approval sebelum dibayar',
                'type' => 'boolean',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        // Note: This assumes you have a settings table
        // You may need to create this table or adjust based on your settings implementation
        foreach ($tarifUangDuduk as $setting) {
            DB::table('settings')->insertOrIgnore($setting);
        }
    }
}