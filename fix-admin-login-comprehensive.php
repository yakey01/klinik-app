<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use App\Models\User;
use App\Models\Role;

echo "\n🔧 COMPREHENSIVE ADMIN LOGIN FIX - WORLD CLASS AUDITOR\n";
echo "======================================================\n\n";

$issues = [];
$fixes = [];

// 1. Database Connection
echo "1️⃣ DATABASE CONNECTION CHECK\n";
try {
    DB::connection()->getPdo();
    echo "✅ Database connected successfully\n";
} catch (\Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
    $issues[] = "Database connection failed";
    exit(1);
}

// 2. Roles Table
echo "\n2️⃣ ROLES TABLE CHECK\n";
if (!Schema::hasTable('roles')) {
    echo "❌ Roles table missing! Creating...\n";
    Schema::create('roles', function ($table) {
        $table->id();
        $table->string('name')->unique();
        $table->string('display_name')->nullable();
        $table->string('description')->nullable();
        $table->timestamps();
    });
    echo "✅ Roles table created\n";
    $fixes[] = "Created roles table";
} else {
    echo "✅ Roles table exists\n";
}

// 3. Admin Role
echo "\n3️⃣ ADMIN ROLE CHECK\n";
$adminRole = Role::where('name', 'admin')->first();
if (!$adminRole) {
    echo "❌ Admin role missing! Creating...\n";
    $adminRole = Role::create([
        'name' => 'admin',
        'display_name' => 'Administrator',
        'description' => 'Full system access'
    ]);
    echo "✅ Admin role created\n";
    $fixes[] = "Created admin role";
} else {
    echo "✅ Admin role exists (ID: {$adminRole->id})\n";
}

// 4. Admin User
echo "\n4️⃣ ADMIN USER CHECK\n";
$adminUser = User::where('email', 'admin@dokterku.com')->first();

if (!$adminUser) {
    echo "❌ Admin user missing! Creating...\n";
    $adminUser = User::create([
        'name' => 'Administrator',
        'email' => 'admin@dokterku.com',
        'username' => 'admin',
        'password' => Hash::make('admin123'),
        'role_id' => $adminRole->id,
        'is_active' => 1,
        'email_verified_at' => now()
    ]);
    echo "✅ Admin user created\n";
    $fixes[] = "Created admin user";
} else {
    echo "✅ Admin user exists\n";
    // Update to ensure correct settings
    $adminUser->update([
        'role_id' => $adminRole->id,
        'is_active' => 1,
        'password' => Hash::make('admin123')
    ]);
    echo "✅ Admin user updated\n";
    $fixes[] = "Updated admin user password and settings";
}

// 5. Session Configuration
echo "\n5️⃣ SESSION CONFIGURATION CHECK\n";
$sessionDriver = config('session.driver');
echo "   Current driver: {$sessionDriver}\n";

if ($sessionDriver === 'database') {
    if (!Schema::hasTable('sessions')) {
        echo "❌ Sessions table missing! Run: php artisan session:table && php artisan migrate\n";
        $issues[] = "Sessions table missing";
    } else {
        echo "✅ Sessions table exists\n";
        // Clear old sessions
        DB::table('sessions')->where('user_id', $adminUser->id)->delete();
        echo "✅ Cleared old admin sessions\n";
        $fixes[] = "Cleared old sessions";
    }
}

// 6. CSRF Token Verification
echo "\n6️⃣ CSRF TOKEN CONFIGURATION CHECK\n";
$csrfMiddleware = app()->make(\App\Http\Middleware\VerifyCsrfToken::class);
$reflection = new ReflectionClass($csrfMiddleware);
$property = $reflection->getProperty('except');
$property->setAccessible(true);
$csrfExcept = $property->getValue($csrfMiddleware);

