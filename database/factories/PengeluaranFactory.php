<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Pengeluaran>
 */
class PengeluaranFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'kode_pengeluaran' => $this->faker->unique()->numerify('KPL###'),
            'nama_pengeluaran' => $this->faker->words(3, true),
            'tanggal' => $this->faker->date(),
            'keterangan' => $this->faker->sentence(),
            'nominal' => $this->faker->numberBetween(10000, 1000000),
            'kategori' => $this->faker->randomElement(['Operasional', 'Medis', 'Administratif', 'Maintenance', 'Lainnya']),
            'bukti_transaksi' => null,
            'input_by' => 1, // Will be overridden in tests with actual user
            'status_validasi' => $this->faker->randomElement(['pending', 'approved', 'rejected']),
            'validasi_by' => null,
            'validasi_at' => null,
            'catatan_validasi' => null,
        ];
    }

    /**
     * Indicate that the pengeluaran is pending validation.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status_validasi' => 'pending',
            'validasi_by' => null,
            'validasi_at' => null,
            'catatan_validasi' => null,
        ]);
    }

    /**
     * Indicate that the pengeluaran is approved.
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

    /**
     * Indicate that the pengeluaran is rejected.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status_validasi' => 'rejected',
            'validasi_by' => 1,
            'validasi_at' => now(),
            'catatan_validasi' => 'Rejected due to invalid documentation',
        ]);
    }
}
