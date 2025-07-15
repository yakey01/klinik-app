<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class NonParamedisUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get or create non_paramedis role
        $nonParamedisRole = Role::firstOrCreate(
            ['name' => 'non_paramedis'],
            [
                'display_name' => 'Non Paramedis',
                'description' => 'Non-medical staff role for administrative and support functions',
                'permissions' => [
                    'dashboard.access',
                    'attendance.view',
                    'attendance.create',
                    'attendance.edit',
                    'profile.view',
                    'profile.edit'
                ],
                'is_active' => true,
            ]
        );

        // Create diverse non-paramedis users with different work patterns
        
        // 1. Senior Administrator - Very punctual, hardworking
        User::create([
            'role_id' => $nonParamedisRole->id,
            'name' => 'Siti Nurhaliza',
            'email' => 'siti.nurhaliza@dokterku.com',
            'username' => 'siti.nurhaliza',
            'password' => Hash::make('password123'),
            'nip' => 'NP001',
            'no_telepon' => '+62 812 3456 7890',
            'tanggal_bergabung' => Carbon::now()->subYears(3)->subMonths(2),
            'is_active' => true,
        ]);

        // 2. Finance Staff - Consistent, slightly early arrivals
        User::create([
            'role_id' => $nonParamedisRole->id,
            'name' => 'Bambang Wijaya',
            'email' => 'bambang.wijaya@dokterku.com',
            'username' => 'bambang.wijaya',
            'password' => Hash::make('password123'),
            'nip' => 'NP002',
            'no_telepon' => '+62 813 4567 8901',
            'tanggal_bergabung' => Carbon::now()->subYears(2)->subMonths(8),
            'is_active' => true,
        ]);

        // 3. IT Support - Sometimes late due to overnight maintenance
        User::create([
            'role_id' => $nonParamedisRole->id,
            'name' => 'Ahmad Rizky Pratama',
            'email' => 'ahmad.rizky@dokterku.com',
            'username' => 'ahmad.rizky',
            'password' => Hash::make('password123'),
            'nip' => 'NP003',
            'no_telepon' => '+62 814 5678 9012',
            'tanggal_bergabung' => Carbon::now()->subYears(1)->subMonths(6),
            'is_active' => true,
        ]);

        // 4. Customer Service - Flexible hours, sometimes overtime
        User::create([
            'role_id' => $nonParamedisRole->id,
            'name' => 'Dewi Sartika',
            'email' => 'dewi.sartika@dokterku.com',
            'username' => 'dewi.sartika',
            'password' => Hash::make('password123'),
            'nip' => 'NP004',
            'no_telepon' => '+62 815 6789 0123',
            'tanggal_bergabung' => Carbon::now()->subYears(2)->subMonths(3),
            'is_active' => true,
        ]);

        // 5. Security Guard - Mixed patterns, shift work
        User::create([
            'role_id' => $nonParamedisRole->id,
            'name' => 'Joko Susilo',
            'email' => 'joko.susilo@dokterku.com',
            'username' => 'joko.susilo',
            'password' => Hash::make('password123'),
            'nip' => 'NP005',
            'no_telepon' => '+62 816 7890 1234',
            'tanggal_bergabung' => Carbon::now()->subYears(1)->subMonths(10),
            'is_active' => true,
        ]);

        // 6. HR Staff - Generally punctual but sometimes has external meetings
        User::create([
            'role_id' => $nonParamedisRole->id,
            'name' => 'Linda Maharani',
            'email' => 'linda.maharani@dokterku.com',
            'username' => 'linda.maharani',
            'password' => Hash::make('password123'),
            'nip' => 'NP006',
            'no_telepon' => '+62 817 8901 2345',
            'tanggal_bergabung' => Carbon::now()->subYears(3)->subMonths(6),
            'is_active' => true,
        ]);

        // 7. Inventory Manager - Early arrival, manages supplies
        User::create([
            'role_id' => $nonParamedisRole->id,
            'name' => 'Eko Prasetyo',
            'email' => 'eko.prasetyo@dokterku.com',
            'username' => 'eko.prasetyo',
            'password' => Hash::make('password123'),
            'nip' => 'NP007',
            'no_telepon' => '+62 818 9012 3456',
            'tanggal_bergabung' => Carbon::now()->subYears(2)->subMonths(1),
            'is_active' => true,
        ]);

        // 8. Marketing Coordinator - Creative schedule, sometimes remote work
        User::create([
            'role_id' => $nonParamedisRole->id,
            'name' => 'Putri Permatasari',
            'email' => 'putri.permatasari@dokterku.com',
            'username' => 'putri.permatasari',
            'password' => Hash::make('password123'),
            'nip' => 'NP008',
            'no_telepon' => '+62 819 0123 4567',
            'tanggal_bergabung' => Carbon::now()->subYears(1)->subMonths(4),
            'is_active' => true,
        ]);

        // 9. Maintenance Worker - Early bird, sometimes weekend work
        User::create([
            'role_id' => $nonParamedisRole->id,
            'name' => 'Agus Setiawan',
            'email' => 'agus.setiawan@dokterku.com',
            'username' => 'agus.setiawan',
            'password' => Hash::make('password123'),
            'nip' => 'NP009',
            'no_telepon' => '+62 820 1234 5678',
            'tanggal_bergabung' => Carbon::now()->subYears(4)->subMonths(2),
            'is_active' => true,
        ]);

        // 10. Junior Admin - New employee, learning patterns
        User::create([
            'role_id' => $nonParamedisRole->id,
            'name' => 'Rini Handayani',
            'email' => 'rini.handayani@dokterku.com',
            'username' => 'rini.handayani',
            'password' => Hash::make('password123'),
            'nip' => 'NP010',
            'no_telepon' => '+62 821 2345 6789',
            'tanggal_bergabung' => Carbon::now()->subMonths(3),
            'is_active' => true,
        ]);

        // Create one inactive user for testing
        User::create([
            'role_id' => $nonParamedisRole->id,
            'name' => 'Budi Santoso (Cuti Panjang)',
            'email' => 'budi.santoso@dokterku.com',
            'username' => 'budi.santoso',
            'password' => Hash::make('password123'),
            'nip' => 'NP011',
            'no_telepon' => '+62 822 3456 7890',
            'tanggal_bergabung' => Carbon::now()->subYears(2),
            'is_active' => false, // Inactive for testing
        ]);

        $this->command->info('NonParamedisUserSeeder completed! Created 11 non-paramedis users (10 active, 1 inactive) with diverse work patterns.');
    }
}