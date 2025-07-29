<?php
require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🔐 WEB AUTH API TEST\n";
echo "====================\n\n";

// Login as Naning first  
$user = \App\Models\User::find(13);
Auth::login($user);

echo "1. ✅ Authenticated as: " . Auth::user()->name . "\n";
echo "2. 🔑 User ID: " . Auth::user()->id . "\n";
echo "3. 🎭 Role: " . Auth::user()->role->name . "\n\n";

// Test the getAttendanceRecap method directly with authenticated user
echo "4. 🔍 TESTING API METHOD DIRECTLY:\n";

try {
    $controller = new \App\Http\Controllers\Api\V2\Dashboards\ParamedisDashboardController();
    $request = new \Illuminate\Http\Request(['month' => 7, 'year' => 2025]);
    
    // Simulate web authentication
    $request->setUserResolver(function () {
        return Auth::user();
    });
    
    $response = $controller->getAttendanceRecap($request);
    $data = json_decode($response->content(), true);
    
    echo "   📊 Direct API call: " . ($data['success'] ? 'SUCCESS' : 'FAILED') . "\n";
    
    if ($data['success']) {
        echo "   📈 Days Present: " . $data['data']['recap']['days_present'] . "\n";
        echo "   📅 Scheduled Days: " . $data['data']['recap']['scheduled_days'] . "\n";
        echo "   📊 Attendance %: " . $data['data']['recap']['attendance_percentage'] . "%\n";
    }
    
} catch (\Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}

echo "\n5. 🌐 TESTING MIDDLEWARE CHAIN:\n";

// Test middleware manually
$request = \Illuminate\Http\Request::create('/api/v2/dashboards/paramedis/attendance-recap', 'GET', ['month' => 7, 'year' => 2025]);
$request->setUserResolver(function () {
    return Auth::user();
});

// Test auth middleware
$authMiddleware = new \Illuminate\Auth\Middleware\Authenticate();
try {
    $response = $authMiddleware->handle($request, function ($req) {
        return response('Auth passed');
    }, 'web');
    echo "   ✅ Auth middleware (web): PASSED\n";
} catch (\Exception $e) {
    echo "   ❌ Auth middleware (web): FAILED - " . $e->getMessage() . "\n";
}

// Test role middleware
$roleMiddleware = new \App\Http\Middleware\RoleMiddleware();
try {
    $response = $roleMiddleware->handle($request, function ($req) {
        return response('Role passed');
    }, 'paramedis');
    echo "   ✅ Role middleware: PASSED\n";
} catch (\Exception $e) {
    echo "   ❌ Role middleware: FAILED - " . $e->getMessage() . "\n";
}

echo "\n====================\n";
echo "🎯 TEST COMPLETE\n";