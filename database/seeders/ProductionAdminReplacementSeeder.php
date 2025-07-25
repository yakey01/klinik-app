<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Role;

class ProductionAdminReplacementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Only run in production environment
        if (!app()->environment('production')) {
            $this->command->info('ProductionAdminReplacement: Skipping in ' . app()->environment() . ' environment');
            Log::info('ProductionAdminReplacement: Seeder skipped in non-production environment');
            return;
        }

        $this->command->info('🚀 Starting Production Admin Replacement...');
        Log::info('ProductionAdminReplacement: Starting admin user creation process');

        DB::beginTransaction();

        try {
            // Step 1: Ensure admin role exists
            $adminRole = $this->ensureAdminRoleExists();

            // Step 2: Create the new admin user from localhost specifications
            $newAdmin = $this->createLocalhostAdmin($adminRole);

            // Step 3: Verify admin panel access
            $this->verifyAdminAccess($newAdmin);

            // Step 4: Log the successful creation
            $this->logAdminReplacement($newAdmin, 'success');

            DB::commit();

            $this->command->info('✅ Production Admin Replacement completed successfully!');
            $this->command->info('📧 New Admin Email: ' . $newAdmin->email);
            $this->command->info('🔐 Admin can now access: https://dokterkuklinik.com/admin');
            
            Log::info('ProductionAdminReplacement: Admin replacement completed successfully', [
                'admin_email' => $newAdmin->email,
                'admin_id' => $newAdmin->id,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            $this->command->error('❌ Production Admin Replacement failed: ' . $e->getMessage());
            Log::error('ProductionAdminReplacement: Failed to create admin user', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Log the failure
            $this->logAdminReplacement(null, 'failed', $e->getMessage());
            
            throw $e;
        }
    }

    /**
     * Ensure admin role exists with all necessary permissions
     */
    private function ensureAdminRoleExists(): Role
    {
        $this->command->info('🔧 Ensuring admin role exists...');

        // Define comprehensive admin permissions
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
        $adminRole = Role::firstOrCreate([
            'name' => 'admin'
        ], [
            'display_name' => 'Administrator',
            'description' => 'System administrator with full access - Created by localhost admin replacement',
            'permissions' => $adminPermissions,
            'is_active' => true,
            'guard_name' => 'web'
        ]);

        // Create admin role in Spatie system if Spatie is installed
        if (class_exists('\Spatie\Permission\Models\Role')) {
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
            $spatieAdmin->syncPermissions($adminPermissions);
            
            $this->command->info('✅ Spatie admin role and permissions configured');
        }

        $this->command->info('✅ Admin role ensured with ' . count($adminPermissions) . ' permissions');
        return $adminRole;
    }

    /**
     * Create the new admin user based on localhost specifications
     */
    private function createLocalhostAdmin(Role $adminRole): User
    {
        $this->command->info('👤 Creating new admin user...');

        // Get admin credentials from environment or use secure defaults
        $adminEmail = env('PRODUCTION_ADMIN_EMAIL', 'admin@dokterku.com');
        $adminPassword = env('PRODUCTION_ADMIN_PASSWORD', 'dokterku_admin_2024');
        $adminName = env('PRODUCTION_ADMIN_NAME', 'Administrator');
        $adminUsername = env('PRODUCTION_ADMIN_USERNAME', 'admin');

        // Ensure no existing admin with same email (cleanup any soft deleted)
        User::withTrashed()->where('email', $adminEmail)->forceDelete();

        // Create the new admin user
        $newAdmin = User::create([
            'name' => $adminName,
            'email' => $adminEmail,
            'username' => $adminUsername,
            'password' => Hash::make($adminPassword),
            'role_id' => $adminRole->id,
            'is_active' => true,
            'email_verified_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Assign Spatie role if available
        if (method_exists($newAdmin, 'assignRole')) {
            $newAdmin->assignRole('admin');
        }

        $this->command->info('✅ Created admin user: ' . $newAdmin->email);
        return $newAdmin;
    }

    /**
     * Verify admin panel access
     */
    private function verifyAdminAccess(User $admin): void
    {
        $this->command->info('🔍 Verifying admin panel access...');

        // Check if user can access admin panel
        if (method_exists($admin, 'canAccessPanel')) {
            $adminPanel = filament('admin')->getPanel();
            $canAccess = $admin->canAccessPanel($adminPanel);
            
            if ($canAccess) {
                $this->command->info('✅ Admin panel access verified');
            } else {
                throw new \Exception('Admin user cannot access admin panel');
            }
        }

        // Verify role assignment
        if ($admin->role_id && $admin->role->name === 'admin') {
            $this->command->info('✅ Admin role assignment verified');
        } else {
            throw new \Exception('Admin role not properly assigned');
        }

        // Verify Spatie permissions if available
        if (method_exists($admin, 'hasRole') && $admin->hasRole('admin')) {
            $this->command->info('✅ Spatie admin role verified');
        }
    }

    /**
     * Log the admin replacement process
     */
    private function logAdminReplacement(?User $admin, string $status, ?string $errorMessage = null): void
    {
        try {
            DB::table('admin_replacement_logs')->insert([
                'action' => 'create_production_admin',
                'status' => $status,
                'details' => json_encode([
                    'admin_id' => $admin?->id,
                    'admin_email' => $admin?->email,
                    'admin_name' => $admin?->name,
                    'error_message' => $errorMessage,
                    'timestamp' => now()->toISOString(),
                    'environment' => app()->environment(),
                    'seeder' => self::class,
                ]),
                'user_agent' => 'ProductionAdminReplacementSeeder',
                'ip_address' => 'system',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Exception $e) {
            // Don't fail the whole process if logging fails
            Log::error('Failed to log admin replacement: ' . $e->getMessage());
        }
    }
}