<?php

namespace Database\Factories;

use App\Models\NonParamedisAttendance;
use App\Models\User;
use App\Models\WorkLocation;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\NonParamedisAttendance>
 */
class NonParamedisAttendanceFactory extends Factory
{
    protected $model = NonParamedisAttendance::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Get random work location
        $workLocation = WorkLocation::inRandomOrder()->first();
        
        // Generate attendance date (last 30 days)
        $attendanceDate = $this->faker->dateTimeBetween('-30 days', 'now');
        $carbonDate = Carbon::parse($attendanceDate);
        
        // Generate realistic check-in time (7:00 AM - 9:30 AM)
        $checkInHour = $this->faker->numberBetween(7, 9);
        $checkInMinute = $this->faker->numberBetween(0, 59);
        if ($checkInHour === 9) {
            $checkInMinute = $this->faker->numberBetween(0, 30); // Max 9:30 AM
        }
        
        $checkInTime = $carbonDate->copy()->setTime($checkInHour, $checkInMinute);
        
        // Work duration (6-10 hours with variations)
        $workHours = $this->faker->randomFloat(1, 6.0, 10.0);
        $workMinutes = (int)($workHours * 60);
        
        // Check-out time
        $checkOutTime = $checkInTime->copy()->addMinutes($workMinutes);
        
        // Location coordinates with slight variations for realism
        $checkInLat = $workLocation ? (float)$workLocation->latitude + $this->faker->randomFloat(6, -0.0005, 0.0005) : null;
        $checkInLng = $workLocation ? (float)$workLocation->longitude + $this->faker->randomFloat(6, -0.0005, 0.0005) : null;
        $checkOutLat = $workLocation ? (float)$workLocation->latitude + $this->faker->randomFloat(6, -0.0005, 0.0005) : null;
        $checkOutLng = $workLocation ? (float)$workLocation->longitude + $this->faker->randomFloat(6, -0.0005, 0.0005) : null;
        
        // Calculate distance from work location
        $checkInDistance = $workLocation ? $this->faker->randomFloat(1, 5, 50) : null;
        $checkOutDistance = $workLocation ? $this->faker->randomFloat(1, 5, 50) : null;
        
        // GPS accuracy
        $checkInAccuracy = $this->faker->randomFloat(1, 3, 25);
        $checkOutAccuracy = $this->faker->randomFloat(1, 3, 25);
        
        // Determine if location is valid (within geofence)
        $checkInValid = $workLocation ? ($checkInDistance <= $workLocation->radius_meters) : false;
        $checkOutValid = $workLocation ? ($checkOutDistance <= $workLocation->radius_meters) : false;
        
        // Status - 90% complete, 10% incomplete (only check-in)
        $isComplete = $this->faker->boolean(90);
        $status = $isComplete ? 'checked_out' : 'checked_in';
        
