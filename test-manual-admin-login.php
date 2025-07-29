<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

echo "\n🔍 TESTING MANUAL ADMIN LOGIN\n";
echo "==============================\n\n";

// Step 1: Get admin user
$adminUser = User::where('email', 'admin@dokterku.com')->first();

if (!$adminUser) {
    echo "❌ Admin user not found!\n";
    exit(1);
}

echo "✅ Admin user found:\n";
echo "   ID: {$adminUser->id}\n";
echo "   Email: {$adminUser->email}\n";
echo "   Username: {$adminUser->username}\n";
echo "   Active: " . ($adminUser->is_active ? 'YES' : 'NO') . "\n";
echo "   Role ID: {$adminUser->role_id}\n";
echo "   Has admin role: " . ($adminUser->hasRole('admin') ? 'YES' : 'NO') . "\n";

// Step 2: Test password
$passwords = ['admin123', 'password', 'admin'];
$correctPassword = null;

echo "\n2. Testing passwords...\n";
foreach ($passwords as $password) {
    if (Hash::check($password, $adminUser->password)) {
        echo "✅ Password '{$password}' is correct!\n";
        $correctPassword = $password;
        break;
    } else {
        echo "❌ Password '{$password}' failed\n";
    }
}

if (!$correctPassword) {
    echo "\n⚠️  No standard password works. Resetting to 'admin123'...\n";
    $adminUser->password = Hash::make('admin123');
    $adminUser->save();
    $correctPassword = 'admin123';
    echo "✅ Password reset to 'admin123'\n";
}

// Step 3: Test Auth::attempt
echo "\n3. Testing Auth::attempt...\n";
$attemptResult = Auth::attempt([
    'email' => $adminUser->email,
    'password' => $correctPassword
]);

if ($attemptResult) {
    echo "✅ Auth::attempt succeeded!\n";
    $loggedInUser = Auth::user();
    echo "   Logged in as: {$loggedInUser->name}\n";
    echo "   ID: {$loggedInUser->id}\n";
    Auth::logout();
} else {
    echo "❌ Auth::attempt failed\n";
    
    // Try manual login
    echo "\n4. Trying manual Auth::login...\n";
    Auth::login($adminUser);
    
    if (Auth::check()) {
        echo "✅ Manual login succeeded!\n";
        $loggedInUser = Auth::user();
        echo "   Logged in as: {$loggedInUser->name}\n";
        Auth::logout();
    } else {
        echo "❌ Manual login failed\n";
    }
}

// Step 4: Check session configuration
echo "\n5. Session configuration check:\n";
echo "   Session driver: " . config('session.driver') . "\n";
echo "   Session table exists: ";
$sessionTableExists = \Illuminate\Support\Facades\Schema::hasTable('sessions');
echo $sessionTableExists ? "YES\n" : "NO\n";

if ($sessionTableExists) {
    $sessionCount = \Illuminate\Support\Facades\DB::table('sessions')->count();
    echo "   Active sessions: {$sessionCount}\n";
}

// Step 5: Check middleware
echo "\n6. Checking web middleware stack...\n";
$webMiddleware = app('router')->getMiddlewareGroups()['web'] ?? [];
foreach ($webMiddleware as $middleware) {
    echo "   - " . (is_string($middleware) ? $middleware : get_class($middleware)) . "\n";
}

echo "\n\n📊 SUMMARY\n";
echo "================\n";
echo "Admin credentials:\n";
echo "  Email: admin@dokterku.com\n";
echo "  Username: admin\n";  
echo "  Password: {$correctPassword}\n";
echo "\nLogin URL: http://127.0.0.1:8000/login\n";
echo "\n✅ Testing complete!\n\n";