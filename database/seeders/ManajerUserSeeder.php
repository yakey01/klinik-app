<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

class ManajerUserSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure manajer role exists
        $manajerRole = Role::firstOrCreate([
            'name' => 'manajer',
            'guard_name' => 'web',
        ], [
            'display_name' => 'Manajer',
            'description' => 'Manajer dengan akses ke dashboard eksekutif',
        ]);

        // Create or update manajer user
        $manajerUser = User::updateOrCreate([
            'email' => 'manajer@dokterku.com'
        ], [
            'name' => 'Manajer Dokterku',
            'email' => 'manajer@dokterku.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Assign manajer role
        if (!$manajerUser->hasRole('manajer')) {
            $manajerUser->assignRole('manajer');
        }

        // Also update existing tina@manajer.com if exists
        $tinaUser = User::where('email', 'tina@manajer.com')->first();
        if ($tinaUser) {
            $tinaUser->update([
                'password' => Hash::make('password'),
                'is_active' => true,
                'email_verified_at' => now(),
            ]);
            
            if (!$tinaUser->hasRole('manajer')) {
                $tinaUser->assignRole('manajer');
            }
        }

        $this->command->info('✅ Manajer users created/updated successfully');
        $this->command->info('📧 Login: manajer@dokterku.com');
        $this->command->info('🔑 Password: password');
        if ($tinaUser) {
            $this->command->info('📧 Alternative: tina@manajer.com');
            $this->command->info('🔑 Password: password');
        }
    }
}