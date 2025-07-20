<?php

require_once 'vendor/autoload.php';

// Initialize Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

echo "=== DOCTOR AUTHENTICATION FLOW TEST ===\n\n";

// Test the actual authentication flow
$identifier = 'yaya';
$password = 'yaya'; // Test with 'yaya' as password

echo "Testing login with:\n";
echo "Username: $identifier\n";
echo "Password: $password\n\n";

// Step 1: Check User table first
$user = \App\Models\User::findForAuth($identifier);
echo "1. User table lookup: " . ($user ? "Found (ID: {$user->id})" : "Not found") . "\n";

// Step 2: Check Dokter table if not found in User
if (!$user) {
    echo "2. Checking Dokter table...\n";
    $dokter = \App\Models\Dokter::where('username', $identifier)
        ->whereNotNull('username')
        ->whereNotNull('password')
        ->where('status_akun', 'Aktif')
        ->first();
    
    if ($dokter) {
        echo "   ✅ Dokter found: {$dokter->nama_lengkap}\n";
        
        // Check password
        $passwordValid = Hash::check($password, $dokter->password);
        echo "   🔑 Password check: " . ($passwordValid ? "✅ VALID" : "❌ INVALID") . "\n";
        
        if ($passwordValid) {
            // Check if user exists or create one
            if (!$dokter->user) {
                echo "   🔄 Creating User record...\n";
                $role = \App\Models\Role::where('name', 'dokter')->first();
                
                $userEmail = $dokter->nik . '@dokter.local';
                $user = \App\Models\User::create([
                    'name' => $dokter->nama_lengkap,
                    'username' => $dokter->username,
                    'email' => $userEmail,
                    'role_id' => $role->id,
                    'is_active' => $dokter->aktif,
                    'password' => $dokter->password,
                ]);
                
                $dokter->update(['user_id' => $user->id]);
                echo "   ✅ User created with ID: {$user->id}\n";
            } else {
                $user = $dokter->user;
                echo "   ✅ Using existing User: {$user->id}\n";
            }
            
            // Test login
            Auth::login($user);
            echo "   ✅ Login successful!\n";
            echo "   🎯 User role: " . ($user->role ? $user->role->name : 'No role') . "\n";
        }
    } else {
        echo "   ❌ No dokter found with username '$identifier'\n";
    }
} else {
    echo "2. User already exists in User table\n";
}

// Final check
if (isset($user)) {
    echo "\n🎉 Doctor login setup complete!\n";
    echo "   Login URL: http://localhost:8000/login\n";
    echo "   Username: $identifier\n";
    echo "   Redirect: http://localhost:8000/dokter\n";
} else {
    echo "\n❌ Doctor login still failing\n";
}

echo "\n=== END TEST ===\n";