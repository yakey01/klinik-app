<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin permissions
        $adminPermissions = [
            'view_admin_panel',
            'view_any_user', 'view_user', 'create_user', 'update_user', 'delete_user', 'delete_any_user',
            'view_any_role', 'view_role', 'create_role', 'update_role', 'delete_role', 'delete_any_role',
            'view_any_pasien', 'view_pasien', 'create_pasien', 'update_pasien', 'delete_pasien', 'delete_any_pasien',
            'view_any_tindakan', 'view_tindakan', 'create_tindakan', 'update_tindakan', 'delete_tindakan', 'delete_any_tindakan',
            'view_any_pendapatan', 'view_pendapatan', 'create_pendapatan', 'update_pendapatan', 'delete_pendapatan', 'delete_any_pendapatan',
            'view_any_pengeluaran', 'view_pengeluaran', 'create_pengeluaran', 'update_pengeluaran', 'delete_pengeluaran', 'delete_any_pengeluaran',
            'view_any_work_location', 'view_work_location', 'create_work_location', 'update_work_location', 'delete_work_location', 'delete_any_work_location',
            'view_clinic_stats_widget', 'view_financial_summary_widget', 'view_financial_chart_widget', 'view_attendance_overview_widget',
            'view_settings_page', 'view_gps_attendance_features_page',
            'manage_users', 'manage_roles', 'view_reports', 'manage_finance', 'validate_transactions', 'export_data'
        ];

        // Create admin role in custom roles table
        $adminRole = \App\Models\Role::firstOrCreate([
            'name' => 'admin'
        ], [
            'display_name' => 'Administrator',
            'description' => 'System administrator with full access',
            'permissions' => $adminPermissions,
            'is_active' => true,
            'guard_name' => 'web'
        ]);

        // Create admin role in Spatie system
        $spatieAdmin = \Spatie\Permission\Models\Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'web'
        ]);

        // Create permissions first
        foreach ($adminPermissions as $permission) {
            \Spatie\Permission\Models\Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web'
            ]);
        }
        
        // Assign all permissions to admin role
        $spatieAdmin->givePermissionTo($adminPermissions);

        // Create admin user - only run in development
        if (app()->environment(['local', 'development'])) {
            $admin = \App\Models\User::firstOrCreate([
                'email' => 'admin@dokterku.com'
            ], [
                'name' => 'Admin User',
                'password' => bcrypt(env('DEFAULT_ADMIN_PASSWORD', 'dokterku_admin_2024')),
                'is_active' => true,
                'role_id' => $adminRole->id
            ]);

            // Assign Spatie role to admin user
            $admin->assignRole('admin');

            $this->command->info('Admin user created: ' . $admin->email);
            $this->command->info('Admin can access admin panel: ' . ($admin->canAccessPanel(filament('admin')->getPanel()) ? 'YES' : 'NO'));
        } else {
            $this->command->info('Admin user creation skipped in production environment');
            $this->command->info('Please create admin user manually with: php artisan make:admin-user');
        }
    }
}
