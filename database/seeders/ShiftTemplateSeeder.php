<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ShiftTemplate;

class ShiftTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing data
        ShiftTemplate::truncate();
        
        // Insert shift templates from local
        $shifts = [
            [
                'nama_shift' => 'Pagi',
                'jam_masuk' => '06:00:00',
                'jam_pulang' => '12:00:00',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama_shift' => 'Sore',
                'jam_masuk' => '16:00:00',
                'jam_pulang' => '21:00:00',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama_shift' => 'test 1',
                'jam_masuk' => '08:00:00',
                'jam_pulang' => '09:00:00',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama_shift' => 'tes 2',
                'jam_masuk' => '08:30:00',
                'jam_pulang' => '09:00:00',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama_shift' => 'tes 3',
                'jam_masuk' => '09:59:00',
                'jam_pulang' => '13:00:00',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama_shift' => 'tes 4',
                'jam_masuk' => '10:59:00',
                'jam_pulang' => '13:00:00',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($shifts as $shift) {
            ShiftTemplate::create($shift);
        }

        $this->command->info('ShiftTemplate seeder completed: ' . count($shifts) . ' records created.');
    }
}