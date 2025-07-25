<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class CreateMissingPermissions extends Command
{
    protected $signature = 'permissions:create-missing';
    protected $description = 'Create missing permissions for the system';

    public function handle()
    {
        $this->info('Creating missing permissions...');

        // Define missing permissions needed by policies
        $missingPermissions = [
            'manage_finance' => 'Manage finance and financial records',
            'validate_transactions' => 'Validate financial transactions',
            'manage_staff' => 'Manage staff and employee records',
            'view_reports' => 'View system reports',
            'manage_schedules' => 'Manage work schedules',
            'manage_inventory' => 'Manage clinic inventory',
            'manage_patients' => 'Manage patient records',
            'manage_doctors' => 'Manage doctor records',
            'manage_settings' => 'Manage system settings',
            'backup_data' => 'Backup system data',
            'export_data' => 'Export system data',
        ];

        $created = 0;
        foreach ($missingPermissions as $name => $description) {
            $permission = Permission::firstOrCreate([
                'name' => $name,
                'guard_name' => 'web'
            ]);

            if ($permission->wasRecentlyCreated) {
                $this->info("Created permission: {$name}");
                $created++;
            } else {
                $this->line("Permission already exists: {$name}");
            }
        }

        // Assign finance permissions to admin and bendahara roles
        $this->assignPermissionsToRoles();

        $this->info("Created {$created} new permissions");
        return 0;
    }

    private function assignPermissionsToRoles()
    {
        $this->info('Assigning permissions to roles...');

        // Admin gets all permissions
        $adminRole = Role::where('name', 'admin')->first();
        if ($adminRole) {
            $adminRole->load('permissions'); // Load existing permissions first
            $adminRole->syncPermissions(Permission::all());
            $this->info('Assigned all permissions to admin role');
        }

        // Bendahara gets finance permissions
        $bendaharaRole = Role::where('name', 'bendahara')->first();
        if ($bendaharaRole) {
            $bendaharaRole->load('permissions'); // Load existing permissions first
            $financePermissions = [
                'manage_finance',
                'validate_transactions',
                'view_reports',
                'view_any_pendapatan',
                'create_pendapatan',
                'update_pendapatan',
                'delete_pendapatan',
                'view_pendapatan',
                'view_any_pengeluaran',
                'create_pengeluaran',
                'update_pengeluaran',
                'delete_pengeluaran',
                'view_pengeluaran',
            ];
            
            $permissions = Permission::whereIn('name', $financePermissions)->get();
            $bendaharaRole->syncPermissions($permissions);
            $this->info('Assigned finance permissions to bendahara role');
        }

        // Petugas gets basic permissions
        $petugasRole = Role::where('name', 'petugas')->first();
        if ($petugasRole) {
            $petugasRole->load('permissions'); // Load existing permissions first
            $basicPermissions = [
                'manage_patients',
                'view_any_pasien',
                'create_pasien',
                'update_pasien',
                'view_pasien',
            ];
            
            $permissions = Permission::whereIn('name', $basicPermissions)->get();
            $petugasRole->syncPermissions($permissions);
            $this->info('Assigned basic permissions to petugas role');
        }
    }
}