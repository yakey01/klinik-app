<?php

namespace Database\Seeders\Master;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PengaturanJaspelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // This seeder can be used to populate jaspel configuration settings
        // For now, we'll use a simple configuration table approach
        
        $pengaturanJaspel = [
            [
                'key' => 'jaspel_dokter_percentage',
                'value' => '60',
                'description' => 'Persentase jaspel untuk dokter dari total tarif tindakan',
                'type' => 'percentage',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'jaspel_paramedis_percentage',
                'value' => '25',
                'description' => 'Persentase jaspel untuk paramedis dari total tarif tindakan',
                'type' => 'percentage',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'jaspel_non_paramedis_percentage',
                'value' => '15',
                'description' => 'Persentase jaspel untuk non-paramedis dari total tarif tindakan',
                'type' => 'percentage',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'auto_generate_jaspel',
                'value' => 'true',
                'description' => 'Otomatis generate jaspel ketika tindakan selesai',
                'type' => 'boolean',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'jaspel_approval_required',
                'value' => 'true',
                'description' => 'Jaspel memerlukan approval sebelum dibayar',
                'type' => 'boolean',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'minimum_jaspel_amount',
                'value' => '10000',
                'description' => 'Minimum nominal jaspel yang bisa dibayar',
                'type' => 'currency',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        // Note: This assumes you have a settings table
        // You may need to create this table or adjust based on your settings implementation
        foreach ($pengaturanJaspel as $setting) {
            DB::table('settings')->insertOrIgnore($setting);
        }
    }
}