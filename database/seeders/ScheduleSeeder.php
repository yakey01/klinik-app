<?php

namespace Database\Seeders;

use App\Models\Schedule;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class ScheduleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get NonParamedis users and available shifts
        $nonParamedisUsers = User::whereHas('roles', function($q) {
            $q->where('name', 'non_paramedis');
        })->get();
        $shifts = Shift::active()->get();
        
        if ($nonParamedisUsers->isEmpty() || $shifts->isEmpty()) {
            $this->command->warn('⚠️ NonParamedis users or shifts not found. Skipping schedule seeding.');
            return;
        }

        // Create schedules for all NonParamedis users
        $allSchedules = [];
        $startDate = Carbon::today();
        
        foreach ($nonParamedisUsers as $user) {
            for ($i = 0; $i < 14; $i++) {
                $date = $startDate->copy()->addDays($i);
                $dayOfWeek = $date->dayOfWeek;
                
                // Skip Sundays (day off)
                if ($dayOfWeek === 0) {
                    $allSchedules[] = [
                        'user_id' => $user->id,
                        'shift_id' => null,
                        'date' => $date->format('Y-m-d'),
                        'is_day_off' => true,
                        'notes' => 'Hari libur mingguan',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                    continue;
                }
                
                // Assign different shifts based on day
                $shiftIndex = match($dayOfWeek) {
                    1, 3, 5 => 1, // Monday, Wednesday, Friday - Shift Pagi
                    2, 4 => 2,    // Tuesday, Thursday - Shift Siang  
                    6 => 1,       // Saturday - Shift Pagi
                    default => 1,
                };
                
                $allSchedules[] = [
                    'user_id' => $user->id,
                    'shift_id' => $shifts[$shiftIndex]->id,
                    'date' => $date->format('Y-m-d'),
                    'is_day_off' => false,
                    'notes' => $this->generateScheduleNotes($date, $shifts[$shiftIndex]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        Schedule::insert($allSchedules);
        
        $this->command->info('✅ Created ' . count($allSchedules) . ' schedules for ' . $nonParamedisUsers->count() . ' NonParamedis users');
        
        // Create schedules for other users as well
        $this->createSchedulesForOtherUsers($shifts);
    }
    
    /**
     * Create schedules for other users
     */
    private function createSchedulesForOtherUsers($shifts)
    {
        $otherUsers = User::whereIn('id', [1, 4, 10, 19]) // Admin, Petugas, Apoteker, Tina
            ->get();
            
        $additionalSchedules = [];
        $startDate = Carbon::today();
        
        foreach ($otherUsers as $user) {
            for ($i = 0; $i < 7; $i++) { // Next 7 days
                $date = $startDate->copy()->addDays($i);
                $dayOfWeek = $date->dayOfWeek;
                
                if ($dayOfWeek === 0) continue; // Skip Sundays
                
                $shiftIndex = ($dayOfWeek % 2 === 1) ? 1 : 2; // Alternate between Pagi and Siang
                
                $additionalSchedules[] = [
                    'user_id' => $user->id,
                    'shift_id' => $shifts[$shiftIndex]->id,
                    'date' => $date->format('Y-m-d'),
                    'is_day_off' => false,
                    'notes' => "Jadwal rutin untuk {$user->name}",
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }
        
        if (!empty($additionalSchedules)) {
            Schedule::insert($additionalSchedules);
            $this->command->info('✅ Created ' . count($additionalSchedules) . ' additional schedules for other users');
        }
    }
    
    /**
     * Generate schedule notes based on date and shift
     */
    private function generateScheduleNotes($date, $shift): string
    {
        $dayName = $date->locale('id')->dayName;
        $notes = [
            "Jadwal {$shift->name} - {$dayName}",
            "Shift normal {$shift->name}",
            "Jadwal rutin mingguan",
            "Schedule untuk {$dayName}",
        ];
        
        return $notes[array_rand($notes)];
    }
}