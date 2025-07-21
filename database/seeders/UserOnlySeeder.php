<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use App\Models\WorkLocation;
use Illuminate\Support\Facades\Hash;

class UserOnlySeeder extends Seeder
{
    public function run()
    {
        try {
            // Create basic roles
            $roles = [
                ['name' => 'admin', 'display_name' => 'Administrator'],
                ['name' => 'manajer', 'display_name' => 'Manajer'],  
                ['name' => 'bendahara', 'display_name' => 'Bendahara'],
                ['name' => 'petugas', 'display_name' => 'Petugas'],
                ['name' => 'paramedis', 'display_name' => 'Paramedis'],
                ['name' => 'dokter', 'display_name' => 'Dokter']
            ];
            
            foreach ($roles as $roleData) {
                Role::firstOrCreate(
                    ['name' => $roleData['name']], 
                    ['display_name' => $roleData['display_name']]
                );
            }
            
            $this->command->info('✅ Roles created');
            
            // Create work locations
            $locations = [
                [
                    'name' => 'Klinik Utama Dokterku',
                    'address' => 'Jl. Kesehatan Raya No. 123, Jakarta Pusat',
                    'latitude' => -6.200000,
                    'longitude' => 106.816666,
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
            
            // Create users
            $users = [
                [
                    'name' => 'Admin Klinik',
                    'email' => 'admin@dokterkuklinik.com',
                    'role' => 'admin'
                ],
                [
                    'name' => 'Manajer Klinik', 
                    'email' => 'manajer@dokterkuklinik.com',
                    'role' => 'manajer'
                ],
                [
                    'name' => 'Petugas Administrasi',
                    'email' => 'petugas@dokterkuklinik.com', 
                    'role' => 'petugas'
                ],
                [
                    'name' => 'Perawat Klinik',
                    'email' => 'paramedis@dokterkuklinik.com',
                    'role' => 'paramedis'
                ],
                [
                    'name' => 'Dokter Klinik',
                    'email' => 'dokter@dokterkuklinik.com',
                    'role' => 'dokter'
                ]
            ];
            
            foreach ($users as $userData) {
                $role = Role::where('name', $userData['role'])->first();
                
                User::firstOrCreate(
                    ['email' => $userData['email']], 
                    [
                        'name' => $userData['name'],
                        'password' => Hash::make('password123'),
                        'role_id' => $role->id,
                        'email_verified_at' => now()
                    ]
                );
            }
            
            $this->command->info('✅ Data restoration completed\!');
            $this->command->info('📊 Total Users: ' . User::count());
            $this->command->info('📍 Total Work Locations: ' . WorkLocation::count()); 
            $this->command->info('🎭 Total Roles: ' . Role::count());
            
        } catch (\Exception $e) {
            $this->command->error('❌ Error: ' . $e->getMessage());
        }
    }
}
