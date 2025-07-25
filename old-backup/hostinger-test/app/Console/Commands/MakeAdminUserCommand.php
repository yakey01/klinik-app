<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\Role;

class MakeAdminUserCommand extends Command
{
    protected $signature = 'make:admin-user';
    protected $description = 'Create an admin user for production environments';

    public function handle()
    {
        $this->info('🔐 Creating Admin User for Production Environment');
        $this->newLine();

        // Get admin role
        $adminRole = Role::where('name', 'admin')->first();
        if (!$adminRole) {
            $this->error('Admin role not found. Please run seeders first.');
            return 1;
        }

        // Get user input
        $name = $this->ask('Enter admin name', 'Administrator');
        $email = $this->ask('Enter admin email');
        
        // Validate email
        $validator = Validator::make(['email' => $email], [
            'email' => 'required|email|unique:users,email'
        ]);
        
        if ($validator->fails()) {
            $this->error('Invalid email or email already exists.');
            return 1;
        }

        // Get password
        $password = $this->secret('Enter admin password (minimum 8 characters)');
        $passwordConfirm = $this->secret('Confirm admin password');

        if ($password !== $passwordConfirm) {
            $this->error('Passwords do not match.');
            return 1;
        }

        if (strlen($password) < 8) {
            $this->error('Password must be at least 8 characters long.');
            return 1;
        }

        // Create admin user
        try {
            $admin = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
                'is_active' => true,
                'role_id' => $adminRole->id,
                'email_verified_at' => now(),
            ]);

            // Assign Spatie role
            $admin->assignRole('admin');

            $this->info('✅ Admin user created successfully!');
            $this->table(['Field', 'Value'], [
                ['Name', $admin->name],
                ['Email', $admin->email],
                ['Role', 'Administrator'],
                ['Status', 'Active'],
                ['Created', $admin->created_at->format('Y-m-d H:i:s')]
            ]);

            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Failed to create admin user: ' . $e->getMessage());
            return 1;
        }
    }
}