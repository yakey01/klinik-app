<?php

namespace Database\Factories;

use App\Models\JenisTindakan;
use App\Models\Pasien;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tindakan>
 */
class TindakanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'pasien_id' => Pasien::factory(),
            'jenis_tindakan_id' => JenisTindakan::factory(),
            'dokter_id' => User::factory(),
            'paramedis_id' => User::factory(),
            'non_paramedis_id' => User::factory(),
            'shift_id' => null,
            'tanggal_tindakan' => fake()->dateTimeBetween('-30 days', 'now'),
            'tarif' => fake()->numberBetween(50000, 1000000),
            'jasa_dokter' => fake()->numberBetween(20000, 300000),
            'jasa_paramedis' => fake()->numberBetween(10000, 150000),
            'jasa_non_paramedis' => fake()->numberBetween(5000, 50000),
            'catatan' => fake()->optional()->sentence(),
            'status' => fake()->randomElement(['pending', 'selesai', 'batal']),
            'input_by' => User::factory(),
        ];
    }
}
