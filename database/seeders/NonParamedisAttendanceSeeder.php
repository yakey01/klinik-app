<?php

namespace Database\Seeders;

use App\Models\NonParamedisAttendance;
use App\Models\User;
use App\Models\WorkLocation;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class NonParamedisAttendanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all active non-paramedis users
        $nonParamedisUsers = User::whereHas('role', function ($query) {
            $query->where('name', 'non_paramedis');
        })->where('is_active', true)->get();

        // Get all active work locations
        $workLocations = WorkLocation::where('is_active', true)->get();

        if ($nonParamedisUsers->isEmpty() || $workLocations->isEmpty()) {
            $this->command->error('Please run NonParamedisUserSeeder and WorkLocationSeeder first!');
            return;
        }

        // Define user work patterns
        $userPatterns = [
            'siti.nurhaliza' => [
                'punctuality' => 'very_punctual', // Always early
                'work_hours' => 'standard', // 8-9 hours
                'attendance_rate' => 95, // 95% attendance
                'preferred_location' => 'Kantor Pusat Klinik Dokterku',
                'pattern' => 'consistent'
            ],
            'bambang.wijaya' => [
                'punctuality' => 'punctual', // Usually on time
                'work_hours' => 'standard',
                'attendance_rate' => 92,
                'preferred_location' => 'Kantor Pusat Klinik Dokterku',
                'pattern' => 'consistent'
            ],
            'ahmad.rizky' => [
                'punctuality' => 'sometimes_late', // IT maintenance
                'work_hours' => 'overtime', // Sometimes long hours
                'attendance_rate' => 88,
                'preferred_location' => 'Kantor Pusat Klinik Dokterku',
                'pattern' => 'irregular'
            ],
            'dewi.sartika' => [
                'punctuality' => 'punctual',
                'work_hours' => 'flexible', // Customer service
                'attendance_rate' => 90,
                'preferred_location' => 'Klinik Dokterku Cabang Malang Kota',
                'pattern' => 'flexible'
            ],
            'joko.susilo' => [
                'punctuality' => 'mixed', // Security shifts
                'work_hours' => 'long', // Security guards work longer
                'attendance_rate' => 85,
                'preferred_location' => 'multiple', // Moves between locations
                'pattern' => 'shift_work'
            ],
            'linda.maharani' => [
                'punctuality' => 'punctual',
                'work_hours' => 'standard',
                'attendance_rate' => 93,
                'preferred_location' => 'Kantor Pusat Klinik Dokterku',
                'pattern' => 'consistent'
            ],
            'eko.prasetyo' => [
                'punctuality' => 'very_punctual', // Early bird for inventory
                'work_hours' => 'standard',
                'attendance_rate' => 96,
                'preferred_location' => 'Apotek Dokterku',
                'pattern' => 'early_bird'
            ],
            'putri.permatasari' => [
                'punctuality' => 'flexible', // Marketing creative hours
                'work_hours' => 'flexible',
                'attendance_rate' => 80, // Sometimes remote
                'preferred_location' => 'multiple',
                'pattern' => 'creative'
            ],
            'agus.setiawan' => [
                'punctuality' => 'very_punctual', // Maintenance early start
                'work_hours' => 'long',
                'attendance_rate' => 94,
                'preferred_location' => 'multiple', // Goes to different locations
                'pattern' => 'maintenance'
            ],
            'rini.handayani' => [
                'punctuality' => 'learning', // New employee
                'work_hours' => 'standard',
                'attendance_rate' => 87, // Still learning
                'preferred_location' => 'Kantor Pusat Klinik Dokterku',
                'pattern' => 'learning'
            ]
        ];

        // Generate attendance for the last 30 days
        $startDate = Carbon::now()->subDays(30);
        $endDate = Carbon::now()->subDay(); // Exclude today

        $attendanceCount = 0;
        $approverUsers = User::whereHas('role', function ($query) {
            $query->whereIn('name', ['admin', 'manajer']);
        })->get();

        foreach ($nonParamedisUsers as $user) {
            $pattern = $userPatterns[$user->username] ?? [
                'punctuality' => 'punctual',
                'work_hours' => 'standard',
                'attendance_rate' => 90,
                'preferred_location' => 'Kantor Pusat Klinik Dokterku',
                'pattern' => 'consistent'
            ];

            // Get preferred work location
            $preferredLocation = $workLocations->where('name', $pattern['preferred_location'])->first();
            if (!$preferredLocation) {
                $preferredLocation = $workLocations->first();
            }

            $currentDate = $startDate->copy();
            
            while ($currentDate->lte($endDate)) {
                // Skip weekends for most users (except security and maintenance)
                if ($currentDate->isWeekend() && !in_array($user->username, ['joko.susilo', 'agus.setiawan'])) {
                    $currentDate->addDay();
                    continue;
                }

                // Determine if user attended based on attendance rate
                $shouldAttend = rand(1, 100) <= $pattern['attendance_rate'];
                
                if (!$shouldAttend) {
                    $currentDate->addDay();
                    continue;
                }

                // Select work location
                $workLocation = $this->selectWorkLocation($user, $preferredLocation, $workLocations, $pattern);

                // Generate attendance based on pattern
                $attendance = $this->generateAttendanceForUser($user, $currentDate, $workLocation, $pattern);
                
                if ($attendance) {
                    // Set approval status and approver
                    $this->setApprovalStatus($attendance, $approverUsers, $currentDate);
                    $attendanceCount++;
                }

                $currentDate->addDay();
            }
        }

        $this->command->info("NonParamedisAttendanceSeeder completed! Created {$attendanceCount} attendance records for the last 30 days.");
    }

    /**
     * Select work location based on user pattern
     */
    private function selectWorkLocation($user, $preferredLocation, $workLocations, $pattern)
    {
        if ($pattern['preferred_location'] === 'multiple') {
            // Users who work at multiple locations
            $locationChoices = $workLocations->take(3); // Rotate between first 3 locations
            return $locationChoices->random();
        }

        // 80% chance to work at preferred location, 20% at other locations
        if (rand(1, 100) <= 80) {
            return $preferredLocation;
        }

        return $workLocations->where('id', '!=', $preferredLocation->id)->random();
    }

    /**
     * Generate attendance record for a user on a specific date
     */
    private function generateAttendanceForUser($user, $date, $workLocation, $pattern)
    {
        // Generate check-in time based on punctuality pattern
        $checkInTime = $this->generateCheckInTime($date, $pattern['punctuality']);
        
        // Generate work duration based on work hours pattern
        $workMinutes = $this->generateWorkDuration($pattern['work_hours']);
        
        // Check-out time
        $checkOutTime = $checkInTime->copy()->addMinutes($workMinutes);

        // Location coordinates with slight variations for realism
        $checkInLat = (float)$workLocation->latitude + fake()->randomFloat(6, -0.0005, 0.0005);
        $checkInLng = (float)$workLocation->longitude + fake()->randomFloat(6, -0.0005, 0.0005);
        $checkOutLat = (float)$workLocation->latitude + fake()->randomFloat(6, -0.0005, 0.0005);
        $checkOutLng = (float)$workLocation->longitude + fake()->randomFloat(6, -0.0005, 0.0005);

        // Calculate distance from work location center
        $checkInDistance = fake()->randomFloat(1, 5, 50);
        $checkOutDistance = fake()->randomFloat(1, 5, 50);

        // GPS accuracy
        $checkInAccuracy = fake()->randomFloat(1, 3, 25);
        $checkOutAccuracy = fake()->randomFloat(1, 3, 25);

        // Determine if location is valid (within geofence)
        $checkInValid = $checkInDistance <= $workLocation->radius_meters;
        $checkOutValid = $checkOutDistance <= $workLocation->radius_meters;

        // 5% chance of incomplete attendance (forgot to check out)
        $isComplete = rand(1, 100) > 5;

        return NonParamedisAttendance::create([
            'user_id' => $user->id,
            'work_location_id' => $workLocation->id,
            'attendance_date' => $date->toDateString(),
            
            // Check-in data
            'check_in_time' => $checkInTime,
            'check_in_latitude' => $checkInLat,
            'check_in_longitude' => $checkInLng,
            'check_in_accuracy' => $checkInAccuracy,
            'check_in_address' => fake()->address() . ', Malang, Jawa Timur',
            'check_in_distance' => $checkInDistance,
            'check_in_valid_location' => $checkInValid,
            
            // Check-out data (only if complete)
            'check_out_time' => $isComplete ? $checkOutTime : null,
            'check_out_latitude' => $isComplete ? $checkOutLat : null,
            'check_out_longitude' => $isComplete ? $checkOutLng : null,
            'check_out_accuracy' => $isComplete ? $checkOutAccuracy : null,
            'check_out_address' => $isComplete ? (fake()->address() . ', Malang, Jawa Timur') : null,
            'check_out_distance' => $isComplete ? $checkOutDistance : null,
            'check_out_valid_location' => $isComplete ? $checkOutValid : false,
            
            // Work duration
            'total_work_minutes' => $isComplete ? $workMinutes : null,
            'status' => $isComplete ? 'checked_out' : 'checked_in',
            
            // Notes (sometimes)
            'notes' => fake()->optional(0.2)->randomElement([
                'Sesuai jadwal',
                'Lembur karena deadline',
                'Meeting dengan klien',
                'Training karyawan',
                'Maintenance sistem',
                'Rapat koordinasi'
            ]),
            
            // Device information
            'device_info' => json_encode([
                'platform' => fake()->randomElement(['Android', 'iOS', 'Web']),
                'version' => fake()->randomElement(['10.0', '11.0', '12.0', '13.0', '14.0']),
                'model' => fake()->randomElement(['Samsung Galaxy', 'iPhone', 'Xiaomi', 'OPPO', 'Vivo']),
            ]),
            'browser_info' => fake()->userAgent(),
            'ip_address' => fake()->ipv4(),
            
            // GPS metadata
            'gps_metadata' => [
                'provider' => fake()->randomElement(['gps', 'network', 'passive']),
                'satellites' => fake()->numberBetween(4, 12),
                'speed' => fake()->randomFloat(1, 0, 5),
                'bearing' => fake()->randomFloat(1, 0, 360),
                'altitude' => fake()->randomFloat(1, 400, 600),
            ],
            
            // Spoofing detection (rarely positive)
            'suspected_spoofing' => fake()->boolean(3), // 3% chance
            
            // Will be set by setApprovalStatus method
            'approval_status' => 'pending',
        ]);
    }

    /**
     * Generate check-in time based on punctuality pattern
     */
    private function generateCheckInTime($date, $punctuality)
    {
        $baseHour = 8; // 8 AM base
        $baseMinute = 0;

        switch ($punctuality) {
            case 'very_punctual':
                // 7:30 - 7:50 AM
                $hour = 7;
                $minute = fake()->numberBetween(30, 50);
                break;
                
            case 'punctual':
                // 7:50 - 8:10 AM
                $hour = fake()->boolean(70) ? 7 : 8;
                $minute = $hour === 7 ? fake()->numberBetween(50, 59) : fake()->numberBetween(0, 10);
                break;
                
            case 'sometimes_late':
                // 7:45 - 9:15 AM (wider range)
                $choices = [
                    ['hour' => 7, 'min_range' => [45, 59]], // Early
                    ['hour' => 8, 'min_range' => [0, 30]],  // On time
                    ['hour' => 8, 'min_range' => [31, 59]], // Slightly late
                    ['hour' => 9, 'min_range' => [0, 15]]   // Late
                ];
                $choice = fake()->randomElement($choices);
                $hour = $choice['hour'];
                $minute = fake()->numberBetween($choice['min_range'][0], $choice['min_range'][1]);
                break;
                
            case 'mixed':
                // Very random: 6:30 - 9:30 AM
                $hour = fake()->numberBetween(6, 9);
                $minute = $hour === 9 ? fake()->numberBetween(0, 30) : fake()->numberBetween(0, 59);
                break;
                
            case 'flexible':
                // 8:30 - 10:00 AM
                $hour = fake()->numberBetween(8, 10);
                $minute = $hour === 10 ? 0 : fake()->numberBetween(0, 59);
                break;
                
            case 'learning':
                // New employee: 7:30 - 8:45 AM (trying to be early but inconsistent)
                $hour = fake()->boolean(60) ? 7 : 8;
                $minute = $hour === 7 ? fake()->numberBetween(30, 59) : fake()->numberBetween(0, 45);
                break;
                
            default:
                $hour = $baseHour;
                $minute = fake()->numberBetween(0, 15);
        }

        return $date->copy()->setTime($hour, $minute, fake()->numberBetween(0, 59));
    }

    /**
     * Generate work duration based on work hours pattern
     */
    private function generateWorkDuration($workHoursPattern)
    {
        switch ($workHoursPattern) {
            case 'standard':
                // 7.5 - 9 hours
                return fake()->numberBetween(450, 540); // 7.5-9 hours in minutes
                
            case 'overtime':
                // 8 - 12 hours (IT work)
                return fake()->numberBetween(480, 720);
                
            case 'flexible':
                // 6 - 10 hours
                return fake()->numberBetween(360, 600);
                
            case 'long':
                // 9 - 12 hours (security/maintenance)
                return fake()->numberBetween(540, 720);
                
            default:
                return fake()->numberBetween(450, 540);
        }
    }

    /**
     * Set approval status and approver
     */
    private function setApprovalStatus($attendance, $approverUsers, $date)
    {
        // Recent dates (last 7 days) are more likely to be pending
        $daysSinceAttendance = Carbon::now()->diffInDays($date);
        
        if ($daysSinceAttendance <= 7) {
            // Recent attendance: 40% pending, 55% approved, 5% rejected
            $statusRand = rand(1, 100);
            if ($statusRand <= 40) {
                $status = 'pending';
            } elseif ($statusRand <= 95) {
                $status = 'approved';
            } else {
                $status = 'rejected';
            }
        } else {
            // Older attendance: 5% pending, 90% approved, 5% rejected
            $statusRand = rand(1, 100);
            if ($statusRand <= 5) {
                $status = 'pending';
            } elseif ($statusRand <= 95) {
                $status = 'approved';
            } else {
                $status = 'rejected';
            }
        }

        $updateData = ['approval_status' => $status];

        if ($status !== 'pending' && $approverUsers->isNotEmpty()) {
            $approver = $approverUsers->random();
            $approvedAt = $date->copy()->addHours(fake()->numberBetween(1, 48));
            
            $updateData['approved_by'] = $approver->id;
            $updateData['approved_at'] = $approvedAt;
            
            if ($status === 'rejected') {
                $updateData['approval_notes'] = fake()->randomElement([
                    'Lokasi tidak sesuai dengan area kerja yang ditentukan',
                    'Waktu check-in tidak sesuai dengan jadwal kerja',
                    'GPS accuracy terlalu rendah, mohon gunakan GPS yang lebih akurat',
                    'Foto check-in tidak jelas atau tidak sesuai',
                    'Durasi kerja tidak wajar, mohon konfirmasi aktivitas',
                    'Data attendance tidak lengkap'
                ]);
            } elseif (fake()->boolean(20)) { // 20% chance of approval notes
                $updateData['approval_notes'] = fake()->randomElement([
                    'Disetujui dengan catatan: perhatikan ketepatan waktu',
                    'Approved - good attendance record',
                    'Disetujui, lanjutkan konsistensi kehadiran',
                    'Approved with overtime compensation'
                ]);
            }
        }

        $attendance->update($updateData);
    }
}