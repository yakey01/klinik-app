<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Dokter>
 */
class DokterFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $jabatanDokter = ['Dokter Umum', 'Dokter Gigi', 'Dokter Spesialis'];
        $spesialisasi = [
            'Umum', 'Gigi', 'Mata', 'THT', 'Kulit', 'Jantung', 
            'Paru', 'Syaraf', 'Kandungan', 'Anak'
        ];

        return [
            'nik' => $this->faker->unique()->numerify('##########'),
            'nama_lengkap' => 'Dr. ' . $this->faker->name(),
            'tanggal_lahir' => $this->faker->dateTimeBetween('-60 years', '-25 years'),
            'jenis_kelamin' => $this->faker->randomElement(['Laki-laki', 'Perempuan']),
            'jabatan' => $this->faker->randomElement($jabatanDokter),
            'nomor_sip' => $this->faker->unique()->numerify('SIP########'),
            'email' => $this->faker->unique()->safeEmail(),
            'aktif' => $this->faker->boolean(90),
            'spesialisasi' => $this->faker->randomElement($spesialisasi),
            'alamat' => $this->faker->address(),
            'no_telepon' => $this->faker->phoneNumber(),
            'tanggal_bergabung' => $this->faker->dateTimeBetween('-5 years', 'now'),
            'foto' => null,
            'keterangan' => $this->faker->optional()->sentence(),
            'input_by' => 1,
            'username' => null,
            'password' => null,
            'status_akun' => 'aktif',
            'password_changed_at' => null,
            'last_login_at' => null,
            'password_reset_by' => null,
        ];
    }

    /**
     * Indicate that the dokter is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'aktif' => false,
            'status_akun' => 'nonaktif',
        ]);
    }

    /**
     * Indicate that the dokter is a general practitioner.
     */
    public function general(): static
    {
        return $this->state(fn (array $attributes) => [
            'jabatan' => 'Dokter Umum',
            'spesialisasi' => 'Umum',
        ]);
    }

    /**
     * Indicate that the dokter is a dentist.
     */
    public function dentist(): static
    {
        return $this->state(fn (array $attributes) => [
            'jabatan' => 'Dokter Gigi',
            'spesialisasi' => 'Gigi',
        ]);
    }
}