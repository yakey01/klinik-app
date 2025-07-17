<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Tindakan;
use App\Models\Pasien;
use App\Models\Dokter;
use App\Models\User;
use App\Models\DokterUmumJaspel;
use Carbon\Carbon;

class DokterTindakanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🏥 Creating tindakan data for dokter users...');

        // Get all dokter users
        $dokterUsers = User::whereHas('role', function ($query) {
            $query->whereIn('name', ['dokter', 'dokter_gigi']);
        })->get();

        if ($dokterUsers->isEmpty()) {
            $this->command->warn('⚠️  No dokter users found. Please run DokterUserSeeder first.');
            return;
        }

        // Get all pasien
        $pasiens = Pasien::all();
        if ($pasiens->isEmpty()) {
            $this->command->warn('⚠️  No pasien found. Skipping patient creation to avoid audit log issues...');
            return;
        }

        // Get all dokter records
        $dokters = Dokter::all();
        if ($dokters->isEmpty()) {
            $this->command->warn('⚠️  No dokter records found. Please run DokterSeeder first.');
            return;
        }

        // Ensure DokterUmumJaspel rules exist
        $this->ensureJaspelRules();

        $tindakanCount = 0;
        $startDate = Carbon::now()->subMonths(2);
        $endDate = Carbon::now()->addDays(7);

        foreach ($dokters as $dokter) {
            $this->command->info("💉 Creating tindakan for: {$dokter->nama_lengkap}");
            
            // Create 30-80 tindakan per dokter over the period
            $tindakanPerDokter = rand(30, 80);
            
            for ($i = 0; $i < $tindakanPerDokter; $i++) {
                $randomDate = $this->getRandomDate($startDate, $endDate);
                $pasien = $pasiens->random();
                $jenisTindakanId = $this->getRandomJenisTindakanId($dokter->jabatan);
                $jaspel = $this->calculateJaspel($jenisTindakanId, $dokter->jabatan);
                
                Tindakan::create([
                    'pasien_id' => $pasien->id,
                    'jenis_tindakan_id' => $jenisTindakanId,
                    'dokter_id' => $dokter->id,
                    'paramedis_id' => null,
                    'non_paramedis_id' => null,
                    'shift_id' => 1,
                    'tanggal_tindakan' => $randomDate,
                    'tarif' => $jaspel['biaya_total'],
                    'jasa_dokter' => $jaspel['jasa_dokter'],
                    'jasa_paramedis' => 0,
                    'jasa_non_paramedis' => 0,
                    'input_by' => $dokter->user_id,
                    'status_validasi' => $this->getRandomStatus(),
                    'catatan' => $this->getRandomKeterangan(),
                    'created_at' => $randomDate,
                    'updated_at' => $randomDate
                ]);
                
                $tindakanCount++;
            }
        }

        $this->command->info("✅ Created {$tindakanCount} tindakan entries for dokter users");
    }

    /**
     * Create sample pasiens if none exist
     */
    private function createSamplePasiens()
    {
        $samplePasiens = [
            ['nama' => 'Budi Santoso', 'tanggal_lahir' => '1980-05-15', 'jenis_kelamin' => 'L'],
            ['nama' => 'Siti Nurhaliza', 'tanggal_lahir' => '1985-08-22', 'jenis_kelamin' => 'P'],
            ['nama' => 'Ahmad Wijaya', 'tanggal_lahir' => '1975-03-10', 'jenis_kelamin' => 'L'],
            ['nama' => 'Dewi Sartika', 'tanggal_lahir' => '1990-12-05', 'jenis_kelamin' => 'P'],
            ['nama' => 'Rudi Hermawan', 'tanggal_lahir' => '1988-07-18', 'jenis_kelamin' => 'L'],
            ['nama' => 'Lia Amalia', 'tanggal_lahir' => '1992-09-30', 'jenis_kelamin' => 'P'],
            ['nama' => 'Agus Salim', 'tanggal_lahir' => '1970-11-12', 'jenis_kelamin' => 'L'],
            ['nama' => 'Maya Sari', 'tanggal_lahir' => '1995-04-25', 'jenis_kelamin' => 'P'],
            ['nama' => 'Toni Setiawan', 'tanggal_lahir' => '1983-06-08', 'jenis_kelamin' => 'L'],
            ['nama' => 'Rina Kartini', 'tanggal_lahir' => '1987-01-20', 'jenis_kelamin' => 'P']
        ];

        foreach ($samplePasiens as $index => $pasienData) {
            Pasien::create(array_merge($pasienData, [
                'no_rekam_medis' => 'P' . str_pad($index + 1, 6, '0', STR_PAD_LEFT),
                'alamat' => 'Jl. Kesehatan No. ' . rand(1, 100),
                'no_telepon' => '08' . rand(1000000000, 9999999999),
                'email' => strtolower(str_replace(' ', '.', $pasienData['nama'])) . '@email.com',
                'pekerjaan' => 'Karyawan',
                'status_pernikahan' => 'menikah'
            ]));
        }
    }

    /**
     * Ensure jaspel rules exist
     */
    private function ensureJaspelRules()
    {
        $jaspelRules = [
            [
                'jenis_shift' => 'pagi',
                'ambang_pasien' => 10,
                'fee_pasien_umum' => 50000,
                'fee_pasien_bpjs' => 35000,
                'status_aktif' => true,
                'keterangan' => 'Tarif jaspel shift pagi'
            ],
            [
                'jenis_shift' => 'siang',
                'ambang_pasien' => 8,
                'fee_pasien_umum' => 60000,
                'fee_pasien_bpjs' => 40000,
                'status_aktif' => true,
                'keterangan' => 'Tarif jaspel shift siang'
            ],
            [
                'jenis_shift' => 'malam',
                'ambang_pasien' => 6,
                'fee_pasien_umum' => 80000,
                'fee_pasien_bpjs' => 55000,
                'status_aktif' => true,
                'keterangan' => 'Tarif jaspel shift malam'
            ]
        ];

        foreach ($jaspelRules as $rule) {
            DokterUmumJaspel::firstOrCreate(
                ['jenis_shift' => $rule['jenis_shift']],
                $rule
            );
        }
    }

    /**
     * Get random date between start and end
     */
    private function getRandomDate($startDate, $endDate)
    {
        $timestamp = rand($startDate->timestamp, $endDate->timestamp);
        return Carbon::createFromTimestamp($timestamp);
    }

    /**
     * Get random jenis tindakan based on dokter specialization
     */
    private function getRandomJenisTindakanId($jabatan)
    {
        // Return random jenis_tindakan_id from database
        $availableIds = [1, 2, 3, 4, 5, 6, 7, 8]; // Common IDs from jenis_tindakan table
        return $availableIds[array_rand($availableIds)];
    }

    /**
     * Calculate jaspel based on tindakan and jabatan
     */
    private function calculateJaspel($jenisTindakan, $jabatan)
    {
        $baseRate = match ($jabatan) {
            'dokter_gigi' => rand(75000, 150000),
            'dokter_spesialis' => rand(100000, 200000),
            default => rand(50000, 100000)
        };

        $jaspelPercentage = 0.4; // 40% for dokter
        $jaspelAmount = $baseRate * $jaspelPercentage;

        return [
            'biaya_total' => $baseRate,
            'jasa_dokter' => $jaspelAmount
        ];
    }

    /**
     * Get random status for tindakan
     */
    private function getRandomStatus()
    {
        $statuses = ['disetujui', 'disetujui', 'disetujui', 'disetujui', 'pending', 'ditolak'];
        return $statuses[array_rand($statuses)];
    }

    /**
     * Get random keterangan
     */
    private function getRandomKeterangan()
    {
        $keterangans = [
            'Tindakan berhasil dilakukan',
            'Pasien kooperatif',
            'Perlu follow up',
            'Tindakan sesuai protokol',
            'Hasil memuaskan',
            null,
            null
        ];
        
        return $keterangans[array_rand($keterangans)];
    }
}