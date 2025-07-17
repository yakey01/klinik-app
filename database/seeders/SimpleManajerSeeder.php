<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SimpleManajerSeeder extends Seeder
{
    public function run(): void
    {
        // Create manajer role if not exists
        $manajerRoleId = DB::table('roles')->where('name', 'manajer')->value('id');
        
        if (!$manajerRoleId) {
            $manajerRoleId = DB::table('roles')->insertGetId([
                'name' => 'manajer',
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Create or update manajer user
        $existingUser = DB::table('users')->where('email', 'manajer@dokterku.com')->first();
        
        if ($existingUser) {
            // Update existing user
            DB::table('users')->where('email', 'manajer@dokterku.com')->update([
                'password' => Hash::make('password'),
                'is_active' => true,
                'email_verified_at' => now(),
                'updated_at' => now(),
            ]);
            $userId = $existingUser->id;
        } else {
            // Create new user
            $userId = DB::table('users')->insertGetId([
                'name' => 'Manajer Dokterku',
                'email' => 'manajer@dokterku.com',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Assign role
        $existingRole = DB::table('model_has_roles')
            ->where('model_id', $userId)
            ->where('role_id', $manajerRoleId)
            ->where('model_type', 'App\\Models\\User')
            ->first();

        if (!$existingRole) {
            DB::table('model_has_roles')->insert([
                'role_id' => $manajerRoleId,
                'model_type' => 'App\\Models\\User',
                'model_id' => $userId,
            ]);
        }

        // Update tina@manajer.com if exists
        $tinaUser = DB::table('users')->where('email', 'tina@manajer.com')->first();
        if ($tinaUser) {
            DB::table('users')->where('email', 'tina@manajer.com')->update([
                'password' => Hash::make('password'),
                'is_active' => true,
                'email_verified_at' => now(),
                'updated_at' => now(),
            ]);

            // Assign role to tina
            $existingTinaRole = DB::table('model_has_roles')
                ->where('model_id', $tinaUser->id)
                ->where('role_id', $manajerRoleId)
                ->where('model_type', 'App\\Models\\User')
                ->first();

            if (!$existingTinaRole) {
                DB::table('model_has_roles')->insert([
                    'role_id' => $manajerRoleId,
                    'model_type' => 'App\\Models\\User',
                    'model_id' => $tinaUser->id,
                ]);
            }
        }

        $this->command->info('✅ Manajer users created/updated successfully');
        $this->command->info('📧 Login: manajer@dokterku.com');
        $this->command->info('🔑 Password: password');
        if ($tinaUser) {
            $this->command->info('📧 Alternative: tina@manajer.com');
            $this->command->info('🔑 Password: password');
        }
        $this->command->info('🌐 URL: http://127.0.0.1:8000/manajer');
    }
}