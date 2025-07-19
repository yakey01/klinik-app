<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class NewUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Users yang ditambah via admin panel
        $users = [
            [
                'name' => 'Dr. Ahmad Santoso',
                'email' => 'ahmad.santoso@dokterku.com',
                'password' => bcrypt('password123'),
                'email_verified_at' => now(),
                'nip' => 'DOK001',
                'no_telepon' => '081234567890',
                'tanggal_bergabung' => '2024-01-15',
                'bio' => 'Dokter umum dengan pengalaman 5 tahun',
                'role' => 'dokter',
            ],
            [
                'name' => 'Sari Wulandari',
                'email' => 'sari.wulandari@dokterku.com', 
                'password' => bcrypt('password123'),
                'email_verified_at' => now(),
                'nip' => 'PTG001',
                'no_telepon' => '081234567891',
                'tanggal_bergabung' => '2024-01-20',
                'bio' => 'Petugas administrasi',
                'role' => 'petugas',
            ],
            // Tambahkan user baru di sini sesuai yang ditambah via admin
        ];

        foreach ($users as $userData) {
            // Check if user already exists
            $existingUser = \App\Models\User::where('email', $userData['email'])
                ->orWhere('nip', $userData['nip'])
                ->first();
                
            if ($existingUser) {
                $this->command->info("User {$userData['email']} already exists, skipping...");
                continue;
            }
            
            // Extract role before creating user
            $role = $userData['role'];
            unset($userData['role']);
            
            // Add timestamps
            $userData['created_at'] = now();
            $userData['updated_at'] = now();
            
            $user = \App\Models\User::create($userData);
            
            // Assign role
            if (\Spatie\Permission\Models\Role::where('name', $role)->exists()) {
                $user->assignRole($role);
                $this->command->info("Created user: {$user->name} with role: {$role}");
            } else {
                $this->command->warn("Role '{$role}' not found for user: {$user->name}");
            }
        }
    }
}
