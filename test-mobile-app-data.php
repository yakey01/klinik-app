<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Find dokter user
$dokter = \App\Models\Dokter::where('username', 'yaya')->first();

if (!$dokter) {
    echo "❌ Dokter with username 'yaya' not found" . PHP_EOL;
    exit(1);
}

$user = $dokter->user;

if (!$user) {
    echo "❌ User not found for dokter" . PHP_EOL;
    exit(1);
}

echo "🔍 Testing mobile app data for Dr. Yaya:" . PHP_EOL;
echo "User ID: " . $user->id . PHP_EOL;
echo "User Name: " . $user->name . PHP_EOL;
echo "Dokter ID: " . $dokter->id . PHP_EOL;
echo "Dokter Nama Lengkap: " . $dokter->nama_lengkap . PHP_EOL;

// Simulate the route logic
$displayName = $dokter ? $dokter->nama_lengkap : $user->name;

$userData = [
    'name' => $displayName,
    'email' => $user->email,
    'greeting' => 'Selamat Siang',
    'initials' => strtoupper(substr($displayName ?? 'DA', 0, 2))
];

echo PHP_EOL . "📱 userData that would be passed to mobile app:" . PHP_EOL;
echo json_encode($userData, JSON_PRETTY_PRINT) . PHP_EOL;

// Test the dashboard API call
echo PHP_EOL . "🔍 Testing dashboard API call:" . PHP_EOL;

try {
    // Simulate authentication
    auth()->login($user);
    
    $controller = new \App\Http\Controllers\Api\V2\Dashboards\DokterDashboardController();
    $request = new Request();
    
    $response = $controller->index($request);
    $responseData = $response->getData(true);
    
    if ($responseData['success']) {
        echo "✅ Dashboard API call successful" . PHP_EOL;
        echo "Dashboard User Name: " . $responseData['data']['user']['name'] . PHP_EOL;
        echo "Dashboard Dokter Nama: " . $responseData['data']['dokter']['nama_lengkap'] . PHP_EOL;
        
        // Check what the frontend would display
        $dashboardStats = $responseData['data'];
        $welcomeName = $dashboardStats['dokter']['nama_lengkap'] ?? 
                      $dashboardStats['user']['name'] ?? 
                      $userData['name'] ?? 
                      'Dokter';
        
        echo "🎯 Final welcome name would be: " . $welcomeName . PHP_EOL;
    } else {
        echo "❌ Dashboard API call failed: " . $responseData['message'] . PHP_EOL;
    }
    
} catch (Exception $e) {
    echo "❌ Dashboard API test failed: " . $e->getMessage() . PHP_EOL;
}