<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Pegawai>
 */
class PegawaiFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $jenisPegawai = $this->faker->randomElement(['Paramedis', 'Non-Paramedis']);

        $jabatanParamedis = ['Perawat', 'Bidan', 'Analis Lab', 'Radiografer', 'Fisioterapis', 'Apoteker'];
        $jabatanNonParamedis = ['Kasir', 'Administrasi', 'IT Support', 'Security', 'Cleaning Service', 'Driver'];

        return [
            'nik' => $this->faker->unique()->numerify('##########'),
            'nama_lengkap' => $this->faker->name(),
            'tanggal_lahir' => $this->faker->dateTimeBetween('-60 years', '-20 years'),
            'jenis_kelamin' => $this->faker->randomElement(['Laki-laki', 'Perempuan']),
            'jabatan' => $jenisPegawai === 'Paramedis'
                ? $this->faker->randomElement($jabatanParamedis)
                : $this->faker->randomElement($jabatanNonParamedis),
            'jenis_pegawai' => $jenisPegawai,
            'aktif' => $this->faker->boolean(85),
            'input_by' => 1,
        ];
    }
}
