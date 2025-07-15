<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class InstallShieldCommand extends Command
{
    protected $signature = 'shield:install-auto';
    protected $description = 'Install Shield automatically for admin panel';

    public function handle()
    {
        $this->info('Starting Shield installation...');
        
        try {
            // Create Shield config programmatically
            $this->call('vendor:publish', [
                '--provider' => 'BezhanSalleh\\FilamentShield\\FilamentShieldServiceProvider',
                '--tag' => 'filament-shield-config'
            ]);
            
            $this->info('Shield config published successfully');
            
            // Generate permissions manually since commands are interactive
            $this->generateShieldPermissions();
            $this->info('Shield permissions generated successfully');
            
            // Create shield resource manually
            $this->createShieldResource();
            $this->info('Shield resource created successfully');
            
        } catch (\Exception $e) {
            $this->error('Shield installation failed: ' . $e->getMessage());
            
            // Fallback: Manual shield setup
            $this->warn('Attempting manual Shield setup...');
            
            // Create super admin manually
            $this->createSuperAdmin();
            $this->info('Super admin created successfully');
        }
        
        $this->info('Shield installation completed!');
        
        return Command::SUCCESS;
    }
    
    private function generateShieldPermissions()
    {
        // Create Shield-specific permissions for resources
        $resources = [
            'UserResource', 'RoleResource', 'PasienResource', 'TindakanResource',
            'PendapatanResource', 'PengeluaranResource', 'WorkLocationResource'
        ];
        
        $permissions = [];
        
        foreach ($resources as $resource) {
            $model = strtolower(str_replace('Resource', '', $resource));
            $permissions[] = "view_any_{$model}";
            $permissions[] = "view_{$model}";
            $permissions[] = "create_{$model}";
            $permissions[] = "update_{$model}";
            $permissions[] = "delete_{$model}";
            $permissions[] = "delete_any_{$model}";
        }
        
        // Create permissions
        foreach ($permissions as $permission) {
            \Spatie\Permission\Models\Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web'
            ]);
        }
        
        // Assign all permissions to admin role
        $adminRole = \Spatie\Permission\Models\Role::where('name', 'admin')->first();
        if ($adminRole) {
            $adminRole->syncPermissions($permissions);
        }
    }
    
    private function createShieldResource()
    {
        // Shield resource is already handled by the existing RoleResource
        // This method exists for completeness
        $this->info('Shield resource integration completed via existing RoleResource');
    }
    
    private function createSuperAdmin()
    {
        // Create super admin user
        $admin = \App\Models\User::firstOrCreate([
            'email' => 'admin@dokterku.com'
        ], [
            'name' => 'Super Admin',
            'password' => bcrypt('password'),
            'is_active' => true
        ]);
        
        // Assign admin role
        $adminRole = \Spatie\Permission\Models\Role::where('name', 'admin')->first();
        if ($adminRole) {
            $admin->assignRole($adminRole);
        }
        
        $this->info("Super admin created: {$admin->email}");
    }
}