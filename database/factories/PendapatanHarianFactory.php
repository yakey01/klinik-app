<?php

namespace Database\Factories;

use App\Models\PendapatanHarian;
use App\Models\Pendapatan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PendapatanHarianFactory extends Factory
{
    protected $model = PendapatanHarian::class;

    public function definition(): array
    {
        return [
            'tanggal_input' => $this->faker->date(),
            'shift' => $this->faker->randomElement(['Pagi', 'Sore', 'Malam']),
            'pendapatan_id' => Pendapatan::factory(),
            'nominal' => $this->faker->numberBetween(50000, 500000),
            'deskripsi' => $this->faker->sentence(),
            'user_id' => User::factory(),
            'status_validasi' => $this->faker->randomElement(['pending', 'approved', 'rejected']),
            'validasi_by' => null,
            'validasi_at' => null,
            'catatan_validasi' => null,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status_validasi' => 'approved',
            'validasi_by' => User::factory(),
            'validasi_at' => now(),
            'catatan_validasi' => 'Disetujui',
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status_validasi' => 'pending',
            'validasi_by' => null,
            'validasi_at' => null,
            'catatan_validasi' => null,
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status_validasi' => 'rejected',
            'validasi_by' => User::factory(),
            'validasi_at' => now(),
            'catatan_validasi' => 'Ditolak',
        ]);
    }
}