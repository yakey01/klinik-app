<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Pasien>
 */
class PasienFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'no_rekam_medis' => 'RM' . str_pad(fake()->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'nama' => fake()->name(),
            'tanggal_lahir' => fake()->dateTimeBetween('-80 years', '-18 years')->format('Y-m-d'),
            'jenis_kelamin' => fake()->randomElement(['L', 'P']),
            'alamat' => fake()->address(),
            'no_telepon' => fake()->phoneNumber(),
            'email' => fake()->unique()->safeEmail(),
            'pekerjaan' => fake()->jobTitle(),
            'status_pernikahan' => fake()->randomElement(['belum_menikah', 'menikah', 'janda', 'duda']),
            'kontak_darurat_nama' => fake()->name(),
            'kontak_darurat_telepon' => fake()->phoneNumber(),
        ];
    }
}
