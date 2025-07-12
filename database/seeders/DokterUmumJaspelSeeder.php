<?php

namespace Database\Seeders;

use App\Models\DokterUmumJaspel;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DokterUmumJaspelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $formulas = [
            [
                'jenis_shift' => 'Pagi',
                'ambang_pasien' => 10,
                'fee_pasien_umum' => 7000,
                'fee_pasien_bpjs' => 5000,
                'status_aktif' => true,
                'keterangan' => 'Formula JP untuk shift pagi (08:00-16:00)',
            ],
            [
                'jenis_shift' => 'Sore',
                'ambang_pasien' => 8,
                'fee_pasien_umum' => 8000,
                'fee_pasien_bpjs' => 6000,
                'status_aktif' => true,
                'keterangan' => 'Formula JP untuk shift sore (16:00-24:00), fee lebih tinggi',
            ],
            [
                'jenis_shift' => 'Hari Libur Besar',
                'ambang_pasien' => 5,
                'fee_pasien_umum' => 10000,
                'fee_pasien_bpjs' => 7500,
                'status_aktif' => false, // Contoh formula nonaktif
                'keterangan' => 'Formula JP untuk hari libur besar dan weekend, fee premium',
            ],
        ];

        foreach ($formulas as $formulaData) {
            DokterUmumJaspel::create($formulaData);
        }
    }
}
