<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class PegawaiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\Pegawai::factory(25)->create();

        \App\Models\Pegawai::create([
            'nik' => '3201234567890001',
            'nama_lengkap' => 'Dr. Sarah Medika',
            'tanggal_lahir' => '1985-05-15',
            'jenis_kelamin' => 'Perempuan',
            'jabatan' => 'Dokter Umum',
            'jenis_pegawai' => 'Paramedis',
            'aktif' => true,
            'input_by' => 1,
        ]);

        \App\Models\Pegawai::create([
            'nik' => '3201234567890002',
            'nama_lengkap' => 'Ahmad Nursalam',
            'tanggal_lahir' => '1990-08-20',
            'jenis_kelamin' => 'Laki-laki',
            'jabatan' => 'Perawat',
            'jenis_pegawai' => 'Paramedis',
            'aktif' => true,
            'input_by' => 1,
        ]);

        \App\Models\Pegawai::create([
            'nik' => '3201234567890003',
            'nama_lengkap' => 'Siti Kasir',
            'tanggal_lahir' => '1992-12-10',
            'jenis_kelamin' => 'Perempuan',
            'jabatan' => 'Kasir',
            'jenis_pegawai' => 'Non-Paramedis',
            'aktif' => true,
            'input_by' => 1,
        ]);
    }
}
