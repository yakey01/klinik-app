<?php
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make('Illuminate\\Contracts\\Console\\Kernel');
$kernel->bootstrap();

echo "=== DEBUG AUTHENTICATION ===" . PHP_EOL;

try {
    // Check if user exists
    $user = DB::table('users')->where('email', 'tina@paramedis.com')->first();
    if ($user) {
        echo "✅ User exists:" . PHP_EOL;
        echo "   ID: {$user->id}" . PHP_EOL;
        echo "   Name: {$user->name}" . PHP_EOL;
        echo "   Email: {$user->email}" . PHP_EOL;
        echo "   Role ID: {$user->role_id}" . PHP_EOL;
        echo "   Email verified: " . ($user->email_verified_at ? 'YES' : 'NO') . PHP_EOL;
        echo "   Password hash starts with: " . substr($user->password, 0, 10) . "..." . PHP_EOL;
        
        // Check role
        $role = DB::table('roles')->where('id', $user->role_id)->first();
        if ($role) {
            echo "✅ Role: {$role->name} (ID: {$role->id})" . PHP_EOL;
        } else {
            echo "❌ Role not found for role_id: {$user->role_id}" . PHP_EOL;
        }
        
        // Test password verification
        $passwordCheck = Hash::check('password123', $user->password);
        echo "Password verification: " . ($passwordCheck ? 'PASS ✅' : 'FAIL ❌') . PHP_EOL;
        
        if (!$passwordCheck) {
            echo "❌ Password hash verification failed!" . PHP_EOL;
            echo "Creating new hash for comparison..." . PHP_EOL;
            $newHash = Hash::make('password123');
            echo "New hash starts with: " . substr($newHash, 0, 10) . "..." . PHP_EOL;
            $newCheck = Hash::check('password123', $newHash);
            echo "New hash verification: " . ($newCheck ? 'PASS' : 'FAIL') . PHP_EOL;
        }
        
    } else {
        echo "❌ User with email 'tina@paramedis.com' not found!" . PHP_EOL;
    }
    
    // Test Laravel Auth attempt
    echo PHP_EOL . "Testing Laravel Auth::attempt()..." . PHP_EOL;
    $authResult = Auth::attempt(['email' => 'tina@paramedis.com', 'password' => 'password123']);
    echo "Auth::attempt result: " . ($authResult ? 'SUCCESS ✅' : 'FAILED ❌') . PHP_EOL;
    
    if ($authResult) {
        $authUser = Auth::user();
        echo "Authenticated user: {$authUser->email}" . PHP_EOL;
        Auth::logout();
    } else {
        echo "❌ Laravel authentication failed" . PHP_EOL;
        
        // Check auth configuration
        echo PHP_EOL . "Checking auth configuration..." . PHP_EOL;
        echo "Auth guard: " . config('auth.defaults.guard') . PHP_EOL;
        echo "Auth provider: " . config('auth.defaults.provider') . PHP_EOL;
        echo "User model: " . config('auth.providers.users.model') . PHP_EOL;
        
        // Check if email is case sensitive issue
        $userCaseInsensitive = DB::table('users')->whereRaw('LOWER(email) = ?', [strtolower('tina@paramedis.com')])->first();
        if ($userCaseInsensitive) {
            echo "Found user with case-insensitive search: {$userCaseInsensitive->email}" . PHP_EOL;
        }
    }
    
    // List all users to see what we have
    echo PHP_EOL . "All users in database:" . PHP_EOL;
    $allUsers = DB::table('users')
        ->leftJoin('roles', 'users.role_id', '=', 'roles.id')
        ->select('users.id', 'users.name', 'users.email', 'roles.name as role_name')
        ->get();
        
    foreach ($allUsers as $u) {
        echo "   ID: {$u->id}, Email: {$u->email}, Role: {$u->role_name}" . PHP_EOL;
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . PHP_EOL;
    echo "File: " . $e->getFile() . ":" . $e->getLine() . PHP_EOL;
}

echo PHP_EOL . "=== DEBUG COMPLETE ===" . PHP_EOL;
?>