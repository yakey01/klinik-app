<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🔧 SIMPLE TINA DELETION FIX\n";
echo "===========================\n\n";

try {
    // Clear permission cache first
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    echo "✅ Permission cache cleared\n";
    
    // Get admin user
    $admin = \App\Models\User::where('username', 'admin')->first();
    if (!$admin) {
        echo "❌ Admin user not found\n";
        exit();
    }
    
    echo "✅ Admin user found: " . $admin->name . "\n";
    
    // Create and assign delete permissions directly
    echo "\n📋 Creating user deletion permissions...\n";
    
    $deletePermissions = ['delete_user', 'delete_any_user'];
    
    foreach ($deletePermissions as $permissionName) {
        // Create permission
        $permission = \Spatie\Permission\Models\Permission::firstOrCreate([
            'name' => $permissionName,
            'guard_name' => 'web'
        ]);
        
        // Direct database insert to avoid Spatie issues
        $exists = \DB::table('model_has_permissions')
            ->where('model_id', $admin->id)
            ->where('permission_id', $permission->id)
            ->where('model_type', 'App\Models\User')
            ->exists();
        
        if (!$exists) {
            \DB::table('model_has_permissions')->insert([
                'permission_id' => $permission->id,
                'model_type' => 'App\Models\User',
                'model_id' => $admin->id
            ]);
            echo "✅ Granted '$permissionName' to admin (direct DB)\n";
        } else {
            echo "✅ Permission '$permissionName' already exists for admin\n";
        }
    }
    
    // Clear cache again
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    
    // Verify permissions
    echo "\n🧪 Testing permissions...\n";
    
    // Refresh admin model
    $admin = $admin->fresh();
    
    foreach ($deletePermissions as $permissionName) {
        try {
            $hasPermission = $admin->hasPermissionTo($permissionName);
            echo "Permission '$permissionName': " . ($hasPermission ? '✅ YES' : '❌ NO') . "\n";
        } catch (Exception $e) {
            echo "Permission '$permissionName': ❌ ERROR - " . $e->getMessage() . "\n";
        }
    }
    
    // Find Tina
    echo "\n👤 Checking Tina user...\n";
    $tina = \App\Models\User::where('name', 'LIKE', '%Tina%')->first();
    
    if ($tina) {
        echo "✅ Tina found: " . $tina->name . " (ID: " . $tina->id . ")\n";
        echo "   Role: " . ($tina->role?->name ?? 'No role') . "\n";
        echo "   Username: " . ($tina->username ?? 'No username') . "\n";
        echo "   Email: " . ($tina->email ?? 'No email') . "\n";
    } else {
        echo "❌ Tina not found\n";
    }
    
    echo "\n🎯 DELETION INSTRUCTIONS:\n";
    echo "========================\n";
    echo "1. 🌐 Go to: https://dokterkuklinik.com/admin/users\n";
    echo "2. 🔍 Find 'Tina Paramedis' in the user list\n";
    echo "3. 🗑️  Click the actions menu (3 dots) in Tina's row\n";
    echo "4. ❌ Select 'Hapus User' (Delete User)\n";
    echo "5. ✅ Confirm the deletion\n";
    echo "\n💡 Key Points:\n";
    echo "   • Tina is a USER, not a PEGAWAI\n";
    echo "   • Look in User Management, not Pegawai Management\n";
    echo "   • Admin now has delete permissions\n";
    echo "   • Deletion will be permanent (or soft delete)\n";
    
    echo "\n🔧 Alternative: Manual deletion via database\n";
    echo "If the UI still doesn't work, run this command:\n";
    echo "php artisan tinker --execute=\"\\App\\Models\\User::where('name', 'LIKE', '%Tina%')->delete();\"\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}