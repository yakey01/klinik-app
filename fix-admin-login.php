<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Role;

echo "\n🔍 COMPREHENSIVE ADMIN LOGIN FIX\n";
echo "===================================\n\n";

// Step 1: Check admin role exists
echo "1. Checking admin role...\n";
$adminRole = Role::where('name', 'admin')->first();

if (!$adminRole) {
    echo "❌ Admin role not found. Creating...\n";
    $adminRole = Role::create([
        'name' => 'admin',
        'display_name' => 'Administrator',
        'description' => 'System Administrator with full access'
    ]);
    echo "✅ Admin role created with ID: {$adminRole->id}\n";
} else {
    echo "✅ Admin role exists with ID: {$adminRole->id}\n";
}

// Step 2: Check admin user
echo "\n2. Checking admin user...\n";
$adminUser = User::where('email', 'admin@dokterku.com')->first();

if (!$adminUser) {
    echo "❌ Admin user not found. Creating...\n";
    $adminUser = User::create([
        'name' => 'Administrator',
        'email' => 'admin@dokterku.com',
        'username' => 'admin',
        'password' => Hash::make('admin123'), // Default password
        'role_id' => $adminRole->id,
        'is_active' => 1,
        'email_verified_at' => now()
    ]);
    echo "✅ Admin user created:\n";
    echo "   Email: admin@dokterku.com\n";
    echo "   Username: admin\n";
    echo "   Password: admin123\n";
} else {
    echo "✅ Admin user exists. Updating...\n";
    
    // Update admin user
    $adminUser->update([
        'name' => 'Administrator',
        'username' => 'admin',
        'role_id' => $adminRole->id,
        'is_active' => 1,
        'password' => Hash::make('admin123')
    ]);
    
    echo "✅ Admin user updated:\n";
    echo "   ID: {$adminUser->id}\n";
    echo "   Email: {$adminUser->email}\n";
    echo "   Username: {$adminUser->username}\n";
    echo "   Password: admin123\n";
}

// Step 3: Clear sessions
echo "\n3. Clearing existing sessions...\n";
DB::table('sessions')->where('user_id', $adminUser->id)->delete();
echo "✅ Sessions cleared\n";

// Step 4: Check middleware and routes
echo "\n4. Checking authentication routes...\n";
$routes = [
    'GET /login' => 'login page',
    'POST /login' => 'login process',
    'GET /admin' => 'admin dashboard'
];

foreach ($routes as $route => $desc) {
    $routeExists = app('router')->getRoutes()->match(
        app('request')->create($route, explode(' ', $route)[0])
    );
    echo $routeExists ? "✅" : "❌";
    echo " Route {$route} ({$desc})\n";
}

// Step 5: Test password verification
echo "\n5. Testing password verification...\n";
$testPassword = 'admin123';
$passwordVerified = Hash::check($testPassword, $adminUser->fresh()->password);
echo $passwordVerified ? "✅" : "❌";
echo " Password verification: " . ($passwordVerified ? "SUCCESS" : "FAILED") . "\n";

// Step 6: Check CSRF settings
echo "\n6. Checking CSRF configuration...\n";
$csrfExcept = config('app.csrf_except', []);
echo "CSRF exceptions configured: " . (in_array('login', $csrfExcept) ? "YES" : "NO") . "\n";

// Step 7: Session configuration
echo "\n7. Session configuration:\n";
echo "   Driver: " . config('session.driver') . "\n";
echo "   Lifetime: " . config('session.lifetime') . " minutes\n";
echo "   Cookie: " . config('session.cookie') . "\n";
echo "   Domain: " . (config('session.domain') ?: 'not set') . "\n";
echo "   Secure: " . (config('session.secure') ? 'YES' : 'NO') . "\n";
echo "   Same Site: " . config('session.same_site') . "\n";

// Step 8: Database connection
echo "\n8. Database check:\n";
try {
    DB::connection()->getPdo();
    echo "✅ Database connected\n";
    
    $userCount = User::count();
    $sessionCount = DB::table('sessions')->count();
    echo "   Total users: {$userCount}\n";
    echo "   Active sessions: {$sessionCount}\n";
} catch (\Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
}

// Step 9: Test login programmatically
echo "\n9. Testing programmatic login...\n";
try {
    \Illuminate\Support\Facades\Auth::login($adminUser);
    $loggedIn = \Illuminate\Support\Facades\Auth::check();
    echo $loggedIn ? "✅" : "❌";
    echo " Programmatic login: " . ($loggedIn ? "SUCCESS" : "FAILED") . "\n";
    
    if ($loggedIn) {
        $currentUser = \Illuminate\Support\Facades\Auth::user();
        echo "   Logged in as: {$currentUser->name} ({$currentUser->email})\n";
        echo "   Has admin role: " . ($currentUser->hasRole('admin') ? 'YES' : 'NO') . "\n";
    }
    
    \Illuminate\Support\Facades\Auth::logout();
} catch (\Exception $e) {
    echo "❌ Login test error: " . $e->getMessage() . "\n";
}

// Summary
echo "\n\n📊 SUMMARY\n";
echo "================\n";
echo "Admin login credentials:\n";
echo "  Email/Username: admin@dokterku.com or admin\n";
echo "  Password: admin123\n";
echo "\nLogin URL: http://127.0.0.1:8000/login\n";
echo "\nIf login still fails, check:\n";
echo "1. Clear browser cache and cookies\n";
echo "2. Try incognito/private browsing mode\n";
echo "3. Check Laravel logs: storage/logs/laravel.log\n";
echo "4. Ensure APP_URL in .env matches your access URL\n";
echo "5. Run: php artisan cache:clear && php artisan config:clear\n";

echo "\n✅ Admin login fix completed!\n\n";