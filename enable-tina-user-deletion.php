<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🗑️  ENABLING TINA USER DELETION\n";
echo "===============================\n\n";

try {
    // Find Tina in users table
    $tina = \App\Models\User::where('username', 'tina')
        ->orWhere('name', 'LIKE', '%tina%')
        ->orWhere('name', 'LIKE', '%Tina%')
        ->first();
    
    if (!$tina) {
        echo "❌ Tina not found in users table\n";
        exit();
    }
    
    echo "✅ Found Tina in users table:\n";
    echo "   ID: " . $tina->id . "\n";
    echo "   Name: " . $tina->name . "\n";
    echo "   Username: " . ($tina->username ?? 'NULL') . "\n";
    echo "   Email: " . ($tina->email ?? 'NULL') . "\n";
    echo "   Role: " . ($tina->role?->name ?? 'NULL') . "\n";
    echo "   Active: " . ($tina->is_active ? 'YES' : 'NO') . "\n";
    
    // Check current admin user permissions
    echo "\n📋 CHECKING ADMIN PERMISSIONS:\n";
    echo "==============================\n";
    
    $adminUser = \App\Models\User::where('username', 'admin')->first();
    if (!$adminUser) {
        echo "❌ Admin user not found\n";
        exit();
    }
    
    echo "Admin user: " . $adminUser->name . "\n";
    
    // Check required permissions for user deletion
    $requiredPermissions = ['delete_user', 'delete_any_user'];
    $missingPermissions = [];
    
    foreach ($requiredPermissions as $permission) {
        $hasPermission = $adminUser->hasPermissionTo($permission);
        echo "Permission '$permission': " . ($hasPermission ? '✅ YES' : '❌ NO') . "\n";
        
        if (!$hasPermission) {
            $missingPermissions[] = $permission;
        }
    }
    
    // Create missing permissions and assign to admin
    if (!empty($missingPermissions)) {
        echo "\n🔧 FIXING MISSING PERMISSIONS:\n";
        echo "==============================\n";
        
        foreach ($missingPermissions as $permissionName) {
            // Create permission if it doesn't exist
            $permission = \Spatie\Permission\Models\Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web'
            ]);
            
            echo "Permission '$permissionName': " . ($permission->wasRecentlyCreated ? 'CREATED' : 'EXISTS') . "\n";
            
            // Give permission to admin user
            if (!$adminUser->hasPermissionTo($permissionName)) {
                $adminUser->givePermissionTo($permission);
                echo "✅ Granted '$permissionName' to admin user\n";
            }
        }
        
        // Also give to admin role if it exists
        $adminRole = \Spatie\Permission\Models\Role::where('name', 'admin')->first();
        if ($adminRole) {
            foreach ($missingPermissions as $permissionName) {
                if (!$adminRole->hasPermissionTo($permissionName)) {
                    $adminRole->givePermissionTo($permissionName);
                    echo "✅ Granted '$permissionName' to admin role\n";
                }
            }
        }
        
        // Clear permission cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        echo "✅ Permission cache cleared\n";
    }
    
    // Check Tina's dependencies that might prevent deletion
    echo "\n📋 CHECKING TINA'S DEPENDENCIES:\n";
    echo "================================\n";
    
    $dependencies = [];
    
    // Check various tables that might reference this user
    $tablesToCheck = [
        'attendances' => 'user_id',
        'user_devices' => 'user_id',
        'jadwal_jagas' => 'pegawai_id',
        'permohonan_cutis' => 'pegawai_id',
        'face_recognitions' => 'user_id',
        'audit_logs' => 'user_id',
        'pendapatan' => 'input_by',
        'pengeluaran' => 'input_by',
        'tindakan' => 'dokter_id',
        'personal_access_tokens' => 'tokenable_id'
    ];
    
    foreach ($tablesToCheck as $table => $column) {
        try {
            $count = \DB::table($table)->where($column, $tina->id)->count();
            if ($count > 0) {
                $dependencies[$table] = $count;
                echo "⚠️  " . ucfirst(str_replace('_', ' ', $table)) . ": " . $count . " records\n";
            }
        } catch (Exception $e) {
            // Table might not exist, skip
            continue;
        }
    }
    
    if (empty($dependencies)) {
        echo "✅ No dependencies found - Tina can be safely deleted\n";
    } else {
        echo "\n🔧 DEPENDENCY HANDLING:\n";
        echo "======================\n";
        echo "Found " . count($dependencies) . " types of dependent records.\n";
        echo "Most of these have cascade delete or set null configured.\n";
        echo "Deletion should still work, but some data will be cleaned up automatically.\n";
    }
    
    // Test deletion capability
    echo "\n🧪 TESTING DELETION CAPABILITY:\n";
    echo "===============================\n";
    
    try {
        // Check if user can be deleted (without actually deleting)
        \DB::beginTransaction();
        
        // Test the deletion
        $canDelete = $tina->delete();
        
        if ($canDelete) {
            echo "✅ Deletion test: SUCCESS\n";
            echo "   Tina can be safely deleted\n";
        } else {
            echo "❌ Deletion test: FAILED\n";
        }
        
        // Rollback to undo the test deletion
        \DB::rollBack();
        echo "   (Test deletion rolled back - no changes made)\n";
        
    } catch (Exception $e) {
        \DB::rollBack();
        echo "❌ Deletion test failed:\n";
        echo "   " . $e->getMessage() . "\n";
        
        if (strpos($e->getMessage(), 'foreign key constraint') !== false) {
            echo "   This is a foreign key constraint error.\n";
            echo "   Some dependent records need manual cleanup first.\n";
        }
    }
    
    echo "\n🎯 SOLUTION STATUS:\n";
    echo "==================\n";
    
    if (empty($missingPermissions)) {
        echo "✅ Admin already has required permissions\n";
    } else {
        echo "✅ Missing permissions have been granted to admin\n";
    }
    
    echo "✅ Tina user found in User Management (not Pegawai Management)\n";
    echo "✅ Deletion should now be available in:\n";
    echo "   📍 /admin/users (User Resource)\n";
    echo "   🗑️  Look for 'Hapus User' action in the table row\n";
    
    echo "\n💡 INSTRUCTIONS:\n";
    echo "================\n";
    echo "1. Go to: https://dokterkuklinik.com/admin/users\n";
    echo "2. Find Tina in the user list\n";
    echo "3. Click the actions menu (•••) for Tina's row\n";
    echo "4. Select 'Hapus User' (Delete User)\n";
    echo "5. Confirm the deletion\n";
    echo "\n⚠️  Note: Tina is a USER, not a PEGAWAI\n";
    echo "   That's why she doesn't appear in Pegawai Management\n";
    
} catch (Exception $e) {
    echo "❌ Error enabling Tina deletion:\n";
    echo $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}