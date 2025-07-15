<?php

namespace Database\Seeders;

use App\Models\NonParamedisAttendance;
use App\Models\Role;
use App\Models\User;
use App\Models\WorkLocation;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class NonParamedisAttendanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            // Get the non_paramedis role
            $nonParamedisRole = Role::where('name', 'non_paramedis')->first();
            
            if (!$nonParamedisRole) {
                $this->command->error('Non-paramedis role not found. Please run RoleSeeder first.');
                return;
            }

            // Get work locations
            $workLocations = WorkLocation::active()->get();
            
            if ($workLocations->isEmpty()) {
                $this->command->error('No active work locations found. Please run WorkLocationSeeder first.');
                return;
            }

            $mainLocation = $workLocations->where('location_type', 'main_office')->first() ?: $workLocations->first();

            // Create 3 non_paramedis users with different patterns
            $users = $this->createNonParamedisUsers($nonParamedisRole->id);

            // Create 7 days of attendance data for each user
            $this->createAttendanceData($users, $mainLocation);

            $this->command->info('Successfully created 3 non-paramedis users with 7 days of attendance data each.');
        });
    }

    /**
     * Create non-paramedis users with different characteristics
     */
    private function createNonParamedisUsers(int $roleId): array
    {
        $users = [];

        // User 1: Punctual and consistent worker
        $users[] = User::create([
            'role_id' => $roleId,
            'name' => 'Sari Lestari',
            'email' => 'sari.lestari@dokterku.com',
            'username' => 'sari.lestari',
            'password' => Hash::make('password'),
            'nip' => 'NP001',
            'no_telepon' => '081234567890',
            'tanggal_bergabung' => Carbon::now()->subMonths(6),
            'is_active' => true,
        ]);

        // User 2: Occasionally late but hardworking
        $users[] = User::create([
            'role_id' => $roleId,
            'name' => 'Budi Santoso',
            'email' => 'budi.santoso@dokterku.com',
            'username' => 'budi.santoso',
            'password' => Hash::make('password'),
            'nip' => 'NP002',
            'no_telepon' => '081234567891',
            'tanggal_bergabung' => Carbon::now()->subMonths(4),
            'is_active' => true,
        ]);

        // User 3: Variable schedule, sometimes overtime
        $users[] = User::create([
            'role_id' => $roleId,
            'name' => 'Dewi Kusuma',
            'email' => 'dewi.kusuma@dokterku.com',
            'username' => 'dewi.kusuma',
            'password' => Hash::make('password'),
            'nip' => 'NP003',
            'no_telepon' => '081234567892',
            'tanggal_bergabung' => Carbon::now()->subMonths(8),
            'is_active' => true,
        ]);

        return $users;
    }

    /**
     * Create realistic attendance data for each user
     */
    private function createAttendanceData(array $users, WorkLocation $workLocation): void
    {
        foreach ($users as $index => $user) {
            $this->createUserAttendancePattern($user, $workLocation, $index);
        }
    }

    /**
     * Create attendance pattern based on user characteristics
     */
    private function createUserAttendancePattern(User $user, WorkLocation $workLocation, int $userIndex): void
    {
        // Create attendance for the past 7 days (excluding today)
        for ($i = 7; $i >= 1; $i--) {
            $date = Carbon::now()->subDays($i);
            
            // Skip weekends for most users
            if ($date->isWeekend() && $userIndex !== 2) {
                continue;
            }

            $this->createDailyAttendance($user, $workLocation, $date, $userIndex);
        }
    }

    /**
     * Create daily attendance based on user pattern
     */
    private function createDailyAttendance(User $user, WorkLocation $workLocation, Carbon $date, int $userIndex): void
    {
        // Different patterns for each user
        $patterns = [
            0 => $this->getPunctualPattern($date),      // Sari - Always on time
            1 => $this->getOccasionallyLatePattern($date), // Budi - Sometimes late
            2 => $this->getVariablePattern($date),      // Dewi - Variable schedule
        ];

        $pattern = $patterns[$userIndex] ?? $patterns[0];

        // Skip if this is a day off for this pattern
        if (!$pattern['should_work']) {
            return;
        }

        // Calculate check-in coordinates (slightly varied within geofence)
        $checkInCoords = $this->generateLocationWithinGeofence($workLocation);
        $checkOutCoords = $this->generateLocationWithinGeofence($workLocation);

        // Create attendance record
        $attendance = NonParamedisAttendance::create([
            'user_id' => $user->id,
            'work_location_id' => $workLocation->id,
            'attendance_date' => $date->toDateString(),
            'check_in_time' => $pattern['check_in_time'],
            'check_in_latitude' => $checkInCoords['lat'],
            'check_in_longitude' => $checkInCoords['lng'],
            'check_in_accuracy' => rand(5, 20),
            'check_in_distance' => rand(10, 80),
            'check_in_valid_location' => true,
            'check_out_time' => $pattern['check_out_time'],
            'check_out_latitude' => $checkOutCoords['lat'],
            'check_out_longitude' => $checkOutCoords['lng'],
            'check_out_accuracy' => rand(5, 20),
            'check_out_distance' => rand(10, 80),
            'check_out_valid_location' => true,
            'total_work_minutes' => $pattern['work_minutes'],
            'status' => 'checked_out',
            'approval_status' => rand(1, 10) <= 8 ? 'approved' : 'pending', // 80% approved
            'approved_by' => rand(1, 10) <= 8 ? 1 : null, // Assume admin user ID 1
            'approved_at' => rand(1, 10) <= 8 ? $date->copy()->addHours(rand(1, 3)) : null,
            'device_info' => [
                'user_agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_0 like Mac OS X) AppleWebKit/605.1.15',
                'ip_address' => $this->generateRandomIP(),
                'platform' => 'mobile_web'
            ],
            'gps_metadata' => [
                'accuracy' => rand(5, 20),
                'provider' => 'gps',
                'timestamp' => $pattern['check_in_time']->toISOString(),
                'speed' => 0,
                'bearing' => rand(0, 360)
            ],
            'suspected_spoofing' => false,
            'notes' => $pattern['notes'] ?? null,
        ]);

        // Add some variety to approval notes
        if ($attendance->approval_status === 'approved' && rand(1, 10) <= 3) {
            $attendance->update([
                'approval_notes' => $this->getRandomApprovalNote()
            ]);
        }
    }

    /**
     * Generate coordinates within work location geofence
     */
    private function generateLocationWithinGeofence(WorkLocation $workLocation): array
    {
        $radiusInDegrees = $workLocation->radius_meters / 111320; // Approximate conversion
        $randomRadius = $radiusInDegrees * sqrt(rand(0, 100) / 100); // Random point within circle
        $randomAngle = rand(0, 360) * pi() / 180;

        return [
            'lat' => $workLocation->latitude + ($randomRadius * cos($randomAngle)),
            'lng' => $workLocation->longitude + ($randomRadius * sin($randomAngle))
        ];
    }

    /**
     * Punctual worker pattern (Sari)
     */
    private function getPunctualPattern(Carbon $date): array
    {
        if ($date->isWeekend()) {
            return ['should_work' => false];
        }

        $checkIn = $date->copy()->setTime(7, 55 + rand(0, 10)); // 7:55-8:05
        $checkOut = $date->copy()->setTime(17, 0 + rand(0, 15)); // 17:00-17:15
        
        return [
            'should_work' => true,
            'check_in_time' => $checkIn,
            'check_out_time' => $checkOut,
            'work_minutes' => $checkOut->diffInMinutes($checkIn),
            'notes' => null
        ];
    }

    /**
     * Occasionally late pattern (Budi)
     */
    private function getOccasionallyLatePattern(Carbon $date): array
    {
        if ($date->isWeekend()) {
            return ['should_work' => false];
        }

        // Sometimes late (30% chance)
        $isLate = rand(1, 10) <= 3;
        $checkInHour = $isLate ? 8 : 7;
        $checkInMinute = $isLate ? rand(10, 30) : rand(55, 59);
        
        $checkIn = $date->copy()->setTime($checkInHour, $checkInMinute);
        
        // Compensates by staying later if late
        $extraMinutes = $isLate ? rand(20, 45) : rand(0, 10);
        $checkOut = $date->copy()->setTime(17, 0 + $extraMinutes);
        
        return [
            'should_work' => true,
            'check_in_time' => $checkIn,
            'check_out_time' => $checkOut,
            'work_minutes' => $checkOut->diffInMinutes($checkIn),
            'notes' => $isLate ? 'Terlambat karena kemacetan' : null
        ];
    }

    /**
     * Variable schedule pattern (Dewi)
     */
    private function getVariablePattern(Carbon $date): array
    {
        // Works some weekends (20% chance)
        if ($date->isWeekend() && rand(1, 10) > 2) {
            return ['should_work' => false];
        }

        // Variable schedule
        $patterns = [
            ['in' => [7, 45], 'out' => [16, 30]], // Early bird
            ['in' => [8, 0], 'out' => [17, 0]],   // Normal
            ['in' => [8, 15], 'out' => [17, 30]], // Slightly late
            ['in' => [9, 0], 'out' => [18, 30]],  // Late start, late finish
        ];

        $pattern = $patterns[array_rand($patterns)];
        
        $checkIn = $date->copy()->setTime($pattern['in'][0], $pattern['in'][1] + rand(-10, 10));
        $checkOut = $date->copy()->setTime($pattern['out'][0], $pattern['out'][1] + rand(-15, 30));
        
        return [
            'should_work' => true,
            'check_in_time' => $checkIn,
            'check_out_time' => $checkOut,
            'work_minutes' => $checkOut->diffInMinutes($checkIn),
            'notes' => $date->isWeekend() ? 'Kerja weekend untuk project khusus' : null
        ];
    }

    /**
     * Generate random IP address
     */
    private function generateRandomIP(): string
    {
        return implode('.', [
            rand(192, 203),
            rand(168, 255),
            rand(1, 254),
            rand(1, 254)
        ]);
    }

    /**
     * Get random approval note
     */
    private function getRandomApprovalNote(): string
    {
        $notes = [
            'Presensi sesuai jadwal kerja',
            'Lokasi valid, waktu kerja memadai',
            'Disetujui setelah verifikasi',
            'Kinerja baik, presensi tepat waktu',
            'Approved - Normal working hours'
        ];

        return $notes[array_rand($notes)];
    }
}