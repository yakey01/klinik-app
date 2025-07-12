<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Dokter;
use Illuminate\Database\Seeder;

class LinkDokterUsersSeeder extends Seeder
{
    public function run(): void
    {
        // Find user with dokter role
        $dokterUser = User::whereHas('roles', function ($query) {
            $query->where('name', 'dokter');
        })->first();
        
        if ($dokterUser) {
            // Create or find existing dokter record
            $dokter = Dokter::first();
            
            if (!$dokter) {
                // Create a dokter record if none exists
                $dokter = Dokter::create([
                    'user_id' => $dokterUser->id,
                    'nama_lengkap' => $dokterUser->name,
                    'email' => $dokterUser->email,
                    'jabatan' => 'dokter_umum',
                    'aktif' => true,
                    'input_by' => 1 // Admin user
                ]);
                
                $this->command->info("Created new dokter record for user: {$dokterUser->name}");
            } else {
                // Link existing dokter to user
                $dokter->update(['user_id' => $dokterUser->id]);
                $this->command->info("Linked existing dokter record to user: {$dokterUser->name}");
            }
        } else {
            $this->command->warn('No user found with dokter role');
        }
    }
}