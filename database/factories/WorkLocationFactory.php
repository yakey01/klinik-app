<?php

namespace Database\Factories;

use App\Models\WorkLocation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WorkLocation>
 */
class WorkLocationFactory extends Factory
{
    protected $model = WorkLocation::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Malang, Indonesia area coordinates
        $malangLatitudes = [-7.9666, -7.9755, -7.9833, -7.9944, -8.0055];
        $malangLongitudes = [112.6326, 112.6276, 112.6426, 112.6176, 112.6476];
        
        $latitude = $this->faker->randomElement($malangLatitudes) + $this->faker->randomFloat(6, -0.01, 0.01);
        $longitude = $this->faker->randomElement($malangLongitudes) + $this->faker->randomFloat(6, -0.01, 0.01);

        return [
            'name' => $this->faker->randomElement([
                'Kantor Pusat Klinik Dokterku',
                'Klinik Dokterku Cabang Malang Kota',
                'Klinik Dokterku Cabang Batu',
                'Klinik Dokterku Cabang Blimbing',
                'Klinik Dokterku Cabang Klojen',
                'Klinik Dokterku Cabang Lowokwaru',
                'Klinik Dokterku Cabang Sukun',
                'Apotek Dokterku',
                'Laboratorium Klinik Dokterku',
                'Pusat Radiologi Dokterku'
            ]),
            'description' => $this->faker->sentence(10),
            'address' => $this->faker->address() . ', Malang, Jawa Timur',
            'latitude' => $latitude,
            'longitude' => $longitude,
            'radius_meters' => $this->faker->randomElement([50, 75, 100, 150, 200]),
            'is_active' => $this->faker->boolean(90), // 90% chance of being active
            'location_type' => $this->faker->randomElement([
                'main_office', 'branch_office', 'project_site', 'mobile_location', 'client_office'
            ]),
            'allowed_shifts' => $this->faker->randomElement([
                ['pagi', 'siang', 'malam'],
                ['pagi', 'siang'],
                ['siang', 'malam'],
                ['pagi'],
                null // All shifts allowed
            ]),
            'working_hours' => $this->faker->randomElement([
                [
                    'monday' => ['start' => '08:00', 'end' => '17:00'],
                    'tuesday' => ['start' => '08:00', 'end' => '17:00'],
                    'wednesday' => ['start' => '08:00', 'end' => '17:00'],
                    'thursday' => ['start' => '08:00', 'end' => '17:00'],
                    'friday' => ['start' => '08:00', 'end' => '17:00'],
                    'saturday' => ['start' => '08:00', 'end' => '14:00'],
                ],
                [
                    'monday' => ['start' => '07:00', 'end' => '16:00'],
                    'tuesday' => ['start' => '07:00', 'end' => '16:00'],
                    'wednesday' => ['start' => '07:00', 'end' => '16:00'],
                    'thursday' => ['start' => '07:00', 'end' => '16:00'],
                    'friday' => ['start' => '07:00', 'end' => '16:00'],
                    'saturday' => ['start' => '07:00', 'end' => '12:00'],
                ],
                null // Default working hours
            ]),
            'tolerance_settings' => [
                'late_tolerance_minutes' => $this->faker->randomElement([10, 15, 30]),
                'early_departure_tolerance_minutes' => $this->faker->randomElement([10, 15, 30]),
                'break_time_minutes' => $this->faker->randomElement([30, 60, 90]),
                'overtime_threshold_minutes' => $this->faker->randomElement([480, 540, 600]), // 8-10 hours
            ],
            'contact_person' => $this->faker->name(),
            'contact_phone' => $this->faker->phoneNumber(),
            'require_photo' => $this->faker->boolean(80), // 80% require photo
            'strict_geofence' => $this->faker->boolean(70), // 70% strict
            'gps_accuracy_required' => $this->faker->randomElement([10, 15, 20, 25, 30]),
        ];
    }

    /**
     * Main office configuration
     */
    public function mainOffice(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Kantor Pusat Klinik Dokterku',
            'location_type' => 'main_office',
            'latitude' => -7.9666,
            'longitude' => 112.6326,
            'address' => 'Jl. Veteran No. 123, Ketawanggede, Kec. Lowokwaru, Kota Malang, Jawa Timur',
            'radius_meters' => 100,
            'is_active' => true,
            'require_photo' => true,
            'strict_geofence' => true,
            'gps_accuracy_required' => 20,
        ]);
    }

    /**
     * Branch office configuration
     */
    public function branchOffice(): static
    {
        return $this->state(fn (array $attributes) => [
            'location_type' => 'branch_office',
            'radius_meters' => 75,
            'require_photo' => true,
            'strict_geofence' => true,
        ]);
    }

    /**
     * Mobile location configuration
     */
    public function mobileLocation(): static
    {
        return $this->state(fn (array $attributes) => [
            'location_type' => 'mobile_location',
            'radius_meters' => 200,
            'require_photo' => false,
            'strict_geofence' => false,
            'gps_accuracy_required' => 30,
        ]);
    }

    /**
     * Active location
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Inactive location
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}