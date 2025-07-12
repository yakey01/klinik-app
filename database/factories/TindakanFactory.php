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
        $jenisTindakan = JenisTindakan::inRandomOrder()->first();
        
        return [
            'pasien_id' => Pasien::factory(),
            'jenis_tindakan_id' => $jenisTindakan->id,
            'dokter_id' => User::whereHas('role', fn($q) => $q->where('name', 'dokter'))->inRandomOrder()->first()?->id,
            'paramedis_id' => User::whereHas('role', fn($q) => $q->where('name', 'paramedis'))->inRandomOrder()->first()?->id,
            'non_paramedis_id' => User::whereHas('role', fn($q) => $q->where('name', 'non_paramedis'))->inRandomOrder()->first()?->id,
            'shift_id' => Shift::inRandomOrder()->first()?->id,
            'tanggal_tindakan' => fake()->dateTimeBetween('-30 days', 'now'),
            'tarif' => $jenisTindakan->tarif,
            'jasa_dokter' => $jenisTindakan->jasa_dokter,
            'jasa_paramedis' => $jenisTindakan->jasa_paramedis,
            'jasa_non_paramedis' => $jenisTindakan->jasa_non_paramedis,
            'catatan' => fake()->optional()->sentence(),
            'status' => fake()->randomElement(['pending', 'selesai', 'batal']),
        ];
    }
}
