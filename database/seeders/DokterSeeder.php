<?php

namespace Database\Seeders;

use App\Models\Dokter;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DokterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $dokters = [
            [
                'nama_lengkap' => 'Dr. Ahmad Yusuf, Sp.PD',
                'nik' => 'DOK20250001',
                'tanggal_lahir' => '1985-03-15',
                'jenis_kelamin' => 'Laki-laki',
                'jabatan' => 'dokter_spesialis',
                'nomor_sip' => '50/SIP/PD/2024/001',
                'email' => 'ahmad.yusuf@dokterku.com',
                'aktif' => true,
                'keterangan' => 'Spesialis Penyakit Dalam dengan pengalaman 10 tahun',
            ],
            [
                'nama_lengkap' => 'Dr. Sari Dewi',
                'nik' => 'DOK20250002', 
                'tanggal_lahir' => '1990-08-22',
                'jenis_kelamin' => 'Perempuan',
                'jabatan' => 'dokter_umum',
                'nomor_sip' => '50/SIP/DU/2024/002',
                'email' => 'sari.dewi@dokterku.com',
                'aktif' => true,
                'keterangan' => 'Dokter umum lulusan FK UI',
            ],
            [
                'nama_lengkap' => 'Dr. Budi Santoso, drg',
                'nik' => 'DOK20250003',
                'tanggal_lahir' => '1987-12-10',
                'jenis_kelamin' => 'Laki-laki',
                'jabatan' => 'dokter_gigi',
                'nomor_sip' => '50/SIP/DRG/2024/003',
                'email' => 'budi.santoso@dokterku.com',
                'aktif' => true,
                'keterangan' => 'Dokter gigi spesialis ortodonti',
            ],
            [
                'nama_lengkap' => 'Dr. Maya Putri',
                'nik' => 'DOK20250004',
                'tanggal_lahir' => '1992-05-18',
                'jenis_kelamin' => 'Perempuan',
                'jabatan' => 'dokter_umum',
                'nomor_sip' => '50/SIP/DU/2024/004',
                'email' => 'maya.putri@dokterku.com',
                'aktif' => true,
                'keterangan' => 'Dokter umum dengan fokus kesehatan ibu dan anak',
            ],
            [
                'nama_lengkap' => 'Dr. Reza Maulana, Sp.A',
                'nik' => 'DOK20250005',
                'tanggal_lahir' => '1983-11-30',
                'jenis_kelamin' => 'Laki-laki',
                'jabatan' => 'dokter_spesialis',
                'nomor_sip' => '50/SIP/SPA/2024/005',
                'email' => 'reza.maulana@dokterku.com',
                'aktif' => false, // Contoh dokter nonaktif
                'keterangan' => 'Spesialis Anak, sedang cuti panjang',
            ],
        ];

        foreach ($dokters as $dokterData) {
            Dokter::create($dokterData);
        }
    }
}
