<?php

namespace Database\Factories;

use App\Models\DiParamedis;
use App\Models\Pegawai;
use App\Models\User;
use App\Models\JadwalJaga;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DiParamedis>
 */
class DiParamedisFactory extends Factory
{
    protected $model = DiParamedis::class;

    public function definition(): array
    {
        $tanggal = $this->faker->dateTimeBetween('-30 days', 'now');
        $jamMulai = $this->faker->time('H:i:s');
        $jamSelesai = Carbon::parse($jamMulai)->addHours($this->faker->numberBetween(6, 12))->format('H:i:s');
        
        return [
            'pegawai_id' => Pegawai::where('jenis_pegawai', 'Paramedis')->inRandomOrder()->first()?->id ?? Pegawai::factory()->create(['jenis_pegawai' => 'Paramedis'])->id,
            'user_id' => User::whereHas('role', fn($q) => $q->where('name', 'paramedis'))->inRandomOrder()->first()?->id ?? User::factory()->create()->id,
            'jadwal_jaga_id' => null, // Jadwal jaga is optional
            'tanggal' => $tanggal,
            'jam_mulai' => $jamMulai,
            'jam_selesai' => $this->faker->boolean(80) ? $jamSelesai : null,
            'shift' => $this->faker->randomElement(['Pagi', 'Siang', 'Malam']),
            'lokasi_tugas' => $this->faker->randomElement(['IGD', 'Rawat Inap', 'Rawat Jalan', 'ICU', 'OK', 'Laboratorium']),
            
            // Patient care activities
            'jumlah_pasien_dilayani' => $this->faker->numberBetween(5, 30),
            'jumlah_tindakan_medis' => $this->faker->numberBetween(3, 20),
            'jumlah_observasi_pasien' => $this->faker->numberBetween(10, 50),
            'jumlah_kasus_emergency' => $this->faker->numberBetween(0, 5),
            
            // Medical procedures
            'tindakan_medis' => $this->generateTindakanMedis(),
            'obat_diberikan' => $this->generateObatDiberikan(),
            'alat_medis_digunakan' => $this->generateAlatMedis(),
            
            // Documentation
            'catatan_kasus_emergency' => $this->faker->boolean(30) ? $this->faker->paragraph() : null,
            'laporan_kegiatan' => $this->faker->paragraphs(2, true),
            'kendala_hambatan' => $this->faker->boolean(40) ? $this->faker->sentence() : null,
            'saran_perbaikan' => $this->faker->boolean(30) ? $this->faker->sentence() : null,
            
            // Status
            'status' => $this->faker->randomElement(['draft', 'submitted', 'approved', 'rejected']),
        ];
    }

    /**
     * Indicate that the DI is in draft status.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
            'approved_by' => null,
            'approved_at' => null,
            'rejection_reason' => null,
        ]);
    }

    /**
     * Indicate that the DI is submitted for approval.
     */
    public function submitted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'submitted',
            'jam_selesai' => Carbon::parse($attributes['jam_mulai'])->addHours(8)->format('H:i:s'),
            'approved_by' => null,
            'approved_at' => null,
            'rejection_reason' => null,
        ]);
    }

    /**
     * Indicate that the DI is approved.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'jam_selesai' => Carbon::parse($attributes['jam_mulai'])->addHours(8)->format('H:i:s'),
            'approved_by' => User::whereHas('role', fn($q) => $q->whereIn('name', ['admin', 'manajer']))->inRandomOrder()->first()?->id,
            'approved_at' => $this->faker->dateTimeBetween($attributes['tanggal'], 'now'),
            'rejection_reason' => null,
        ]);
    }

    /**
     * Indicate that the DI is rejected.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
            'approved_by' => User::whereHas('role', fn($q) => $q->whereIn('name', ['admin', 'manajer']))->inRandomOrder()->first()?->id,
            'approved_at' => $this->faker->dateTimeBetween($attributes['tanggal'], 'now'),
            'rejection_reason' => $this->faker->randomElement([
                'Data tidak lengkap',
                'Laporan kegiatan tidak sesuai',
                'Jumlah tindakan tidak sesuai dengan catatan sistem',
                'Perlu verifikasi lebih lanjut',
            ]),
        ]);
    }

    /**
     * Generate random medical procedures
     */
    private function generateTindakanMedis(): array
    {
        $tindakan = [
            'Injeksi IM/IV',
            'Pemasangan Infus',
            'Pengambilan Darah',
            'EKG',
            'Nebulizer',
            'Wound Care',
            'Catheter Insertion',
            'NGT Insertion',
            'Suction',
            'Oxygen Therapy',
        ];

        $count = $this->faker->numberBetween(1, 5);
        $result = [];

        for ($i = 0; $i < $count; $i++) {
            $result[] = [
                'nama_tindakan' => $this->faker->randomElement($tindakan),
                'jumlah' => $this->faker->numberBetween(1, 5),
                'keterangan' => $this->faker->boolean(50) ? $this->faker->sentence() : null,
                'timestamp' => now()->subHours($this->faker->numberBetween(1, 8))->toIso8601String(),
            ];
        }

        return $result;
    }

    /**
     * Generate random medications given
     */
    private function generateObatDiberikan(): array
    {
        $obat = [
            ['nama' => 'Paracetamol', 'dosis' => '500mg', 'cara' => 'Oral'],
            ['nama' => 'Amoxicillin', 'dosis' => '500mg', 'cara' => 'Oral'],
            ['nama' => 'Omeprazole', 'dosis' => '20mg', 'cara' => 'Oral'],
            ['nama' => 'Dexamethasone', 'dosis' => '0.5mg', 'cara' => 'IV'],
            ['nama' => 'Ketorolac', 'dosis' => '30mg', 'cara' => 'IM'],
            ['nama' => 'Ranitidine', 'dosis' => '50mg', 'cara' => 'IV'],
        ];

        $count = $this->faker->numberBetween(0, 4);
        $result = [];

        for ($i = 0; $i < $count; $i++) {
            $selectedObat = $this->faker->randomElement($obat);
            $result[] = [
                'nama_obat' => $selectedObat['nama'],
                'dosis' => $selectedObat['dosis'],
                'jumlah' => $this->faker->numberBetween(1, 3),
                'cara_pemberian' => $selectedObat['cara'],
                'timestamp' => now()->subHours($this->faker->numberBetween(1, 8))->toIso8601String(),
            ];
        }

        return $result;
    }

    /**
     * Generate random medical equipment used
     */
    private function generateAlatMedis(): array
    {
        $alat = [
            'Syringe 3cc',
            'Syringe 5cc',
            'IV Catheter',
            'Infusion Set',
            'Blood Collection Tube',
            'Gauze',
            'Plaster',
            'Gloves',
            'Mask',
            'Oxygen Mask',
        ];

        $count = $this->faker->numberBetween(1, 5);
        $result = [];

        for ($i = 0; $i < $count; $i++) {
            $result[] = [
                'nama_alat' => $this->faker->randomElement($alat),
                'jumlah' => $this->faker->numberBetween(1, 10),
                'kondisi' => $this->faker->randomElement(['Baik', 'Cukup', 'Perlu diganti']),
                'timestamp' => now()->subHours($this->faker->numberBetween(1, 8))->toIso8601String(),
            ];
        }

        return $result;
    }
}