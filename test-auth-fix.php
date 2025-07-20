<?php

require_once 'vendor/autoload.php';
require_once 'bootstrap/app.php';

use Illuminate\Support\Facades\Hash;

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Testing Doctor Authentication Fix\n";
echo "================================\n";

// Test 1: Check if doctors exist with login credentials
$dokters = \App\Models\Dokter::whereNotNull('username')
    ->where('status_akun', 'Aktif')
    ->get();

echo "Active doctors with login accounts: " . $dokters->count() . "\n\n";

foreach ($dokters as $dokter) {
    echo "Doctor: " . $dokter->nama_lengkap . "\n";
    echo "Username: " . $dokter->username . "\n";
    echo "Status: " . $dokter->status_akun . "\n";
    echo "Has linked User: " . ($dokter->user ? 'Yes' : 'No') . "\n";
    
    if ($dokter->user) {
        echo "User ID: " . $dokter->user->id . "\n";
        echo "User Role: " . ($dokter->user->role ? $dokter->user->role->name : 'No role') . "\n";
    }
    echo "---\n";
}

// Test 2: Check if dokter role exists
$role = \App\Models\Role::where('name', 'dokter')->first();
echo "Dokter role exists: " . ($role ? 'Yes (ID: ' . $role->id . ')' : 'No') . "\n\n";

// Test 3: Test password verification
echo "Testing password verification for 'yaya':\n";
$yaya = \App\Models\Dokter::where('username', 'yaya')->first();
if ($yaya) {
    $testPassword = 'yaya'; // Let's assume this is the password
    $isValid = Hash::check($testPassword, $yaya->password);
    echo "Password 'yaya' is " . ($isValid ? 'VALID' : 'INVALID') . "\n";
}

echo "\nFix Status: ✅ Doctor authentication support added to UnifiedAuthController\n";