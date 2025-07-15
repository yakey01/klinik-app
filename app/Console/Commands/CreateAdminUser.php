<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class CreateAdminUser extends Command
{
    protected $signature = 'user:create-admin';
    protected $description = 'Create admin user for the system';

    public function handle()
    {
        // Create admin role if not exists
        $adminRole = Role::firstOrCreate([
            'name' => 'admin'
        ], [
            'display_name' => 'Administrator',
            'description' => 'Super administrator with full system access',
            'guard_name' => 'web',
            'is_active' => true,
        ]);

        $this->info("Admin role created/found: {$adminRole->name} (ID: {$adminRole->id})");

        // Create admin user
        $adminUser = User::where('email', 'admin@dokterku.com')->first();
        
        if ($adminUser) {
            $this->warn('Admin user already exists. Updating...');
            $adminUser->update([
                'role_id' => $adminRole->id,
                'password' => Hash::make('admin123'),
                'is_active' => true,
            ]);
        } else {
            $adminUser = User::create([
                'name' => 'Administrator',
                'email' => 'admin@dokterku.com',
                'username' => 'admin',
                'password' => Hash::make('admin123'),
                'role_id' => $adminRole->id,
                'is_active' => true,
                'email_verified_at' => now(),
            ]);
        }

        $this->info('Admin user created successfully!');
        $this->info('Email: admin@dokterku.com');
        $this->info('Username: admin');
        $this->info('Password: admin123');
        $this->info('Role: ' . $adminRole->name);
        
        return 0;
    }
}