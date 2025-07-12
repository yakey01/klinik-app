<?php

namespace Database\Factories;

use App\Models\LocationValidation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LocationValidation>
 */
class LocationValidationFactory extends Factory
{
    protected $model = LocationValidation::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Office coordinates (example: Jakarta coordinates)
        $officeLat = -6.2088;
        $officeLon = 106.8456;
        
        // Generate random location within or outside work zone
        $isWithinZone = $this->faker->boolean(70); // 70% chance to be within zone
        $radius = $this->faker->numberBetween(50, 200); // Work zone radius in meters
        
        if ($isWithinZone) {
            // Generate location within work zone
            $distance = $this->faker->numberBetween(0, $radius - 10);
            $bearing = $this->faker->numberBetween(0, 360);
            
            $lat = $officeLat + ($distance / 111000) * cos(deg2rad($bearing));
            $lon = $officeLon + ($distance / (111000 * cos(deg2rad($officeLat)))) * sin(deg2rad($bearing));
            $distanceFromZone = 0;
        } else {
            // Generate location outside work zone
            $distance = $this->faker->numberBetween($radius + 10, $radius + 1000);
            $bearing = $this->faker->numberBetween(0, 360);
            
            $lat = $officeLat + ($distance / 111000) * cos(deg2rad($bearing));
            $lon = $officeLon + ($distance / (111000 * cos(deg2rad($officeLat)))) * sin(deg2rad($bearing));
            $distanceFromZone = $distance - $radius;
        }

        return [
            'user_id' => User::factory(),
            'latitude' => round($lat, 8),
            'longitude' => round($lon, 8),
            'accuracy' => $this->faker->numberBetween(3, 20),
            'work_zone_radius' => $radius,
            'is_within_zone' => $isWithinZone,
            'distance_from_zone' => $isWithinZone ? 0 : round($distanceFromZone, 2),
            'validation_time' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'attendance_type' => $this->faker->randomElement(['check_in', 'check_out']),
            'notes' => $this->faker->optional(0.3)->sentence(),
        ];
    }

    /**
     * Indicate that the validation is within the work zone.
     */
    public function withinZone(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_within_zone' => true,
            'distance_from_zone' => 0,
        ]);
    }

    /**
     * Indicate that the validation is outside the work zone.
     */
    public function outsideZone(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_within_zone' => false,
            'distance_from_zone' => $this->faker->numberBetween(10, 500),
        ]);
    }

    /**
     * Indicate that this is a check-in validation.
     */
    public function checkIn(): static
    {
        return $this->state(fn (array $attributes) => [
            'attendance_type' => 'check_in',
        ]);
    }

    /**
     * Indicate that this is a check-out validation.
     */
    public function checkOut(): static
    {
        return $this->state(fn (array $attributes) => [
            'attendance_type' => 'check_out',
        ]);
    }

    /**
     * Indicate that this validation happened today.
     */
    public function today(): static
    {
        return $this->state(fn (array $attributes) => [
            'validation_time' => now()->subHours($this->faker->numberBetween(1, 10)),
        ]);
    }
}
