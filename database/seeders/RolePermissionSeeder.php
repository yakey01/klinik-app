<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use App\Models\Role;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        // Clear cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // User Management
            'view-users',
            'create-users',
            'edit-users',
            'delete-users',
            'manage-roles',
            
            // Patient Management
            'view-patients',
            'create-patients',
            'edit-patients',
            'delete-patients',
            
            // Medical Procedures
            'view-procedures',
            'create-procedures',
            'edit-procedures',
            'delete-procedures',
            'perform-procedures',
            
            // Financial Management
            'view-finances',
            'create-finances',
            'edit-finances',
            'delete-finances',
            'approve-finances',
            'reject-finances',
            
            // Service Fees (Jaspel)
            'view-jaspel',
            'create-jaspel',
            'edit-jaspel',
            'delete-jaspel',
            'approve-jaspel',
            
            // Sitting Allowance (Uang Duduk)
            'view-uang-duduk',
            'create-uang-duduk',
            'edit-uang-duduk',
            'delete-uang-duduk',
            'approve-uang-duduk',
            
            // Reports
            'view-reports',
            'generate-reports',
            'export-reports',
            
            // System Administration
            'view-system-logs',
            'manage-system-settings',
            'backup-system',
            'restore-system',
            
            // Dashboard Access
            'admin-dashboard',
            'manager-dashboard',
            'treasurer-dashboard',
            'staff-dashboard',
            'doctor-dashboard',
            'paramedic-dashboard',
            'non-paramedic-dashboard',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Create roles with permissions
        $roles = [
            'admin' => [
                'display_name' => 'Administrator',
                'description' => 'Full system access and user management',
                'permissions' => $permissions, // All permissions
            ],
            'manajer' => [
                'display_name' => 'Manager', 
                'description' => 'Manage operations and view reports',
                'permissions' => [
                    'view-users', 'view-patients', 'view-procedures', 'view-finances',
                    'view-jaspel', 'view-uang-duduk', 'view-reports', 'generate-reports',
                    'export-reports', 'manager-dashboard', 'approve-finances', 'approve-jaspel',
                    'approve-uang-duduk', 'create-patients', 'edit-patients',
                ],
            ],
            'bendahara' => [
                'display_name' => 'Treasurer',
                'description' => 'Financial management and reporting',
                'permissions' => [
                    'view-finances', 'create-finances', 'edit-finances', 'view-jaspel',
                    'create-jaspel', 'edit-jaspel', 'view-uang-duduk', 'create-uang-duduk',
                    'edit-uang-duduk', 'view-reports', 'generate-reports', 'export-reports',
                    'treasurer-dashboard', 'view-patients', 'view-procedures',
                ],
            ],
            'petugas' => [
                'display_name' => 'Staff',
                'description' => 'Administrative staff with limited access',
                'permissions' => [
                    'view-patients', 'create-patients', 'edit-patients', 'view-procedures',
                    'create-procedures', 'edit-procedures', 'view-finances', 'create-finances',
                    'staff-dashboard', 'view-jaspel', 'view-uang-duduk',
                ],
            ],
            'dokter' => [
                'display_name' => 'Doctor',
                'description' => 'Medical professional with patient care access',
                'permissions' => [
                    'view-patients', 'create-patients', 'edit-patients', 'view-procedures',
                    'create-procedures', 'edit-procedures', 'perform-procedures',
                    'doctor-dashboard', 'view-jaspel', 'view-reports',
                ],
            ],
            'paramedis' => [
                'display_name' => 'Paramedic',
                'description' => 'Medical support staff',
                'permissions' => [
                    'view-patients', 'create-patients', 'edit-patients', 'view-procedures',
                    'create-procedures', 'edit-procedures', 'perform-procedures',
                    'paramedic-dashboard', 'view-jaspel',
                ],
            ],
            'non_paramedis' => [
                'display_name' => 'Non-Paramedic',
                'description' => 'Support staff for medical procedures',
                'permissions' => [
                    'view-patients', 'view-procedures', 'create-procedures',
                    'edit-procedures', 'non-paramedic-dashboard', 'view-jaspel',
                ],
            ],
        ];

        foreach ($roles as $roleName => $roleData) {
            $role = Role::updateOrCreate(
                ['name' => $roleName, 'guard_name' => 'web'],
                [
                    'display_name' => $roleData['display_name'],
                    'description' => $roleData['description'],
                    'is_active' => true,
                ]
            );

            // Clear existing permissions and assign new ones
            $role->permissions()->detach();
            foreach ($roleData['permissions'] as $permissionName) {
                $permission = Permission::where('name', $permissionName)->first();
                if ($permission) {
                    $role->permissions()->attach($permission);
                }
            }
        }
    }
}