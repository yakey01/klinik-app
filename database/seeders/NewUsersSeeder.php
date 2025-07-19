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
        // Contoh user yang ditambah via admin
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
                'created_at' => now(),
                'updated_at' => now(),
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
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($users as $userData) {
            $user = \App\Models\User::create($userData);
            
            // Assign role berdasarkan NIP prefix
            if (str_starts_with($userData['nip'], 'DOK')) {
                $user->assignRole('dokter');
            } elseif (str_starts_with($userData['nip'], 'PTG')) {
                $user->assignRole('petugas');
            }
        }
    }
}
