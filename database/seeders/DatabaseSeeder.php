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
            Master\RoleSeeder::class,
            Master\ShiftSeeder::class,
            Master\JenisTindakanSeeder::class,
            RolePermissionSeeder::class,
            UserSeeder::class,
            LocationValidationSeeder::class,
            GpsSpoofingDetectionSeeder::class,
        ]);
    }
}
