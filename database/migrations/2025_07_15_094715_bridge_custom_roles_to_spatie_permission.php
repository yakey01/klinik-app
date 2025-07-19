<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role as SpatieRole;
use App\Models\Role as CustomRole;
use App\Models\User;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Clear cached permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Step 1: Extract all unique permissions from custom roles
        $customRoles = CustomRole::whereNotNull('permissions')->get();
        $allPermissions = [];
        
        foreach ($customRoles as $role) {
            if ($role->permissions && is_array($role->permissions)) {
                $allPermissions = array_merge($allPermissions, $role->permissions);
            }
        }
        
        $uniquePermissions = array_unique($allPermissions);
        
        // Step 2: Create Spatie Permission entries
        foreach ($uniquePermissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web'
            ]);
        }

        // Step 3: Create comprehensive permissions for Filament resources
        $filamentPermissions = [
            // Admin panel access
            'view_admin_panel',
            
            // User management
            'view_any_user',
            'view_user', 
            'create_user',
            'update_user',
            'delete_user',
            'delete_any_user',
            
            // Role management (for Shield integration)
            'view_any_role',
            'view_role',
            'create_role', 
            'update_role',
            'delete_role',
            'delete_any_role',
            
            // Patient management
            'view_any_pasien',
            'view_pasien',
            'create_pasien',
            'update_pasien', 
            'delete_pasien',
            'delete_any_pasien',
            
            // Medical procedures
            'view_any_tindakan',
            'view_tindakan',
            'create_tindakan',
            'update_tindakan',
            'delete_tindakan', 
            'delete_any_tindakan',
            
            // Financial management
            'view_any_pendapatan',
            'view_pendapatan',
            'create_pendapatan',
            'update_pendapatan',
            'delete_pendapatan',
            'delete_any_pendapatan',
            
            'view_any_pengeluaran',
            'view_pengeluaran', 
            'create_pengeluaran',
            'update_pengeluaran',
            'delete_pengeluaran',
            'delete_any_pengeluaran',
            
            // System management
            'view_any_work_location',
            'view_work_location',
            'create_work_location',
            'update_work_location',
            'delete_work_location',
            'delete_any_work_location',
            
            // Dashboard widgets
            'view_clinic_stats_widget',
            'view_financial_summary_widget',
            'view_financial_chart_widget',
            'view_attendance_overview_widget',
            
            // Settings pages
            'view_settings_page',
            'view_gps_attendance_features_page',
        ];
        
        foreach ($filamentPermissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web'
            ]);
        }

        // Step 4: Migrate custom roles to Spatie roles and assign permissions
        $rolePermissionMapping = [
            'admin' => array_merge($uniquePermissions, $filamentPermissions), // Admin gets all permissions
            'manajer' => [
                'view_admin_panel',
                'view_any_user', 'view_user',
                'view_any_pasien', 'view_pasien', 'create_pasien', 'update_pasien',
                'view_any_tindakan', 'view_tindakan',
                'view_any_pendapatan', 'view_pendapatan',
                'view_any_pengeluaran', 'view_pengeluaran',
                'view_clinic_stats_widget', 'view_financial_summary_widget', 'view_financial_chart_widget',
                'view_reports', 'validate_transactions', 'manage_finance', 'view_analytics', 'export_data'
            ],
            'bendahara' => [
                'view_admin_panel',
                'view_any_pendapatan', 'view_pendapatan', 'create_pendapatan', 'update_pendapatan',
                'view_any_pengeluaran', 'view_pengeluaran', 'create_pengeluaran', 'update_pengeluaran',
                'view_any_pasien', 'view_pasien',
                'view_any_tindakan', 'view_tindakan',
                'view_financial_summary_widget', 'view_financial_chart_widget',
                'manage_finance', 'validate_transactions', 'view_reports', 'manage_expenses', 'manage_income'
            ],
            'petugas' => [
                'view_any_pasien', 'view_pasien', 'create_pasien', 'update_pasien',
                'view_any_tindakan', 'view_tindakan', 'create_tindakan', 'update_tindakan',
                'view_any_pendapatan', 'view_pendapatan', 'create_pendapatan',
                'input_transactions', 'view_own_data', 'manage_patients'
            ],
            'paramedis' => [
                'view_any_pasien', 'view_pasien', 'create_pasien', 'update_pasien',
                'view_any_tindakan', 'view_tindakan', 'create_tindakan', 'update_tindakan',
                'input_medical_actions', 'view_own_data', 'manage_patients'
            ],
            'dokter' => [
                'view_any_pasien', 'view_pasien', 'create_pasien', 'update_pasien',
                'view_any_tindakan', 'view_tindakan', 'create_tindakan', 'update_tindakan',
                'input_medical_actions', 'view_own_data', 'manage_patients', 'view_medical_reports'
            ],
            'non_paramedis' => [
                'view_any_pasien', 'view_pasien',
                'view_any_tindakan', 'view_tindakan', 'create_tindakan', 'update_tindakan',
                'input_support_actions', 'view_own_data'
            ]
        ];

        foreach ($customRoles as $customRole) {
            // Delete any existing Spatie role with the same name first
            SpatieRole::where('name', $customRole->name)->delete();
            
            // Create new Spatie role using direct database insert to avoid fillable issues
            $spatieRoleId = DB::table('roles')->insertGetId([
                'name' => $customRole->name,
                'guard_name' => 'web',
                'display_name' => $customRole->display_name,
                'description' => $customRole->description,
                'is_active' => $customRole->is_active,
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            $spatieRole = SpatieRole::find($spatieRoleId);
            
            // Get permissions for this role
            $permissions = $rolePermissionMapping[$customRole->name] ?? [];
            
            // Assign permissions one by one to avoid conflicts
            foreach ($permissions as $permissionName) {
                $permission = Permission::where('name', $permissionName)->first();
                if ($permission) {
                    // Use direct database insert to avoid ORM conflicts
                    DB::table('role_has_permissions')->insertOrIgnore([
                        'role_id' => $spatieRole->id,
                        'permission_id' => $permission->id
                    ]);
                }
            }
            
            echo "Migrated role: {$customRole->name} with " . count($permissions) . " permissions\n";
        }

        // Step 5: Migrate user role assignments from custom system to Spatie
        // Use query builder to avoid soft delete issues if users table doesn't have deleted_at
        $users = DB::table('users')
            ->whereNotNull('role_id')
            ->get();
        
        foreach ($users as $user) {
            if ($user->role_id) {
                // Get the custom role by ID using query builder
                $customRole = DB::table('roles')->where('id', $user->role_id)->first();
                
                if ($customRole) {
                    // Find corresponding Spatie role
                    $spatieRole = SpatieRole::where('name', $customRole->name)->first();
                    
                    if ($spatieRole) {
                        // Use direct database insert to avoid ORM conflicts
                        DB::table('model_has_roles')->insertOrIgnore([
                            'role_id' => $spatieRole->id,
                            'model_type' => 'App\\Models\\User',
                            'model_id' => $user->id
                        ]);
                        echo "Assigned role '{$spatieRole->name}' to user: {$user->email}\n";
                    }
                }
            }
        }

        echo "Role migration completed successfully!\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Clear all Spatie role assignments
        DB::table('model_has_roles')->truncate();
        DB::table('role_has_permissions')->truncate();
        
        // Delete Spatie permissions and roles
        SpatieRole::truncate();
        Permission::truncate();
        
        echo "Reverted Spatie Permission data. Custom roles remain intact.\n";
    }
};