        return [
            'user_id' => User::factory(),
            'work_location_id' => $workLocation?->id,
            'attendance_date' => $carbonDate->toDateString(),
            
            // Check-in data
            'check_in_time' => $checkInTime,
            'check_in_latitude' => $checkInLat,
            'check_in_longitude' => $checkInLng,
            'check_in_accuracy' => $checkInAccuracy,
            'check_in_address' => $this->faker->address() . ', Malang, Jawa Timur',
            'check_in_distance' => $checkInDistance,
            'check_in_valid_location' => $checkInValid,
            
            // Check-out data (only if complete)
            'check_out_time' => $isComplete ? $checkOutTime : null,
            'check_out_latitude' => $isComplete ? $checkOutLat : null,
            'check_out_longitude' => $isComplete ? $checkOutLng : null,
            'check_out_accuracy' => $isComplete ? $checkOutAccuracy : null,
            'check_out_address' => $isComplete ? ($this->faker->address() . ', Malang, Jawa Timur') : null,
            'check_out_distance' => $isComplete ? $checkOutDistance : null,
            'check_out_valid_location' => $isComplete ? $checkOutValid : false,
            
            // Work duration
            'total_work_minutes' => $isComplete ? $workMinutes : null,
            'status' => $status,
            
            // Notes (sometimes)
            'notes' => $this->faker->optional(0.3)->sentence(),
            
            // Device information
            'device_info' => json_encode([
                'platform' => $this->faker->randomElement(['Android', 'iOS', 'Web']),
                'version' => $this->faker->randomElement(['10.0', '11.0', '12.0', '13.0', '14.0']),
                'model' => $this->faker->randomElement(['Samsung Galaxy', 'iPhone', 'Xiaomi', 'OPPO', 'Vivo']),
            ]),
            'browser_info' => $this->faker->userAgent(),
            'ip_address' => $this->faker->ipv4(),
            
            // GPS metadata
            'gps_metadata' => [
                'provider' => $this->faker->randomElement(['gps', 'network', 'passive']),
                'satellites' => $this->faker->numberBetween(4, 12),
                'speed' => $this->faker->randomFloat(1, 0, 5),
                'bearing' => $this->faker->randomFloat(1, 0, 360),
                'altitude' => $this->faker->randomFloat(1, 400, 600),
            ],
            
            // Spoofing detection (rarely positive)
            'suspected_spoofing' => $this->faker->boolean(5), // 5% chance of suspected spoofing
            
            // Approval status
            'approval_status' => $this->faker->randomElement([
                'pending' => 20,    // 20% pending
                'approved' => 70,   // 70% approved  
                'rejected' => 10,   // 10% rejected
            ]),
            'approved_by' => null, // Will be set by seeder if approved/rejected
            'approved_at' => null, // Will be set by seeder if approved/rejected
            'approval_notes' => null, // Will be set by seeder if needed
        ];
    }

    /**
     * Create punctual attendance (early arrival)
     */
    public function punctual(): static
    {
        return $this->state(function (array $attributes) {
            $attendanceDate = Carbon::parse($attributes['attendance_date'] ?? now());
            $checkInTime = $attendanceDate->copy()->setTime(7, rand(0, 30)); // 7:00-7:30 AM
            $workMinutes = rand(480, 540); // 8-9 hours
            $checkOutTime = $checkInTime->copy()->addMinutes($workMinutes);
            
            return [
                'check_in_time' => $checkInTime,
                'check_out_time' => $checkOutTime,
                'total_work_minutes' => $workMinutes,
                'status' => 'checked_out',
                'approval_status' => 'approved',
            ];
        });
    }

    /**
     * Create late attendance
     */
    public function late(): static
    {
        return $this->state(function (array $attributes) {
            $attendanceDate = Carbon::parse($attributes['attendance_date'] ?? now());
            $checkInTime = $attendanceDate->copy()->setTime(9, rand(15, 59)); // 9:15-9:59 AM (late)
            $workMinutes = rand(420, 480); // 7-8 hours (shorter due to late start)
            $checkOutTime = $checkInTime->copy()->addMinutes($workMinutes);
            
            return [
                'check_in_time' => $checkInTime,
                'check_out_time' => $checkOutTime,
                'total_work_minutes' => $workMinutes,
                'status' => 'checked_out',
                'approval_status' => 'pending',
                'notes' => 'Terlambat masuk kerja',
            ];
        });
    }

    /**
     * Create overtime attendance
     */
    public function overtime(): static
    {
        return $this->state(function (array $attributes) {
            $attendanceDate = Carbon::parse($attributes['attendance_date'] ?? now());
            $checkInTime = $attendanceDate->copy()->setTime(8, rand(0, 30)); // Normal start
            $workMinutes = rand(600, 720); // 10-12 hours (overtime)
            $checkOutTime = $checkInTime->copy()->addMinutes($workMinutes);
            
            return [
                'check_in_time' => $checkInTime,
                'check_out_time' => $checkOutTime,
                'total_work_minutes' => $workMinutes,
                'status' => 'checked_out',
                'approval_status' => 'approved',
                'notes' => 'Lembur',
            ];
        });
    }

    /**
     * Create incomplete attendance (only check-in)
     */
    public function incomplete(): static
    {
        return $this->state(function (array $attributes) {
            $attendanceDate = Carbon::parse($attributes['attendance_date'] ?? now());
            $checkInTime = $attendanceDate->copy()->setTime(8, rand(0, 30));
            
            return [
                'check_in_time' => $checkInTime,
                'check_out_time' => null,
                'check_out_latitude' => null,
                'check_out_longitude' => null,
                'check_out_accuracy' => null,
                'check_out_address' => null,
                'check_out_distance' => null,
                'check_out_valid_location' => false,
                'total_work_minutes' => null,
                'status' => 'checked_in',
                'approval_status' => 'pending',
                'notes' => 'Belum check-out',
            ];
        });
    }

    /**
     * Create approved attendance
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'approval_status' => 'approved',
            'approved_at' => $this->faker->dateTimeBetween($attributes['check_out_time'] ?? $attributes['check_in_time'], 'now'),
        ]);
    }

    /**
     * Create rejected attendance
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'approval_status' => 'rejected',
            'approved_at' => $this->faker->dateTimeBetween($attributes['check_out_time'] ?? $attributes['check_in_time'], 'now'),
            'approval_notes' => $this->faker->randomElement([
                'Lokasi tidak valid',
                'Waktu tidak sesuai',
                'GPS tidak akurat',
                'Foto tidak jelas',
                'Data tidak lengkap'
            ]),
        ]);
    }

    /**
     * Create pending approval
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'approval_status' => 'pending',
            'approved_by' => null,
            'approved_at' => null,
            'approval_notes' => null,
        ]);
    }
}