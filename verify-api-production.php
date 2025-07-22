<?php
// API verification script for production

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);

// Simulate dokter user login
$dokterUser = \App\Models\User::whereHas('roles', function($q) {
    $q->where('name', 'dokter');
})->first();

if (!$dokterUser) {
    die("No dokter user found!\n");
}

\Auth::login($dokterUser);

echo "🔍 Testing as: " . $dokterUser->name . " (ID: " . $dokterUser->id . ")\n\n";

// Test getIgdSchedules endpoint
$controller = new \App\Http\Controllers\Api\V2\Dashboards\DokterDashboardController();
$request = new \Illuminate\Http\Request();

echo "📋 Testing getIgdSchedules():\n";
$response = $controller->getIgdSchedules($request);
$data = json_decode($response->getContent(), true);

if ($data['success']) {
    echo "✅ Success! Found " . count($data['data']) . " schedules\n";
    
    // Check if schedules are filtered correctly
    $wrongUserSchedules = 0;
    foreach ($data['data'] as $schedule) {
        if ($schedule['pegawai_id'] != $dokterUser->id) {
            $wrongUserSchedules++;
            echo "❌ WRONG USER DATA: Schedule ID " . $schedule['id'] . " belongs to user " . $schedule['pegawai_id'] . "\n";
        }
    }
    
    if ($wrongUserSchedules === 0) {
        echo "✅ All schedules correctly filtered to current user\n";
    } else {
        echo "❌ Found $wrongUserSchedules schedules from other users!\n";
    }
} else {
    echo "❌ API Error: " . ($data['message'] ?? 'Unknown error') . "\n";
}

echo "\n✅ Test complete!\n";