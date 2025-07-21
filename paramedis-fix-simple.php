<?php
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make('Illuminate\\Contracts\\Console\\Kernel');
$kernel->bootstrap();

echo "=== PARAMEDIS FIX ===" . PHP_EOL;

try {
    // Get or create role
    $role = DB::table('roles')->where('name', 'paramedis')->first();
    if (!$role) {
        $roleId = DB::table('roles')->insertGetId([
            'name' => 'paramedis',
            'display_name' => 'Paramedic',
            'guard_name' => 'web',
            'created_at' => now(),
            'updated_at' => now()
        ]);
        $role = (object)['id' => $roleId];
        echo "Created role: $roleId" . PHP_EOL;
    } else {
        echo "Role exists: {$role->id}" . PHP_EOL;
    }

    // Delete existing user
    DB::table('users')->where('email', 'tina@paramedis.com')->delete();
    echo "Deleted existing user" . PHP_EOL;

    // Create new user
    $userId = DB::table('users')->insertGetId([
        'name' => 'Tina Paramedis',
        'email' => 'tina@paramedis.com',
        'email_verified_at' => now(),
        'password' => Hash::make('password123'),
        'role_id' => $role->id,
        'created_at' => now(),
        'updated_at' => now()
    ]);
    echo "Created user: $userId" . PHP_EOL;

    // Test auth
    $auth = Auth::attempt(['email' => 'tina@paramedis.com', 'password' => 'password123']);
    echo "Auth: " . ($auth ? 'SUCCESS' : 'FAILED') . PHP_EOL;
    if ($auth) Auth::logout();

    // Clear caches
    Artisan::call('cache:clear');
    Artisan::call('config:clear');

    echo "=== FIX COMPLETE ===" . PHP_EOL;
    echo "Login at: " . config('app.url') . "/paramedis/login" . PHP_EOL;
    echo "Email: tina@paramedis.com" . PHP_EOL;
    echo "Password: password123" . PHP_EOL;

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . PHP_EOL;
}
?>