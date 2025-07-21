<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\JenisTindakan>
 */
class JenisTindakanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'kode' => 'TND-' . str_pad(fake()->unique()->numberBetween(1, 999), 3, '0', STR_PAD_LEFT),
            'nama' => fake()->words(3, true),
            'deskripsi' => fake()->sentence(),
            'tarif' => fake()->numberBetween(50000, 1000000),
            'jasa_dokter' => fake()->numberBetween(20000, 300000),
            'jasa_paramedis' => fake()->numberBetween(10000, 150000),
            'jasa_non_paramedis' => fake()->numberBetween(5000, 50000),
            'kategori' => fake()->randomElement(['konsultasi', 'pemeriksaan', 'tindakan', 'operasi']),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the jenis tindakan is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the jenis tindakan is for consultation.
     */
    public function consultation(): static
    {
        return $this->state(fn (array $attributes) => [
            'kategori' => 'konsultasi',
            'tarif' => fake()->numberBetween(50000, 200000),
        ]);
    }

    /**
     * Indicate that the jenis tindakan is for surgery.
     */
    public function surgery(): static
    {
        return $this->state(fn (array $attributes) => [
            'kategori' => 'operasi',
            'tarif' => fake()->numberBetween(500000, 5000000),
            'jasa_dokter' => fake()->numberBetween(200000, 1500000),
        ]);
    }
}