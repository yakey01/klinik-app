<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Shift>
 */
class ShiftFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $shifts = [
            ['name' => 'Pagi', 'start_time' => '07:00:00', 'end_time' => '15:00:00'],
            ['name' => 'Sore', 'start_time' => '15:00:00', 'end_time' => '23:00:00'],
            ['name' => 'Malam', 'start_time' => '23:00:00', 'end_time' => '07:00:00'],
        ];
        
        $shift = $this->faker->randomElement($shifts);
        
        return [
            'name' => $shift['name'],
            'start_time' => $shift['start_time'],
            'end_time' => $shift['end_time'],
            'description' => 'Shift ' . $shift['name'] . ' (' . $shift['start_time'] . ' - ' . $shift['end_time'] . ')',
            'is_active' => $this->faker->boolean(90), // 90% chance of being active
        ];
    }

    /**
     * Indicate that the shift is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the shift is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
