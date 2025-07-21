<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Pegawai;
use App\Models\Role;
use App\Models\WorkLocation;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class RestorePegawaiSeeder extends Seeder
{
    public function run()
    {
        DB::beginTransaction();
        
        try {
            // Create roles with display names if they don't exist
            $roles = [
                ['name' => 'admin', 'display_name' => 'Administrator'],
                ['name' => 'manajer', 'display_name' => 'Manajer'],
                ['name' => 'bendahara', 'display_name' => 'Bendahara'],
                ['name' => 'petugas', 'display_name' => 'Petugas'],
                ['name' => 'paramedis', 'display_name' => 'Paramedis'],
                ['name' => 'dokter', 'display_name' => 'Dokter'],
                ['name' => 'non_paramedis', 'display_name' => 'Non Paramedis'],
                ['name' => 'dokter_gigi', 'display_name' => 'Dokter Gigi']
            ];
            
            foreach ($roles as $roleData) {
                Role::firstOrCreate(
                    ['name' => $roleData['name']], 
                    ['display_name' => $roleData['display_name']]
                );
            }
            
            // Get role IDs
            $adminRole = Role::where('name', 'admin')->first()->id;
            $manajerRole = Role::where('name', 'manajer')->first()->id;
            $bendaharaRole = Role::where('name', 'bendahara')->first()->id;
            $petugasRole = Role::where('name', 'petugas')->first()->id;
            $paramedisRole = Role::where('name', 'paramedis')->first()->id;
            $dokterRole = Role::where('name', 'dokter')->first()->id;
            $nonParamedisRole = Role::where('name', 'non_paramedis')->first()->id;
            
            // Create Work Locations (GPS Data)
            $workLocations = [
                [
                    'name' => 'Klinik Utama Dokterku',
                    'address' => 'Jl. Kesehatan Raya No. 123, Jakarta Pusat',
                    'latitude' => -6.200000,
                    'longitude' => 106.816666,
                    'radius' => 100,
                    'is_active' => true,
                    'description' => 'Lokasi kerja utama klinik dengan fasilitas lengkap',
                    'created_at' => now(),
                    'updated_at' => now()
                ],
                [
                    'name' => 'Cabang Dokterku Selatan', 
                    'address' => 'Jl. Sehat Sejahtera No. 45, Jakarta Selatan',
                    'latitude' => -6.261493,
                    'longitude' => 106.810600,
                    'radius' => 80,
                    'is_active' => true,
                    'description' => 'Cabang klinik di area Jakarta Selatan',
                    'created_at' => now(),
                    'updated_at' => now()
                ],
                [
                    'name' => 'Cabang Dokterku Timur',
                    'address' => 'Jl. Medika Prima No. 67, Jakarta Timur', 
                    'latitude' => -6.225014,
                    'longitude' => 106.900447,
                    'radius' => 90,
                    'is_active' => true,
                    'description' => 'Cabang klinik di area Jakarta Timur',
                    'created_at' => now(),
                    'updated_at' => now()
                ]
            ];
            
            foreach ($workLocations as $location) {
                WorkLocation::firstOrCreate(
                    ['name' => $location['name']], 
                    $location
                );
            }
            
            // Sample Pegawai Data
            $pegawaiData = [
                // Admin Staff
                [
                    'name' => 'Dr. Ahmad Sudirman',
                    'email' => 'admin@dokterkuklinik.com',
                    'password' => 'password123',
                    'role_id' => $adminRole,
                    'nip' => 'ADM001',
                    'jabatan' => 'Administrator',
                    'no_hp' => '081234567890',
                    'alamat' => 'Jl. Admin Raya No. 1, Jakarta',
                    'status_pegawai' => 'tetap'
                ],
                
                // Manajer
                [
                    'name' => 'Siti Rahayu, S.Kes',
                    'email' => 'manajer@dokterkuklinik.com', 
                    'password' => 'password123',
                    'role_id' => $manajerRole,
                    'nip' => 'MNG001',
                    'jabatan' => 'Manajer Operasional',
                    'no_hp' => '081234567891',
                    'alamat' => 'Jl. Manajer Indah No. 2, Jakarta',
                    'status_pegawai' => 'tetap'
                ],
                
                // Bendahara
                [
                    'name' => 'Budi Santoso, S.E',
                    'email' => 'bendahara@dokterkuklinik.com',
                    'password' => 'password123', 
                    'role_id' => $bendaharaRole,
                    'nip' => 'BND001',
                    'jabatan' => 'Bendahara',
                    'no_hp' => '081234567892',
                    'alamat' => 'Jl. Keuangan Sejahtera No. 3, Jakarta',
                    'status_pegawai' => 'tetap'
                ],
                
                // Petugas
                [
                    'name' => 'Andi Wijaya',
                    'email' => 'petugas1@dokterkuklinik.com',
                    'password' => 'password123',
                    'role_id' => $petugasRole,
                    'nip' => 'PTG001', 
                    'jabatan' => 'Petugas Administrasi',
                    'no_hp' => '081234567893',
                    'alamat' => 'Jl. Petugas Bahagia No. 4, Jakarta',
                    'status_pegawai' => 'tetap'
                ],
                [
                    'name' => 'Maya Sari',
                    'email' => 'petugas2@dokterkuklinik.com',
                    'password' => 'password123',
                    'role_id' => $petugasRole,
                    'nip' => 'PTG002',
                    'jabatan' => 'Petugas Pendaftaran', 
                    'no_hp' => '081234567894',
                    'alamat' => 'Jl. Pelayanan Prima No. 5, Jakarta',
                    'status_pegawai' => 'kontrak'
                ],
                
                // Paramedis
                [
                    'name' => 'Ns. Indira Sari, S.Kep',
                    'email' => 'paramedis1@dokterkuklinik.com',
                    'password' => 'password123',
                    'role_id' => $paramedisRole,
                    'nip' => 'PRM001',
                    'jabatan' => 'Perawat',
                    'no_hp' => '081234567895', 
                    'alamat' => 'Jl. Perawat Setia No. 6, Jakarta',
                    'status_pegawai' => 'tetap'
                ],
                [
                    'name' => 'Ns. Rini Astuti, S.Kep',
                    'email' => 'paramedis2@dokterkuklinik.com',
                    'password' => 'password123',
                    'role_id' => $paramedisRole,
                    'nip' => 'PRM002',
                    'jabatan' => 'Perawat IGD',
                    'no_hp' => '081234567896',
                    'alamat' => 'Jl. Gawat Darurat No. 7, Jakarta', 
                    'status_pegawai' => 'tetap'
                ],
                
                // Dokter
                [
                    'name' => 'Dr. Yaya Mulyana, Sp.PD',
                    'email' => 'dokter1@dokterkuklinik.com',
                    'password' => 'password123',
                    'role_id' => $dokterRole,
                    'nip' => 'DKT001',
                    'jabatan' => 'Dokter Spesialis Penyakit Dalam',
                    'no_hp' => '081234567897',
                    'alamat' => 'Jl. Dokter Ahli No. 8, Jakarta',
                    'status_pegawai' => 'tetap'
                ],
                [
                    'name' => 'Dr. Naning Setiawati, Sp.A', 
                    'email' => 'dokter2@dokterkuklinik.com',
                    'password' => 'password123',
                    'role_id' => $dokterRole,
                    'nip' => 'DKT002',
                    'jabatan' => 'Dokter Spesialis Anak',
                    'no_hp' => '081234567898',
                    'alamat' => 'Jl. Anak Sehat No. 9, Jakarta',
                    'status_pegawai' => 'tetap'
                ],
                
                // Non-Paramedis Staff
                [
                    'name' => 'Joko Susilo',
                    'email' => 'nonparamedis1@dokterkuklinik.com',
                    'password' => 'password123', 
                    'role_id' => $nonParamedisRole,
                    'nip' => 'NPM001',
                    'jabatan' => 'Teknisi IT',
                    'no_hp' => '081234567899',
                    'alamat' => 'Jl. Teknologi Canggih No. 10, Jakarta',
                    'status_pegawai' => 'kontrak'
                ],
                [
                    'name' => 'Sri Wahyuni',
                    'email' => 'nonparamedis2@dokterkuklinik.com',
                    'password' => 'password123',
                    'role_id' => $nonParamedisRole,
                    'nip' => 'NPM002', 
                    'jabatan' => 'Cleaning Service',
                    'no_hp' => '081234567800',
                    'alamat' => 'Jl. Kebersihan Terjaga No. 11, Jakarta',
                    'status_pegawai' => 'kontrak'
                ]
            ];
            
            foreach ($pegawaiData as $data) {
                // Check if user exists
                $existingUser = User::where('email', $data['email'])->first();
                
                if (!$existingUser) {
                    // Create User
                    $user = User::create([
                        'name' => $data['name'],
                        'email' => $data['email'],
                        'password' => Hash::make($data['password']),
                        'role_id' => $data['role_id'],
                        'email_verified_at' => now()
                    ]);
                    
                    // Create Pegawai
                    Pegawai::firstOrCreate(
                        ['nip' => $data['nip']], 
                        [
                            'user_id' => $user->id,
                            'nama' => $data['name'],
                            'jabatan' => $data['jabatan'],
                            'email' => $data['email'],
                            'no_hp' => $data['no_hp'],
                            'alamat' => $data['alamat'],
                            'tanggal_masuk' => now()->subDays(rand(30, 365)),
                            'status_pegawai' => $data['status_pegawai'],
                            'is_active' => true
                        ]
                    );
                }
            }
            
            DB::commit();
            $this->command->info('✅ Data pegawai dan lokasi GPS berhasil di-restore!');
            $this->command->info('📊 Total Users: ' . User::count());
            $this->command->info('👥 Total Pegawai: ' . Pegawai::count());
            $this->command->info('📍 Total Lokasi Kerja: ' . WorkLocation::count());
            $this->command->info('🎭 Total Roles: ' . Role::count());
            
        } catch (\Exception $e) {
            DB::rollback();
            $this->command->error('❌ Error: ' . $e->getMessage());
            $this->command->error('Stack trace: ' . $e->getTraceAsString());
        }
    }
}
