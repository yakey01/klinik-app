<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

// Find paramedis role
$paramedisRole = Role::where('name', 'paramedis')->first();
if (!$paramedisRole) {
    echo "Error: paramedis role not found\n";
    exit;
}

// Check if tina user exists
$tinaUser = User::where('email', 'tina@paramedis.com')->first();
if (!$tinaUser) {
    // Create new user
    $tinaUser = User::create([
        'name' => 'Tina Paramedis',
        'email' => 'tina@paramedis.com',
        'password' => Hash::make('password123'),
        'role_id' => $paramedisRole->id
    ]);
    echo "Created tina user: {$tinaUser->email}\n";
} else {
    // Update existing user
    $tinaUser->role_id = $paramedisRole->id;
    $tinaUser->save();
    echo "Updated tina user role to paramedis\n";
}

echo "\nTest credentials:\n";
echo "Email: tina@paramedis.com\n";
echo "Password: password123\n";

// Verify the user has correct role
$testUser = User::with('role')->where('email', 'tina@paramedis.com')->first();
if ($testUser && $testUser->role) {
    echo "Role verification: {$testUser->role->name}\n";
} else {
    echo "Error: User role verification failed\n";
}