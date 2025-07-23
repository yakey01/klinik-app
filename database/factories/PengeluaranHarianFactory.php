<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PengeluaranHarian>
 */
class PengeluaranHarianFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tanggal_input' => $this->faker->date(),
            'shift' => $this->faker->randomElement(['Pagi', 'Sore', 'Malam']),
            'pengeluaran_id' => 1, // Will be overridden in tests with actual pengeluaran
            'nominal' => $this->faker->numberBetween(5000, 500000),
            'deskripsi' => $this->faker->sentence(),
            'user_id' => 1, // Will be overridden in tests with actual user
            'status_validasi' => $this->faker->randomElement(['pending', 'approved', 'rejected']),
            'validasi_by' => null,
            'validasi_at' => null,
            'catatan_validasi' => null,
        ];
    }

    /**
     * Indicate that the pengeluaran harian is for morning shift.
     */
    public function morning(): static
    {
        return $this->state(fn (array $attributes) => [
            'shift' => 'Pagi',
        ]);
    }

    /**
     * Indicate that the pengeluaran harian is for evening shift.
     */
    public function evening(): static
    {
        return $this->state(fn (array $attributes) => [
            'shift' => 'Sore',
        ]);
    }

    /**
     * Indicate that the pengeluaran harian is approved.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status_validasi' => 'approved',
            'validasi_by' => 1,
            'validasi_at' => now(),
            'catatan_validasi' => 'Approved by system',
        ]);
    }
}
