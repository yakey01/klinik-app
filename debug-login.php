<?php

require_once 'vendor/autoload.php';

// Initialize Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== DEBUG DOCTOR LOGIN ISSUE ===\n\n";

// Check the actual authentication flow
$identifier = 'yaya';
echo "Debugging login for username: $identifier\n\n";

// Check what exists
$user = \App\Models\User::where('username', $identifier)->first();
$dokter = \App\Models\Dokter::where('username', $identifier)->first();

if ($user) {
    echo "✅ User found in User table:\n";
    echo "   ID: {$user->id}\n";
    echo "   Email: {$user->email}\n";
    echo "   Username: {$user->username}\n";
    echo "   Role: " . ($user->role ? $user->role->name : 'None') . "\n";
    echo "   Active: " . ($user->is_active ? 'Yes' : 'No') . "\n";
    echo "   Has password: " . ($user->password ? 'Yes' : 'No') . "\n";
    echo "\n";
}

if ($dokter) {
    echo "✅ Doctor found in Dokter table:\n";
    echo "   ID: {$dokter->id}\n";
    echo "   Name: {$dokter->nama_lengkap}\n";
    echo "   Username: {$dokter->username}\n";
    echo "   Status: {$dokter->status_akun}\n";
    echo "   Active: " . ($dokter->aktif ? 'Yes' : 'No') . "\n";
    echo "   Has password: " . ($dokter->password ? 'Yes' : 'No') . "\n";
    echo "   Linked to User: " . ($dokter->user ? 'Yes (ID: ' . $dokter->user->id . ')' : 'No') . "\n";
    echo "\n";
}

// Test different passwords
echo "Testing password verification:\n";
$passwords = ['yaya', 'password', '123456', 'dokter123'];

foreach ($passwords as $pwd) {
    echo "Testing '$pwd':\n";
    
    // Test against User table
    if ($user) {
        $userValid = \Illuminate\Support\Facades\Hash::check($pwd, $user->password);
        echo "   User table: " . ($userValid ? "✅ VALID" : "❌ INVALID") . "\n";
    }
    
    // Test against Dokter table
    if ($dokter) {
        $dokterValid = \Illuminate\Support\Facades\Hash::check($pwd, $dokter->password);
        echo "   Dokter table: " . ($dokterValid ? "✅ VALID" : "❌ INVALID") . "\n";
    }
    echo "\n";
}

echo "=== RECOMMENDATIONS ===\n";
if ($user && $dokter) {
    echo "1. Try logging in with username 'yaya' and password 'yaya'\n";
    echo "2. If that fails, the password might be different\n";
    echo "3. Check the actual password in the Dokter table\n";
    echo "4. Use password reset if needed\n";
} else {
    echo "❌ Either user or doctor record is missing\n";
}

echo "\n=== END DEBUG ===\n";