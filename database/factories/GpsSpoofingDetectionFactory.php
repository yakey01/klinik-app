<?php

namespace Database\Factories;

use App\Models\GpsSpoofingDetection;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GpsSpoofingDetection>
 */
class GpsSpoofingDetectionFactory extends Factory
{
    protected $model = GpsSpoofingDetection::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $isSpoofed = $this->faker->boolean(30); // 30% chance of being spoofed
        $riskLevels = ['low', 'medium', 'high', 'critical'];
        $riskLevel = $isSpoofed 
            ? $this->faker->randomElement(['high', 'critical']) 
            : $this->faker->randomElement(['low', 'medium']);

        $fakeApps = [
            'Fake GPS Location',
            'GPS Joystick',
            'Mock Locations',
            'Location Spoofer',
            'GPS Emulator'
        ];

        return [
            'user_id' => User::factory(),
            'device_id' => $this->faker->uuid(),
            'ip_address' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
            
            // Location Data
            'latitude' => $this->faker->latitude(-90, 90),
            'longitude' => $this->faker->longitude(-180, 180),
            'accuracy' => $this->faker->numberBetween(3, 50),
            'altitude' => $this->faker->optional(0.7)->numberBetween(0, 500),
            'speed' => $this->faker->optional(0.5)->numberBetween(0, 100),
            'heading' => $this->faker->optional(0.5)->numberBetween(0, 360),
            
            // Detection Results
            'detection_results' => [
                'timestamp' => now()->toISOString(),
                'methods_checked' => ['mock_location', 'fake_apps', 'impossible_travel'],
                'total_score' => $this->faker->numberBetween(0, 100),
            ],
            'risk_level' => $riskLevel,
            'risk_score' => $isSpoofed ? $this->faker->numberBetween(70, 100) : $this->faker->numberBetween(0, 30),
            'is_spoofed' => $isSpoofed,
            'is_blocked' => $isSpoofed && $this->faker->boolean(60),
            
            // Detection Methods
            'mock_location_detected' => $isSpoofed && $this->faker->boolean(80),
            'fake_gps_app_detected' => $isSpoofed && $this->faker->boolean(70),
            'developer_mode_detected' => $isSpoofed && $this->faker->boolean(60),
            'impossible_travel_detected' => $isSpoofed && $this->faker->boolean(40),
            'coordinate_anomaly_detected' => $isSpoofed && $this->faker->boolean(30),
            'device_integrity_failed' => $isSpoofed && $this->faker->boolean(50),
            
            // Spoofing Indicators
            'spoofing_indicators' => $isSpoofed ? [
                'mock_location_enabled' => $this->faker->boolean(),
                'suspicious_accuracy' => $this->faker->boolean(),
                'rapid_location_changes' => $this->faker->boolean(),
                'unrealistic_speed' => $this->faker->boolean(),
            ] : [],
            'detected_fake_apps' => $isSpoofed 
                ? $this->faker->randomElement($fakeApps) 
                : null,
            'travel_speed_kmh' => $this->faker->optional(0.6)->numberBetween(0, 300),
            'time_diff_seconds' => $this->faker->optional(0.6)->numberBetween(1, 3600),
            'distance_from_last_km' => $this->faker->optional(0.6)->numberBetween(0, 100),
            
            // Action Taken
            'action_taken' => $isSpoofed 
                ? $this->faker->randomElement(['warning', 'blocked', 'flagged'])
                : 'none',
            'admin_notes' => $this->faker->optional(0.3)->sentence(),
            'reviewed_at' => $this->faker->optional(0.4)->dateTimeBetween('-7 days', 'now'),
            'reviewed_by' => $this->faker->optional(0.4)->numberBetween(1, 10),
            
            // Additional Context
            'attendance_type' => $this->faker->randomElement(['check_in', 'check_out']),
            'attempted_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'location_source' => $this->faker->randomElement(['gps', 'network', 'passive']),
            'device_fingerprint' => [
                'model' => $this->faker->randomElement(['SM-G950F', 'iPhone12,1', 'Pixel 5']),
                'os' => $this->faker->randomElement(['Android 11', 'iOS 15.0', 'Android 12']),
                'app_version' => $this->faker->semver(),
                'root_detected' => $this->faker->boolean(20),
            ],
        ];
    }

    /**
     * Indicate that GPS spoofing was detected.
     */
    public function spoofed(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_spoofed' => true,
            'risk_level' => $this->faker->randomElement(['high', 'critical']),
            'risk_score' => $this->faker->numberBetween(70, 100),
            'mock_location_detected' => true,
            'action_taken' => $this->faker->randomElement(['warning', 'blocked', 'flagged']),
        ]);
    }

    /**
     * Indicate that no GPS spoofing was detected.
     */
    public function clean(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_spoofed' => false,
            'risk_level' => $this->faker->randomElement(['low', 'medium']),
            'risk_score' => $this->faker->numberBetween(0, 30),
            'mock_location_detected' => false,
            'fake_gps_app_detected' => false,
            'action_taken' => 'none',
        ]);
    }

    /**
     * Indicate that this detection was blocked.
     */
    public function blocked(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_blocked' => true,
            'action_taken' => 'blocked',
            'is_spoofed' => true,
        ]);
    }

    /**
     * Indicate that this detection happened today.
     */
    public function today(): static
    {
        return $this->state(fn (array $attributes) => [
            'attempted_at' => now()->subHours($this->faker->numberBetween(1, 10)),
        ]);
    }
}
