<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use App\Models\WorkLocation;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RobustUserSeeder extends Seeder
{
    public function run()
    {
        try {
            DB::beginTransaction();
            
            // Create roles first
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
            
            // Create work locations
            WorkLocation::firstOrCreate(
                ['name' => 'Klinik Utama Dokterku'],
                [
                    'address' => 'Jl. Kesehatan Raya No. 123, Jakarta Pusat',
                    'latitude' => -6.200000,
                    'longitude' => 106.816666,
                    'is_active' => true
                ]
            );
            
            // Robust user creation with validation
            $users = [
                [
                    'name' => 'Administrator Klinik',
                    'email' => 'admin@dokterkuklinik.com',
                    'role' => 'admin'
                ],
                [
                    'name' => 'Manajer Operasional',
                    'email' => 'manajer@dokterkuklinik.com',
                    'role' => 'manajer'
                ],
                [
                    'name' => 'Bendahara Klinik',
                    'email' => 'bendahara@dokterkuklinik.com',
                    'role' => 'bendahara'
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
                // Validate email
                $validator = Validator::make($userData, [
                    'name' => 'required|string|max:255',
                    'email' => 'required|email|max:255',
                    'role' => 'required|string'
                ]);
                
                if ($validator->fails()) {
                    $this->command->error('Invalid user data: ' . implode(', ', $validator->errors()->all()));
                    continue;
                }
                
                $role = Role::where('name', $userData['role'])->first();
                if (\!$role) {
                    $this->command->error('Role not found: ' . $userData['role']);
                    continue;
                }
                
                // Use updateOrCreate to handle duplicates safely
                $user = User::updateOrCreate(
                    ['email' => $userData['email']], 
                    [
                        'name' => $userData['name'],
                        'password' => Hash::make('password123'),
                        'role_id' => $role->id,
                        'email_verified_at' => now()
                    ]
                );
                
                $this->command->info('✅ User created/updated: ' . $user->email);
            }
            
            DB::commit();
            
            $this->command->info('✅ Robust user seeding completed\!');
            $this->command->info('📊 Total Users: ' . User::count());
            $this->command->info('📍 Total Work Locations: ' . WorkLocation::count());
            $this->command->info('🎭 Total Roles: ' . Role::count());
            
        } catch (\Exception $e) {
            DB::rollback();
            $this->command->error('❌ Error: ' . $e->getMessage());
            $this->command->error('Stack trace: ' . $e->getTraceAsString());
        }
    }
}
