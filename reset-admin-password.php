<?php

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

echo "=== Reset Admin Password ===\n\n";

// Find admin user
$adminEmail = 'admin@dokterku.com';
$admin = User::where('email', $adminEmail)->first();

if (!$admin) {
    echo "❌ Admin user with email '{$adminEmail}' not found!\n";
    echo "\nDo you want to:\n";
    echo "1. Create a new admin user\n";
    echo "2. Search for existing admin users\n";
    echo "3. Exit\n";
    echo "\nEnter your choice (1-3): ";
    
    $choice = trim(fgets(STDIN));
    
    if ($choice == '1') {
        // Create new admin user
        echo "\nCreating new admin user...\n";
        
        // First ensure admin role exists
        $adminRole = \App\Models\Role::firstOrCreate([
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
        
        // Create new admin user
        $admin = User::create([
            'name' => 'Admin User',
            'email' => $adminEmail,
            'username' => 'admin',
            'password' => Hash::make('admin123'),
            'is_active' => true,
            'role_id' => $adminRole->id
        ]);
        
        // Assign Spatie role
        $admin->assignRole('admin');
        
        echo "✅ Admin user created successfully!\n";
        
    } elseif ($choice == '2') {
        // Find users with admin role
        $adminUsers = User::whereHas('roles', function($query) {
            $query->where('name', 'admin');
        })->get();
        
        if ($adminUsers->isEmpty()) {
            // Try another approach - check by role_id
            $adminRole = \App\Models\Role::where('name', 'admin')->first();
            if ($adminRole) {
                $adminUsers = User::where('role_id', $adminRole->id)->get();
            }
        }
        
        if ($adminUsers->isEmpty()) {
            echo "❌ No admin users found in the database!\n";
            exit(1);
        }
        
        echo "\nFound admin users:\n";
        foreach ($adminUsers as $user) {
            echo "- ID: {$user->id}, Name: {$user->name}, Email: {$user->email}, Username: {$user->username}\n";
        }
        
        echo "\nWhich user ID do you want to reset password for? Enter ID: ";
        $userId = trim(fgets(STDIN));
        $admin = User::find($userId);
        
        if (!$admin) {
            echo "❌ User with ID {$userId} not found!\n";
            exit(1);
        }
    } else {
        echo "Exiting...\n";
        exit(0);
    }
}

echo "\n✅ Found admin user: {$admin->name} ({$admin->email})\n";

// Ask for new password
echo "\nEnter new password (press Enter to use default 'admin123'): ";
$newPassword = trim(fgets(STDIN));

if (empty($newPassword)) {
    $newPassword = 'admin123';
    echo "Using default password: admin123\n";
}

// Set new password
$admin->password = Hash::make($newPassword);
$admin->save();

echo "\n✅ Password has been reset successfully!\n";
echo "📧 Email: {$admin->email}\n";
if (!empty($admin->username)) {
    echo "👤 Username: {$admin->username}\n";
}
echo "🔑 New Password: {$newPassword}\n";
echo "\nYou can now login with these credentials.\n";

// Verify the user has admin role
if (method_exists($admin, 'hasRole') && !$admin->hasRole('admin')) {
    echo "\n⚠️  Warning: This user doesn't have the admin role assigned via Spatie.\n";
    echo "Assigning admin role now...\n";
    $admin->assignRole('admin');
    echo "✅ Admin role assigned.\n";
}

// Check if user is active
if (isset($admin->is_active) && !$admin->is_active) {
    echo "\n⚠️  Warning: This user is not active. Activating now...\n";
    $admin->is_active = true;
    $admin->save();
    echo "✅ User activated.\n";
}

echo "\n=== Reset Complete ===\n";