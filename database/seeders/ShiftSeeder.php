<?php

namespace Database\Seeders;

use App\Models\Shift;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class ShiftSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $shifts = [
            [
                'name' => 'Shift Pagi',
                'start_time' => '08:00:00',
                'end_time' => '16:00:00',
                'description' => 'Shift pagi untuk staff administrasi dan non-paramedis',
                'is_active' => true,
            ],
            [
                'name' => 'Shift Siang',
                'start_time' => '13:00:00',
                'end_time' => '21:00:00',
                'description' => 'Shift siang untuk staff administrasi',
                'is_active' => true,
            ],
            [
                'name' => 'Shift Malam',
                'start_time' => '21:00:00',
                'end_time' => '08:00:00',
                'description' => 'Shift malam untuk keamanan dan petugas jaga',
                'is_active' => true,
            ],
            [
                'name' => 'Shift Regular',
                'start_time' => '09:00:00',
                'end_time' => '17:00:00',
                'description' => 'Shift regular untuk staff kantor',
                'is_active' => true,
            ],
            [
                'name' => 'Shift Paruh Waktu',
                'start_time' => '10:00:00',
                'end_time' => '14:00:00',
                'description' => 'Shift paruh waktu 4 jam',
                'is_active' => true,
            ],
        ];

        foreach ($shifts as $shift) {
            Shift::create($shift);
        }

        $this->command->info('✅ Created ' . count($shifts) . ' shifts successfully');
    }
}