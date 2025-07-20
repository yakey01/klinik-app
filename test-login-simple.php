<?php

require_once 'vendor/autoload.php';

// Initialize Laravel
$app = require_once 'bootstrap/app.php';

// Bootstrap the application
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Hash;

// Test the doctor login functionality
echo "=== DOCTOR LOGIN TEST ===\n\n";

// Test 1: Check if doctor exists
try {
    $dokter = \App\Models\Dokter::where('username', 'yaya')->first();
    if (!$dokter) {
        echo "❌ Doctor 'yaya' not found\n";
        exit;
    }
    
    echo "✅ Doctor found:\n";
    echo "   Username: {$dokter->username}\n";
    echo "   Name: {$dokter->nama_lengkap}\n";
    echo "   Status: {$dokter->status_akun}\n";
    echo "   Has password: " . ($dokter->password ? 'Yes' : 'No') . "\n";
    echo "   Has linked user: " . ($dokter->user ? 'Yes' : 'No') . "\n\n";
    
    // Test 2: Check role
    $role = \App\Models\Role::where('name', 'dokter')->first();
    if (!$role) {
        echo "❌ Dokter role not found\n";
        exit;
    }
    echo "✅ Dokter role exists: {$role->name} (ID: {$role->id})\n\n";
    
    // Test 3: Try to create user for doctor if doesn't exist
    if (!$dokter->user) {
        echo "🔄 Creating User record for doctor...\n";
        
        $userEmail = $dokter->nik . '@dokter.local';
        
        // Check if user already exists
        $existingUser = \App\Models\User::where('email', $userEmail)->first();
        
        if (!$existingUser) {
            $user = \App\Models\User::create([
                'name' => $dokter->nama_lengkap,
                'username' => $dokter->username,
                'email' => $userEmail,
                'role_id' => $role->id,
                'is_active' => $dokter->aktif,
                'password' => $dokter->password,
            ]);
            
            // Update dokter with user_id
            $dokter->update(['user_id' => $user->id]);
            echo "✅ User created with ID: {$user->id}\n";
        } else {
            // Update existing user
            $existingUser->update([
                'name' => $dokter->nama_lengkap,
                'username' => $dokter->username,
                'role_id' => $role->id,
                'is_active' => $dokter->aktif,
            ]);
            $dokter->update(['user_id' => $existingUser->id]);
            echo "✅ Existing user updated: {$existingUser->id}\n";
        }
    }
    
    echo "\n🎯 Doctor login should now work!\n";
    echo "   Login URL: /login\n";
    echo "   Username: {$dokter->username}\n";
    echo "   Redirect: /dokter\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}