echo "   CSRF exceptions: " . implode(', ', $csrfExcept) . "\n";
if (in_array('login', $csrfExcept)) {
    echo "✅ Login route excluded from CSRF\n";
} else {
    echo "⚠️  Login route NOT excluded from CSRF (this is normal)\n";
}

// 7. Authentication Test
echo "\n7️⃣ AUTHENTICATION TEST\n";
$testResult = \Illuminate\Support\Facades\Auth::attempt([
    'email' => 'admin@dokterku.com',
    'password' => 'admin123'
]);

if ($testResult) {
    echo "✅ Authentication test PASSED\n";
    $user = \Illuminate\Support\Facades\Auth::user();
    echo "   Logged in as: {$user->name}\n";
    echo "   Has admin role: " . ($user->hasRole('admin') ? 'YES' : 'NO') . "\n";
    \Illuminate\Support\Facades\Auth::logout();
} else {
    echo "❌ Authentication test FAILED\n";
    $issues[] = "Authentication test failed";
}

// 8. Routes Check
echo "\n8️⃣ ROUTES CHECK\n";
$routes = [
    'login' => 'Login page',
    'unified.login' => 'Login handler',
    'admin' => 'Admin dashboard'
];

foreach ($routes as $name => $desc) {
    try {
        $url = route($name);
        echo "✅ Route '{$name}' exists: {$url}\n";
    } catch (\Exception $e) {
        echo "❌ Route '{$name}' missing ({$desc})\n";
        $issues[] = "Route '{$name}' missing";
    }
}

// 9. Middleware Check
echo "\n9️⃣ MIDDLEWARE CHECK\n";
$webMiddleware = app('router')->getMiddlewareGroups()['web'] ?? [];
$requiredMiddleware = [
    \Illuminate\Session\Middleware\StartSession::class,
    \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
];

foreach ($requiredMiddleware as $middleware) {
    $found = false;
    foreach ($webMiddleware as $m) {
        if ($m === $middleware || (is_object($m) && get_class($m) === $middleware)) {
            $found = true;
            break;
        }
    }
    echo $found ? "✅" : "❌";
    echo " {$middleware}\n";
    if (!$found) {
        $issues[] = "Missing middleware: {$middleware}";
    }
}

// 10. Clear Caches
echo "\n🔟 CLEARING CACHES\n";
\Illuminate\Support\Facades\Artisan::call('cache:clear');
echo "✅ Cache cleared\n";
\Illuminate\Support\Facades\Artisan::call('config:clear');
echo "✅ Config cleared\n";
\Illuminate\Support\Facades\Artisan::call('view:clear');
echo "✅ Views cleared\n";
$fixes[] = "Cleared all caches";

// SUMMARY
echo "\n\n📊 AUDIT SUMMARY\n";
echo "================\n\n";

if (empty($issues)) {
    echo "✅ NO ISSUES FOUND - System is ready!\n\n";
} else {
    echo "❌ ISSUES FOUND:\n";
    foreach ($issues as $issue) {
        echo "   - {$issue}\n";
    }
    echo "\n";
}

if (!empty($fixes)) {
    echo "✅ FIXES APPLIED:\n";
    foreach ($fixes as $fix) {
        echo "   - {$fix}\n";
    }
    echo "\n";
}

echo "📋 ADMIN LOGIN CREDENTIALS:\n";
echo "   URL: http://127.0.0.1:8000/login\n";
echo "   Email: admin@dokterku.com\n";
echo "   Username: admin\n";
echo "   Password: admin123\n\n";

echo "🔧 TROUBLESHOOTING STEPS:\n";
echo "1. Clear browser cache and cookies\n";
echo "2. Try incognito/private browsing mode\n";
echo "3. Check browser console for JavaScript errors\n";
echo "4. Ensure APP_URL in .env matches your access URL\n";
echo "5. Check storage/logs/laravel.log for errors\n";
echo "6. Run: php artisan optimize:clear\n\n";

echo "✅ Comprehensive fix completed!\n\n";