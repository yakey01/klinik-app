<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🔧 Creating remaining users...\n";

// Create paramedis role if it doesn't exist
$paramedisRole = App\Models\Role::firstOrCreate(['name' => 'paramedis'], [
    'name' => 'paramedis',
    'description' => 'Paramedis role',
    'is_active' => true
]);

// Check if user already exists
$existingUser = App\Models\User::where('email', 'naning@dokterku.com')->first();
if (!$existingUser) {
    $paramedisUser = App\Models\User::create([
        'name' => 'Naning Paramedis',
        'username' => 'naning_paramedis',
        'email' => 'naning@dokterku.com',
        'password' => bcrypt('password123'),
        'role_id' => $paramedisRole->id,
        'is_active' => true,
    ]);
    
    echo "✅ Paramedis user created:\n";
    echo "   Email: naning@dokterku.com\n";
    echo "   Username: naning_paramedis\n";
    echo "   Password: password123\n";
    echo "   ID: {$paramedisUser->id}\n";
} else {
    echo "✅ Paramedis user already exists: ID {$existingUser->id}\n";
}

// List all users for verification
echo "\n📋 All users in system:\n";
$allUsers = App\Models\User::with('role')->get();
foreach ($allUsers as $user) {
    echo "   ID {$user->id}: {$user->name} ({$user->email}) - Role: " . ($user->role ? $user->role->name : 'No role') . "\n";
}

echo "\n🎉 All users ready for login testing!\n";
echo "\n📝 Login credentials:\n";
echo "   Admin: admin@dokterku.com / password123\n";
echo "   Bendahara: bendahara@dokterku.com / password123\n";
echo "   Paramedis: naning@dokterku.com / password123\n";