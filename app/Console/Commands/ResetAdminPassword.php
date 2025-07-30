<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class ResetAdminPassword extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:reset-password {--email=admin@dokterku.com} {--password=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset admin user password or create new admin user';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->option('email');
        $password = $this->option('password');
        
        $this->info('=== Admin Password Reset ===');
        
        // Find admin user
        $admin = User::where('email', $email)->first();
        
        if (!$admin) {
            $this->error("Admin user with email '{$email}' not found!");
            
            if ($this->confirm('Do you want to create a new admin user?')) {
                // Create admin role if it doesn't exist
                $adminRole = Role::firstOrCreate([
                    'name' => 'admin'
                ], [
                    'display_name' => 'Administrator',
                    'description' => 'System administrator with full access',
                    'is_active' => true,
                    'guard_name' => 'web'
                ]);
                
                // Create Spatie role too
                $spatieAdmin = \Spatie\Permission\Models\Role::firstOrCreate([
                    'name' => 'admin',
                    'guard_name' => 'web'
                ]);
                
                // Get user details
                $name = $this->ask('Enter admin name', 'Admin User');
                $username = $this->ask('Enter username', 'admin');
                
                // Create new admin user
                $admin = User::create([
                    'name' => $name,
                    'email' => $email,
                    'username' => $username,
                    'password' => Hash::make('temp123'),
                    'is_active' => true,
                    'role_id' => $adminRole->id
                ]);
                
                // Assign Spatie role
                $admin->assignRole('admin');
                
                $this->info('✅ Admin user created successfully!');
            } else {
                return 1;
            }
        }
        
        // Get password if not provided
        if (!$password) {
            $password = $this->secret('Enter new password (or press Enter for default "admin123")');
            if (empty($password)) {
                $password = 'admin123';
                $this->info('Using default password: admin123');
            }
        }
        
        // Update password
        $admin->password = Hash::make($password);
        $admin->save();
        
        // Ensure user is active
        if (!$admin->is_active) {
            $admin->is_active = true;
            $admin->save();
            $this->info('✅ User activated');
        }
        
        // Ensure user has admin role
        if (!$admin->hasRole('admin')) {
            $admin->assignRole('admin');
            $this->info('✅ Admin role assigned');
        }
        
        $this->info('');
        $this->info('✅ Password reset successfully!');
        $this->info('📧 Email: ' . $admin->email);
        if ($admin->username) {
            $this->info('👤 Username: ' . $admin->username);
        }
        $this->info('🔑 Password: ' . $password);
        $this->info('');
        $this->info('You can now login at: ' . url('/login'));
        
        return 0;
    }
}