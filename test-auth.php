<?php
require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

// Manually set up the application without HTTP
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Login as user Naning
$user = \App\Models\User::find(13);
if (!$user) {
    echo "❌ User Naning not found!\n";
    exit(1);
}

Auth::login($user);
echo "✅ Logged in as: {$user->name} (ID: {$user->id})\n";
echo "📧 Email: {$user->email}\n\n";

// Test if user can access mobile app
try {
    // Simulate the authentication check that mobile app route uses
    $middleware = app(\App\Http\Middleware\ParamedisMobileAppAuth::class);
    echo "🔐 Testing ParamedisMobileAppAuth middleware...\n";
    
    // Create a fake request
    $request = \Illuminate\Http\Request::create('/paramedis/mobile-app', 'GET');
    $request->setUserResolver(function () use ($user) {
        return $user;
    });
    
    // Test the middleware
    $response = $middleware->handle($request, function ($req) {
        return response('OK');
    });
    
    if ($response->getStatusCode() === 200) {
        echo "✅ User can access mobile app\n";
    } else {
        echo "❌ User cannot access mobile app (Status: {$response->getStatusCode()})\n";
        echo "Response: " . $response->getContent() . "\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Middleware test failed: " . $e->getMessage() . "\n";
}

// Test API access directly
echo "\n🔍 Testing API access...\n";
try {
    $controller = new \App\Http\Controllers\Api\V2\Dashboards\ParamedisDashboardController();
    $request = new \Illuminate\Http\Request([
        'month' => 7,
        'year' => 2025
    ]);
    
    // Set the authenticated user for the request
    $request->setUserResolver(function () use ($user) {
        return $user;
    });
    
    $response = $controller->getAttendanceRecap($request);
    $data = json_decode($response->content(), true);
    
    echo "📊 API Response Status: " . ($data['success'] ? 'SUCCESS' : 'FAILED') . "\n";
    echo "📊 Message: " . $data['message'] . "\n";
    
    if ($data['success'] && isset($data['data']['recap'])) {
        $recap = $data['data']['recap'];
        echo "✅ API returns valid data:\n";
        echo "   - Days Present: {$recap['days_present']}\n";
        echo "   - Scheduled Days: {$recap['scheduled_days']}\n";
        echo "   - Attendance %: {$recap['attendance_percentage']}%\n";
    }
    
} catch (\Exception $e) {
    echo "❌ API test failed: " . $e->getMessage() . "\n";
}