<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Dokter;
use App\Models\Pegawai;
use App\Models\User;
use App\Models\Role;

class StaffSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        // Get roles
        $dokterRole = Role::where('name', 'dokter')->first();
        $paramedisRole = Role::where('name', 'paramedis')->first();
        $petugasRole = Role::where('name', 'petugas')->first();

        // Create sample Dokter records
        $dokters = [
            [
                'nama_lengkap' => 'Dr. Ahmad Rizky, Sp.PD',
                'nik' => 'DOK101',
                'jabatan' => 'Dokter Spesialis Penyakit Dalam',
                'nomor_sip' => 'SIP101/2024',
                'aktif' => true,
                'spesialisasi' => 'Penyakit Dalam',
                'email' => 'dr.ahmad@dokterku.com',
                'tanggal_bergabung' => '2024-01-15',
            ],
            [
                'nama_lengkap' => 'Dr. Sari Indah, Sp.A',
                'nik' => 'DOK102',
                'jabatan' => 'Dokter Spesialis Anak',
                'nomor_sip' => 'SIP102/2024',
                'aktif' => true,
                'spesialisasi' => 'Pediatri',
                'email' => 'dr.sari@dokterku.com',
                'tanggal_bergabung' => '2024-02-01',
            ],
            [
                'nama_lengkap' => 'Dr. Budi Santoso',
                'nik' => 'DOK103',
                'jabatan' => 'Dokter Umum',
                'nomor_sip' => 'SIP103/2024',
                'aktif' => true,
                'email' => 'dr.budi@dokterku.com',
                'tanggal_bergabung' => '2024-03-01',
            ],
            [
                'nama_lengkap' => 'Dr. Maya Putri',
                'nik' => 'DOK104',
                'jabatan' => 'Dokter Umum',
                'nomor_sip' => 'SIP104/2024',
                'aktif' => true,
                'email' => 'dr.maya@dokterku.com',
                'tanggal_bergabung' => '2024-03-15',
            ],
        ];

        foreach ($dokters as $dokterData) {
            // Create User account for dokter
            $user = User::create([
                'name' => $dokterData['nama_lengkap'],
                'email' => $dokterData['email'],
                'password' => bcrypt('dokter123'),
                'role_id' => $dokterRole->id,
                'nip' => $dokterData['nik'],
                'is_active' => true,
                'email_verified_at' => now(),
            ]);

            // Create Dokter record
            Dokter::create(array_merge($dokterData, ['user_id' => $user->id]));
        }

        // Create sample Pegawai records (Paramedis)
        $paramedis = [
            [
                'nama_lengkap' => 'Ns. Rina Sari, S.Kep',
                'nik' => 'PEG101',
                'jabatan' => 'Perawat Pelaksana',
                'jenis_pegawai' => 'Paramedis',
                'aktif' => true,
                'no_telepon' => '081234567001',
                'alamat' => 'Jl. Kesehatan No. 1',
            ],
            [
                'nama_lengkap' => 'Ns. Dewi Lestari, S.Kep',
                'nik' => 'PEG102',
                'jabatan' => 'Perawat Koordinator',
                'jenis_pegawai' => 'Paramedis',
                'aktif' => true,
                'no_telepon' => '081234567002',
                'alamat' => 'Jl. Kesehatan No. 2',
            ],
            [
                'nama_lengkap' => 'Bidan Sinta Maharani, S.ST',
                'nik' => 'PEG103',
                'jabatan' => 'Bidan',
                'jenis_pegawai' => 'Paramedis',
                'aktif' => true,
                'no_telepon' => '081234567003',
                'alamat' => 'Jl. Kesehatan No. 3',
            ],
            [
                'nama_lengkap' => 'Ns. Andi Pratama, S.Kep',
                'nik' => 'PEG104',
                'jabatan' => 'Perawat IGD',
                'jenis_pegawai' => 'Paramedis',
                'aktif' => true,
                'no_telepon' => '081234567004',
                'alamat' => 'Jl. Kesehatan No. 4',
            ],
        ];

        foreach ($paramedis as $pegawaiData) {
            // Create User account for paramedis
            $user = User::create([
                'name' => $pegawaiData['nama_lengkap'],
                'email' => strtolower(str_replace([' ', '.', ','], ['', '', ''], $pegawaiData['nama_lengkap'])) . '@dokterku.com',
                'password' => bcrypt('paramedis123'),
                'role_id' => $paramedisRole->id,
                'nip' => $pegawaiData['nik'],
                'is_active' => true,
                'email_verified_at' => now(),
            ]);

            // Create Pegawai record
            Pegawai::create($pegawaiData);
        }

        // Create sample Pegawai records (Non-Paramedis)
        $nonParamedis = [
            [
                'nama_lengkap' => 'Sari Administrasi',
                'nik' => 'PEG201',
                'jabatan' => 'Staff Administrasi',
                'jenis_pegawai' => 'Non-Paramedis',
                'aktif' => true,
                'no_telepon' => '081234567005',
                'alamat' => 'Jl. Admin No. 1',
            ],
            [
                'nama_lengkap' => 'Budi Resepsionis',
                'nik' => 'PEG202',
                'jabatan' => 'Resepsionis',
                'jenis_pegawai' => 'Non-Paramedis',
                'aktif' => true,
                'no_telepon' => '081234567006',
                'alamat' => 'Jl. Admin No. 2',
            ],
            [
                'nama_lengkap' => 'Andi Farmasi',
                'nik' => 'PEG203',
                'jabatan' => 'Asisten Apoteker',
                'jenis_pegawai' => 'Non-Paramedis',
                'aktif' => true,
                'no_telepon' => '081234567007',
                'alamat' => 'Jl. Admin No. 3',
            ],
            [
                'nama_lengkap' => 'Maya Keuangan',
                'nik' => 'PEG204',
                'jabatan' => 'Staff Keuangan',
                'jenis_pegawai' => 'Non-Paramedis',
                'aktif' => true,
                'no_telepon' => '081234567008',
                'alamat' => 'Jl. Admin No. 4',
            ],
        ];

        foreach ($nonParamedis as $pegawaiData) {
            // Create User account for non-paramedis
            $user = User::create([
                'name' => $pegawaiData['nama_lengkap'],
                'email' => strtolower(str_replace([' ', '.', ','], ['', '', ''], $pegawaiData['nama_lengkap'])) . '@dokterku.com',
                'password' => bcrypt('petugas123'),
                'role_id' => $petugasRole->id,
                'nip' => $pegawaiData['nik'],
                'is_active' => true,
                'email_verified_at' => now(),
            ]);

            // Create Pegawai record
            Pegawai::create($pegawaiData);
        }

        $this->command->info('Staff seeder completed:');
        $this->command->info('- 4 Dokter records created');
        $this->command->info('- 4 Paramedis records created');
        $this->command->info('- 4 Non-Paramedis records created');
        $this->command->info('- All staff have User accounts for login');
    }
}