<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use Spatie\Permission\Models\Role;

try {
    echo "Fixing manajer role assignment...\n";
    
    // Find the user
    $user = User::where('email', 'tina@manajer.com')->first();
    if (!$user) {
        echo "User not found!\n";
        exit(1);
    }
    
    echo "Found user: {$user->name}\n";
    
    // Create or find the manajer role
    $role = Role::firstOrCreate([
        'name' => 'manajer',
        'guard_name' => 'web'
    ]);
    
    echo "Role 'manajer' ready\n";
    
    // Remove any existing roles and assign manajer
    $user->syncRoles(['manajer']);
    
    echo "Assigned manajer role to user\n";
    
    // Verify
    $hasRole = $user->hasRole('manajer');
    echo "User has manajer role: " . ($hasRole ? 'YES' : 'NO') . "\n";
    
    if ($hasRole) {
        echo "✅ Success! User can now login to manajer panel\n";
    } else {
        echo "❌ Failed to assign role\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}