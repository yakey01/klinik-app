<?php

namespace Database\Seeders\Master;

use App\Models\JenisTindakan;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class JenisTindakanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jenisTindakan = [
            [
                'kode' => 'KONS001',
                'nama' => 'Konsultasi Dokter Umum',
                'deskripsi' => 'Konsultasi dengan dokter umum untuk pemeriksaan kesehatan',
                'tarif' => 50000,
                'jasa_dokter' => 30000,
                'jasa_paramedis' => 5000,
                'jasa_non_paramedis' => 3000,
                'kategori' => 'konsultasi',
            ],
            [
                'kode' => 'KONS002',
                'nama' => 'Konsultasi Dokter Spesialis',
                'deskripsi' => 'Konsultasi dengan dokter spesialis',
                'tarif' => 150000,
                'jasa_dokter' => 100000,
                'jasa_paramedis' => 10000,
                'jasa_non_paramedis' => 5000,
                'kategori' => 'konsultasi',
            ],
            [
                'kode' => 'PERI001',
                'nama' => 'Pemeriksaan Tekanan Darah',
                'deskripsi' => 'Pemeriksaan tekanan darah dan nadi',
                'tarif' => 25000,
                'jasa_dokter' => 0,
                'jasa_paramedis' => 15000,
                'jasa_non_paramedis' => 2000,
                'kategori' => 'pemeriksaan',
            ],
            [
                'kode' => 'PERI002',
                'nama' => 'Pemeriksaan Gula Darah',
                'deskripsi' => 'Pemeriksaan kadar gula darah sewaktu',
                'tarif' => 35000,
                'jasa_dokter' => 0,
                'jasa_paramedis' => 20000,
                'jasa_non_paramedis' => 3000,
                'kategori' => 'pemeriksaan',
            ],
            [
                'kode' => 'TIND001',
                'nama' => 'Suntik Intramuskular',
                'deskripsi' => 'Pemberian suntikan obat secara intramuskular',
                'tarif' => 30000,
                'jasa_dokter' => 0,
                'jasa_paramedis' => 20000,
                'jasa_non_paramedis' => 3000,
                'kategori' => 'tindakan',
            ],
            [
                'kode' => 'TIND002',
                'nama' => 'Perawatan Luka',
                'deskripsi' => 'Perawatan dan pembersihan luka',
                'tarif' => 40000,
                'jasa_dokter' => 0,
                'jasa_paramedis' => 25000,
                'jasa_non_paramedis' => 5000,
                'kategori' => 'tindakan',
            ],
            [
                'kode' => 'OBAT001',
                'nama' => 'Obat Paracetamol',
                'deskripsi' => 'Obat penurun demam dan pereda nyeri',
                'tarif' => 15000,
                'jasa_dokter' => 0,
                'jasa_paramedis' => 0,
                'jasa_non_paramedis' => 2000,
                'kategori' => 'obat',
            ],
            [
                'kode' => 'OBAT002',
                'nama' => 'Obat Antibiotik',
                'deskripsi' => 'Obat antibiotik untuk infeksi bakteri',
                'tarif' => 45000,
                'jasa_dokter' => 0,
                'jasa_paramedis' => 0,
                'jasa_non_paramedis' => 3000,
                'kategori' => 'obat',
            ],
            [
                'kode' => 'LAIN001',
                'nama' => 'Surat Keterangan Sehat',
                'deskripsi' => 'Penerbitan surat keterangan sehat',
                'tarif' => 25000,
                'jasa_dokter' => 15000,
                'jasa_paramedis' => 0,
                'jasa_non_paramedis' => 5000,
                'kategori' => 'lainnya',
            ],
            [
                'kode' => 'LAIN002',
                'nama' => 'Surat Keterangan Sakit',
                'deskripsi' => 'Penerbitan surat keterangan sakit',
                'tarif' => 20000,
                'jasa_dokter' => 12000,
                'jasa_paramedis' => 0,
                'jasa_non_paramedis' => 3000,
                'kategori' => 'lainnya',
            ],
        ];

        foreach ($jenisTindakan as $jenis) {
            JenisTindakan::create($jenis);
        }
    }
}
