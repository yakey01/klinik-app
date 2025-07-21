<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Pegawai;
use App\Models\Role;
use App\Models\WorkLocation;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class CleanDataRestoreSeeder extends Seeder
{
    public function run()
    {
        try {
            // Create basic roles first
            $roles = [
                ['name' => 'admin', 'display_name' => 'Administrator'],
                ['name' => 'manajer', 'display_name' => 'Manajer'],
                ['name' => 'bendahara', 'display_name' => 'Bendahara'],
                ['name' => 'petugas', 'display_name' => 'Petugas'],
                ['name' => 'paramedis', 'display_name' => 'Paramedis'],
                ['name' => 'dokter', 'display_name' => 'Dokter'],
                ['name' => 'non_paramedis', 'display_name' => 'Non Paramedis']
            ];
            
            foreach ($roles as $roleData) {
                Role::firstOrCreate(
                    ['name' => $roleData['name']], 
                    ['display_name' => $roleData['display_name']]
                );
            }
            
            $this->command->info('✅ Roles created');
            
            // Create basic work locations
            $locations = [
                [
                    'name' => 'Klinik Utama Dokterku',
                    'address' => 'Jl. Kesehatan Raya No. 123, Jakarta Pusat',
                    'latitude' => -6.200000,
                    'longitude' => 106.816666,
                    'is_active' => true
                ],
                [
                    'name' => 'Cabang Dokterku Selatan',
                    'address' => 'Jl. Sehat Sejahtera No. 45, Jakarta Selatan', 
                    'latitude' => -6.261493,
                    'longitude' => 106.810600,
                    'is_active' => true
                ]
            ];
            
            foreach ($locations as $location) {
                WorkLocation::firstOrCreate(
                    ['name' => $location['name']], 
                    $location
                );
            }
            
            $this->command->info('✅ Work locations created');
            
            // Get role IDs  
            $adminRole = Role::where('name', 'admin')->first()->id;
            $manajerRole = Role::where('name', 'manajer')->first()->id;
            $petugasRole = Role::where('name', 'petugas')->first()->id;
            $paramedisRole = Role::where('name', 'paramedis')->first()->id;
            $dokterRole = Role::where('name', 'dokter')->first()->id;
            
            // Create sample pegawai
            $pegawaiData = [
                [
                    'name' => 'Dr. Ahmad Sudirman',
                    'email' => 'admin@dokterkuklinik.com',
                    'role_id' => $adminRole,
                    'nip' => 'ADM001',
                    'jabatan' => 'Administrator'
                ],
                [
                    'name' => 'Siti Rahayu, S.Kes',
                    'email' => 'manajer@dokterkuklinik.com',
                    'role_id' => $manajerRole,
                    'nip' => 'MNG001', 
                    'jabatan' => 'Manajer Operasional'
                ],
                [
                    'name' => 'Andi Wijaya',
                    'email' => 'petugas1@dokterkuklinik.com',
                    'role_id' => $petugasRole,
                    'nip' => 'PTG001',
                    'jabatan' => 'Petugas Administrasi'
                ],
                [
                    'name' => 'Ns. Indira Sari, S.Kep', 
                    'email' => 'paramedis1@dokterkuklinik.com',
                    'role_id' => $paramedisRole,
                    'nip' => 'PRM001',
                    'jabatan' => 'Perawat'
                ],
                [
                    'name' => 'Dr. Yaya Mulyana, Sp.PD',
                    'email' => 'dokter1@dokterkuklinik.com',
                    'role_id' => $dokterRole,
                    'nip' => 'DKT001',
                    'jabatan' => 'Dokter Spesialis'
                ]
            ];
            
            foreach ($pegawaiData as $data) {
                $existingUser = User::where('email', $data['email'])->first();
                
                if (!$existingUser) {
                    $user = User::create([
                        'name' => $data['name'],
                        'email' => $data['email'], 
                        'password' => Hash::make('password123'),
                        'role_id' => $data['role_id'],
                        'email_verified_at' => now()
                    ]);
                    
                    Pegawai::create([
                        'user_id' => $user->id,
                        'nip' => $data['nip'],
                        'nama' => $data['name'],
                        'jabatan' => $data['jabatan'],
                        'email' => $data['email'],
                        'no_hp' => '081234567' . str_pad(rand(100, 999), 3, '0'),
                        'alamat' => 'Jakarta', 
                        'tanggal_masuk' => now()->subDays(rand(30, 365)),
                        'status_pegawai' => 'tetap',
                        'is_active' => true
                    ]);
                }
            }
            
            $this->command->info('✅ Data restored successfully!');
            $this->command->info('📊 Total Users: ' . User::count());
            $this->command->info('👥 Total Pegawai: ' . Pegawai::count()); 
            $this->command->info('📍 Total Work Locations: ' . WorkLocation::count());
            $this->command->info('🎭 Total Roles: ' . Role::count());
            
        } catch (\Exception $e) {
            $this->command->error('❌ Error: ' . $e->getMessage());
        }
    }
}