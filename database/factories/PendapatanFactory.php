<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Pendapatan>
 */
class PendapatanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'kode_pendapatan' => 'PND-' . str_pad(fake()->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'nama_pendapatan' => fake()->words(3, true),
            'sumber_pendapatan' => fake()->randomElement(['tindakan', 'administrasi', 'obat', 'konsultasi']),
            'is_aktif' => true,
            'tanggal' => fake()->date(),
            'keterangan' => fake()->sentence(),
            'nominal' => fake()->numberBetween(50000, 500000),
            'kategori' => fake()->randomElement(['medis', 'non_medis', 'administrasi']),
            'tindakan_id' => null,
            'input_by' => User::factory(),
            'status_validasi' => fake()->randomElement(['pending', 'approved', 'rejected']),
            'validasi_by' => null,
            'validasi_at' => null,
        ];
    }

    /**
     * Indicate that the pendapatan is validated.
     */
    public function validated(): static
    {
        return $this->state(fn (array $attributes) => [
            'status_validasi' => 'approved',
            'validasi_by' => User::factory(),
            'validasi_at' => fake()->dateTime(),
        ]);
    }

    /**
     * Indicate that the pendapatan is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_aktif' => false,
        ]);
    }
}