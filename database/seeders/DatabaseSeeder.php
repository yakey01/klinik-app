<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            // Core master data
            Master\RoleSeeder::class,
            Master\ShiftSeeder::class,
            Master\JenisTindakanSeeder::class,
            RolePermissionSeeder::class,
            UserSeeder::class,
            
            // Location and GPS validation
            LocationValidationSeeder::class,
            GpsSpoofingDetectionSeeder::class,
            
            // NonParamedis system seeders
            WorkLocationSeeder::class,
            NonParamedisUserSeeder::class,
            NonParamedisAttendanceSeeder::class,
            
            // Dokter system seeders
            DokterPermissionsSeeder::class,
            DokterSeeder::class,
            DokterUserSeeder::class,
            DokterJadwalJagaSeeder::class,
            DokterTindakanSeeder::class,
            DokterAttendanceSeeder::class,
        ]);
    }
}
