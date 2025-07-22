<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Attendance;
use App\Models\WorkLocation;
use App\Models\JadwalJaga;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class NaningAttendanceSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::where('username', 'naning')->first();
        if (!$user) {
            $this->command->error('User naning not found!');
            return;
        }

        $workLocation = WorkLocation::first();
        if (!$workLocation) {
            $this->command->error('No work location found!');
            return;
        }

        // Create realistic attendance data for this month (July 2025)
        $startDate = Carbon::now()->startOfMonth(); // Start of current month
        $endDate = Carbon::now(); // Up to today

        $this->command->info("Creating attendance data for {$user->name} from {$startDate->format('Y-m-d')} to {$endDate->format('Y-m-d')}");

        $attendanceCount = 0;
        $totalDays = 0;

        for ($date = $startDate->copy(); $date <= $endDate; $date->addDay()) {
            $totalDays++;
            
            // Skip some days to make it realistic (weekends, random absences)
            if ($date->isWeekend()) {
                continue; // Skip weekends
            }
            
            // 85% attendance rate - randomly skip some days
            if (rand(1, 100) > 85) {
                continue; // Random absence
            }

            // Create morning shift attendance with correct date
            $timeIn = Carbon::create($date->year, $date->month, $date->day, 7, rand(0, 30)); // 7:00-7:30 AM
            $timeOut = Carbon::create($date->year, $date->month, $date->day, 15, rand(0, 30)); // 3:00-3:30 PM
            
            // Add small GPS variations around the work location
            $latVariation = (rand(-50, 50) / 100000); // ±0.0005 degrees (~50 meters)
            $lonVariation = (rand(-50, 50) / 100000);
            
            $attendance = Attendance::create([
                'user_id' => $user->id,
                'date' => $date->format('Y-m-d'),
                'time_in' => $timeIn,
                'time_out' => $timeOut,
                'latlon_in' => ($workLocation->latitude + $latVariation) . ',' . ($workLocation->longitude + $lonVariation),
                'latlon_out' => ($workLocation->latitude + $latVariation) . ',' . ($workLocation->longitude + $lonVariation),
                'location_name_in' => $workLocation->name,
                'location_name_out' => $workLocation->name,
                'device_info' => json_encode([
                    'user_agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_0 like Mac OS X)',
                    'ip_address' => '192.168.1.' . rand(100, 200),
                    'device_type' => 'mobile'
                ]),
                'status' => rand(1, 100) <= 95 ? 'present' : 'late', // 95% on time, 5% late
                'notes' => rand(1, 10) > 8 ? 'Shift pagi - pelayanan pasien rutin' : null,
                'created_at' => $timeIn,
                'updated_at' => $timeOut->addMinutes(30),
            ]);

            $attendanceCount++;

            // Sometimes add evening shift on the same day (double shift)
            if (rand(1, 100) <= 20) { // 20% chance of double shift
                $eveningTimeIn = Carbon::create($date->year, $date->month, $date->day, 16, rand(0, 30)); // 4:00-4:30 PM
                $eveningTimeOut = Carbon::create($date->year, $date->month, $date->day, 23, rand(0, 30)); // 11:00-11:30 PM
                
                Attendance::create([
                    'user_id' => $user->id,
                    'date' => $date->format('Y-m-d'),
                    'time_in' => $eveningTimeIn,
                    'time_out' => $eveningTimeOut,
                    'latlon_in' => ($workLocation->latitude + $latVariation) . ',' . ($workLocation->longitude + $lonVariation),
                    'latlon_out' => ($workLocation->latitude + $latVariation) . ',' . ($workLocation->longitude + $lonVariation),
                    'location_name_in' => $workLocation->name,
                    'location_name_out' => $workLocation->name,
                    'device_info' => json_encode([
                        'user_agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_0 like Mac OS X)',
                        'ip_address' => '192.168.1.' . rand(100, 200),
                        'device_type' => 'mobile'
                    ]),
                    'status' => 'present',
                    'notes' => 'Shift malam - lembur',
                    'created_at' => $eveningTimeIn,
                    'updated_at' => $eveningTimeOut->addMinutes(30),
                ]);
                $attendanceCount++;
            }
        }

        $attendanceRate = round(($attendanceCount / ($totalDays - 8)) * 100, 1); // Exclude weekends
        
        $this->command->info("✅ Created {$attendanceCount} attendance records for {$user->name}");
        $this->command->info("📊 Attendance rate: {$attendanceRate}% over {$totalDays} days");
        $this->command->info("🏥 Work location: {$workLocation->name}");
    }
}