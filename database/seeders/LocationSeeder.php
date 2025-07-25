<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Location;
use App\Models\User;

class LocationSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        $admin = User::where('email', 'admin@dokterku.com')->first();
        if (!$admin) {
            $admin = User::first();
        }

        $locations = [
            [
                'name' => 'Klinik Utama Jakarta Pusat',
                'latitude' => -6.2088,
                'longitude' => 106.8456,
                'radius' => 100,
                'created_by' => $admin?->id ?? 1,
            ],
            [
                'name' => 'Klinik Cabang Bekasi',
                'latitude' => -6.2349,
                'longitude' => 106.9896,
                'radius' => 150,
                'created_by' => $admin?->id ?? 1,
            ],
            [
                'name' => 'RS Pratama Tangerang',
                'latitude' => -6.1783,
                'longitude' => 106.6319,
                'radius' => 200,
                'created_by' => $admin?->id ?? 1,
            ],
            [
                'name' => 'Puskesmas Depok',
                'latitude' => -6.4025,
                'longitude' => 106.7942,
                'radius' => 120,
                'created_by' => $admin?->id ?? 1,
            ],
        ];

        foreach ($locations as $locationData) {
            Location::create($locationData);
        }

        $this->command->info('Location seeder completed successfully.');
    }
}