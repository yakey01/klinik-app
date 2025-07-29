<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\User;

echo "\n🌐 BROWSER LOGIN TEST INSTRUCTIONS\n";
echo "===================================\n\n";

// Check admin user
$adminUser = User::where('email', 'admin@dokterku.com')->first();

if (!$adminUser) {
    echo "❌ Admin user not found! Please run fix-admin-login-comprehensive.php first.\n";
    exit(1);
}

echo "✅ Admin user found and ready\n";
echo "   Email: {$adminUser->email}\n";
echo "   Username: {$adminUser->username}\n";
echo "   Password: admin123\n";
echo "   Active: " . ($adminUser->is_active ? 'YES' : 'NO') . "\n\n";

// Clear sessions
DB::table('sessions')->where('user_id', $adminUser->id)->delete();
echo "✅ Cleared any existing sessions\n\n";

echo "📋 STEP-BY-STEP LOGIN INSTRUCTIONS:\n";
echo "====================================\n\n";

echo "1. CLEAR BROWSER DATA:\n";
echo "   - Press Cmd+Shift+Delete (Mac) or Ctrl+Shift+Delete (Windows)\n";
echo "   - Select 'Cookies and other site data'\n";
echo "   - Select 'Cached images and files'\n";
echo "   - Click 'Clear data'\n\n";

echo "2. OPEN INCOGNITO/PRIVATE WINDOW:\n";
echo "   - Chrome: Cmd+Shift+N (Mac) or Ctrl+Shift+N (Windows)\n";
echo "   - Safari: Cmd+Shift+N\n";
echo "   - Firefox: Cmd+Shift+P (Mac) or Ctrl+Shift+P (Windows)\n\n";

echo "3. NAVIGATE TO LOGIN:\n";
echo "   URL: http://127.0.0.1:8000/login\n\n";

echo "4. ENTER CREDENTIALS:\n";
echo "   Email/Username: admin@dokterku.com atau admin\n";
echo "   Password: admin123\n\n";

echo "5. TROUBLESHOOTING:\n";
echo "   - If you see '419 Page Expired':\n";
echo "     • Refresh the page (F5)\n";
echo "     • Try again with the same credentials\n";
echo "   - If login fails:\n";
echo "     • Check browser console (F12) for errors\n";
echo "     • Check Network tab for failed requests\n\n";

echo "6. EXPECTED RESULT:\n";
echo "   - You should be redirected to: /admin\n";
echo "   - This is the Filament admin panel\n\n";

// Check current configuration
echo "📊 CURRENT CONFIGURATION:\n";
echo "========================\n";
echo "APP_URL: " . config('app.url') . "\n";
echo "Session Driver: " . config('session.driver') . "\n";
echo "Session Cookie: " . config('session.cookie') . "\n";
echo "Session Domain: " . (config('session.domain') ?: 'not set') . "\n";
echo "CSRF Protection: " . (class_exists(\App\Http\Middleware\VerifyCsrfToken::class) ? 'ENABLED' : 'DISABLED') . "\n";
echo "PreventPageExpired Middleware: " . (class_exists(\App\Http\Middleware\PreventPageExpired::class) ? 'INSTALLED' : 'NOT FOUND') . "\n\n";

// Test routes
echo "🛣️  ROUTES CHECK:\n";
echo "================\n";
$routes = [
    '/login' => 'Login Page',
    '/refresh-csrf' => 'CSRF Refresh Endpoint',
    '/admin' => 'Admin Panel'
];

foreach ($routes as $path => $name) {
    $exists = false;
    try {
        $routeCollection = app('router')->getRoutes();
        foreach ($routeCollection as $route) {
            if ($route->uri() === ltrim($path, '/')) {
                $exists = true;
                break;
            }
        }
    } catch (\Exception $e) {
        // Route not found
    }
    
    echo $exists ? "✅" : "❌";
    echo " {$path} ({$name})\n";
}

echo "\n✅ Ready for browser testing!\n\n";