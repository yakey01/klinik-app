<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class DokterPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🔐 Creating DOKTER permissions...');

        // Define permissions for DOKTER role
        $dokterPermissions = [
            // Dashboard permissions
            'view-dashboard',
            'view-dashboard-stats',
            
            // Patient management
            'view-patients',
            'manage-patient-queue',
            'call-patients',
            
            // Schedule management
            'view-schedules',
            'manage-schedules',
            'add-schedule',
            'edit-schedule',
            'delete-schedule',
            
            // Attendance permissions
            'view-attendance',
            'manage-attendance',
            'checkin-attendance',
            'checkout-attendance',
            'view-attendance-history',
            
            // Jaspel (Service Fee) permissions
            'view-jaspel',
            'manage-jaspel',
            'view-jaspel-stats',
            'view-jaspel-breakdown',
            
            // Medical procedures
            'view-procedures',
            'manage-procedures',
            'record-procedures',
            'edit-procedures',
            
            // Reports and analytics
            'view-reports',
            'generate-reports',
            'export-reports',
            'view-analytics',
            
            // Profile management
            'view-profile',
            'edit-profile',
            'change-password',
            
            // Presensi (specific to Indonesian system)
            'view-presensi',
            'manage-presensi',
            'presensi-checkin',
            'presensi-checkout',
            
            // Dokter specific permissions
            'view-dokter-dashboard',
            'manage-dokter-schedule',
            'view-dokter-patients',
            'manage-dokter-procedures',
            'view-dokter-jaspel',
            'view-dokter-reports',
            
            // System access
            'access-dokter-panel',
            'view-dokter-mobile',
            'use-dokter-api',
        ];

        // Create permissions if they don't exist
        foreach ($dokterPermissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web'
            ]);
        }

        $this->command->info('✅ Created ' . count($dokterPermissions) . ' permissions');

        // Get or create DOKTER role
        $dokterRole = Role::firstOrCreate([
            'name' => 'dokter',
            'guard_name' => 'web'
        ], [
            'display_name' => 'Dokter',
            'description' => 'Role untuk dokter umum dan spesialis',
            'is_active' => true,
        ]);

        // Assign all permissions to DOKTER role using direct database operations
        // Clear existing permissions first
        $dokterRole->permissions()->detach();
        
        // Get permission IDs and attach them
        $permissionIds = Permission::whereIn('name', $dokterPermissions)->pluck('id');
        $dokterRole->permissions()->attach($permissionIds);
        
        $this->command->info('✅ Assigned all permissions to DOKTER role');

        // Also create permissions for DOKTER GIGI if needed
        $dokterGigiRole = Role::firstOrCreate([
            'name' => 'dokter_gigi',
            'guard_name' => 'web'
        ], [
            'display_name' => 'Dokter Gigi',
            'description' => 'Role untuk dokter gigi',
            'is_active' => true,
        ]);

        // Dokter Gigi gets most of the same permissions
        $dokterGigiPermissions = array_filter($dokterPermissions, function($perm) {
            // Exclude some general dokter permissions
            return !in_array($perm, ['view-dokter-dashboard', 'manage-dokter-schedule']);
        });

        // Add specific dokter gigi permissions
        $dokterGigiSpecific = [
            'view-dokter-gigi-dashboard',
            'manage-dokter-gigi-schedule',
            'view-dental-procedures',
            'manage-dental-procedures',
        ];

        foreach ($dokterGigiSpecific as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web'
            ]);
        }

        $allDokterGigiPermissions = array_merge($dokterGigiPermissions, $dokterGigiSpecific);
        
        // Assign permissions using direct database operations
        $dokterGigiRole->permissions()->detach(); // Clear existing permissions
        $dokterGigiPermissionIds = Permission::whereIn('name', $allDokterGigiPermissions)->pluck('id');
        $dokterGigiRole->permissions()->attach($dokterGigiPermissionIds);
        
        $this->command->info('✅ Assigned permissions to DOKTER GIGI role');

        // Display summary
        $this->command->info('');
        $this->command->info('📋 Permission Summary:');
        $this->command->info('   DOKTER role: ' . count($dokterPermissions) . ' permissions');
        $this->command->info('   DOKTER_GIGI role: ' . count($allDokterGigiPermissions) . ' permissions');
        $this->command->info('');
        $this->command->info('🔑 Key permissions created:');
        $this->command->info('   • view-procedures ✅');
        $this->command->info('   • view-dashboard ✅');
        $this->command->info('   • manage-attendance ✅');
        $this->command->info('   • view-jaspel ✅');
        $this->command->info('   • access-dokter-panel ✅');
    }
}