<?php
require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);

// Create login request
$loginData = [
    'email' => 'naning@dokterku.com',
    'password' => 'password', // You might need to set this password first
    '_token' => \Illuminate\Support\Str::random(40)
];

$request = \Illuminate\Http\Request::create('/login', 'POST', $loginData, [], [], [
    'HTTP_ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
    'HTTP_USER_AGENT' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36',
]);

// Start session
$request->setLaravelSession($app['session.store']);

try {
    echo "🔐 Attempting to login as naning@dokterku.com...\n";
    
    // Check if user exists and can be authenticated
    $user = \App\Models\User::where('email', 'naning@dokterku.com')->first();
    if (!$user) {
        echo "❌ User not found\n";
        exit(1);
    }
    
    echo "✅ User found: {$user->name}\n";
    echo "🔑 Role: " . json_encode($user->role) . "\n";
    
    // Check if user has correct role
    if (is_array($user->role) && isset($user->role['name']) && $user->role['name'] === 'paramedis') {
        echo "✅ User has paramedis role\n";
    } elseif (is_string($user->role) && $user->role === 'paramedis') {
        echo "✅ User has paramedis role\n";
    } else {
        echo "❌ User does not have paramedis role\n";
    }
    
    // Try to manually authenticate
    \Auth::login($user);
    
    if (\Auth::check()) {
        echo "✅ Successfully authenticated as: " . \Auth::user()->name . "\n";
        
        // Test mobile app access
        $mobileRequest = \Illuminate\Http\Request::create('/paramedis/mobile-app', 'GET');
        $mobileRequest->setUserResolver(function () {
            return \Auth::user();
        });
        
        echo "🔍 Testing mobile app access...\n";
        
        // Test role middleware manually
        $roleMiddleware = new \Spatie\Permission\Middlewares\RoleMiddleware();
        try {
            $response = $roleMiddleware->handle($mobileRequest, function ($req) {
                return response('Mobile app accessible');
            }, 'paramedis');
            
            echo "✅ Role middleware passed\n";
        } catch (\Exception $e) {
            echo "❌ Role middleware failed: " . $e->getMessage() . "\n";
        }
        
    } else {
        echo "❌ Authentication failed\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack: " . $e->getTraceAsString() . "\n";
}