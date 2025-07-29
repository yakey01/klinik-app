<?php
require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🛡️ MIDDLEWARE DEBUG TEST\n";
echo "========================\n\n";

// Login as Naning
$user = \App\Models\User::find(13);
Auth::login($user);

echo "1. 👤 USER AUTHENTICATION:\n";
echo "   ✅ Logged in as: " . Auth::user()->name . "\n";
echo "   🔑 User ID: " . Auth::user()->id . "\n";
echo "   🎭 Role: " . Auth::user()->role->name . "\n";
echo "   🛡️ hasRole('paramedis'): " . (Auth::user()->hasRole('paramedis') ? 'TRUE' : 'FALSE') . "\n";

echo "\n2. 🔍 MIDDLEWARE SIMULATION:\n";

// Create a request that simulates the mobile app route
$request = \Illuminate\Http\Request::create('/paramedis/mobile-app', 'GET');
$request->setUserResolver(function () {
    return Auth::user();
});

// Test the middleware manually
$middleware = new \App\Http\Middleware\RoleMiddleware();

echo "   🔄 Testing RoleMiddleware->handle()...\n";

try {
    $response = $middleware->handle($request, function ($req) {
        return response('Success: Mobile app accessible');
    }, 'paramedis');
    
    $content = $response->getContent();
    $statusCode = $response->getStatusCode();
    
    echo "   📊 Response Status: $statusCode\n";
    
    if ($statusCode === 200) {
        echo "   ✅ Middleware passed successfully\n";
        echo "   📱 Response: $content\n";
    } else {
        echo "   ❌ Middleware blocked access\n";
        echo "   📱 Response: $content\n";
        
        // Check if it's a redirect
        if ($response instanceof \Illuminate\Http\RedirectResponse) {
            echo "   🔄 Redirect URL: " . $response->getTargetUrl() . "\n";
        }
    }
    
} catch (\Exception $e) {
    echo "   ❌ Middleware error: " . $e->getMessage() . "\n";
    echo "   📚 Stack: " . $e->getTraceAsString() . "\n";
}

echo "\n3. 🔍 STEP-BY-STEP MIDDLEWARE LOGIC:\n";

// Manually test each step of the middleware
echo "   Step 1 - Auth::check(): " . (Auth::check() ? 'PASS' : 'FAIL') . "\n";

if (Auth::check()) {
    $user = Auth::user();
    echo "   Step 2 - Get user: SUCCESS\n";
    echo "   Step 3 - Role check: paramedis\n";
    
    $hasRole = false;
    $roles = ['paramedis'];
    
    foreach ($roles as $role) {
        echo "   Step 4 - Checking role '$role'...\n";
        if ($user->hasRole($role)) {
            echo "     ✅ User has role '$role'\n";
            $hasRole = true;
            break;
        } else {
            echo "     ❌ User does NOT have role '$role'\n";
        }
    }
    
    echo "   Step 5 - Final hasRole result: " . ($hasRole ? 'PASS' : 'FAIL') . "\n";
    
    if (!$hasRole) {
        echo "   Step 6 - Would redirect to: " . $middleware->getRedirectUrl($user, $roles) . "\n";
    }
}

echo "\n4. 🌐 WEB CONTEXT SIMULATION:\n";

// Test if there are any session or web-specific issues
session_start();
$_SESSION['user_id'] = $user->id;

echo "   📋 Session ID: " . session_id() . "\n";
echo "   🔐 Session user_id: " . ($_SESSION['user_id'] ?? 'None') . "\n";

// Check if the web.php route has any additional middleware
echo "\n5. 📍 ROUTE MIDDLEWARE CHECK:\n";

try {
    $router = app('router');
    $routes = $router->getRoutes();
    
    foreach ($routes as $route) {
        if ($route->getName() === 'paramedis.mobile-app') {
            echo "   ✅ Route found: " . $route->getName() . "\n";
            echo "   🔐 Middleware: " . implode(', ', $route->middleware()) . "\n";
            echo "   📍 URI: " . $route->uri() . "\n";
            break;
        }
    }
} catch (\Exception $e) {
    echo "   ❌ Route check error: " . $e->getMessage() . "\n";
}

echo "\n========================\n";
echo "🎯 MIDDLEWARE DEBUG COMPLETE\n";