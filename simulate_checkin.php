<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Http\Controllers\Api\V2\Dashboards\ParamedisDashboardController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

echo "=== SIMULATING BITA CHECK-IN THROUGH API ===\n";

// Get user bita
$user = User::find(20);
echo "User: " . $user->name . "\n";

// Simulate authentication
Auth::login($user);
echo "User authenticated: " . (Auth::check() ? 'YES' : 'NO') . "\n";

// Create a request object with check-in data
$request = new Request([
    'latitude' => -6.2088,
    'longitude' => 106.8456,
    'accuracy' => 10,
    'location_name' => 'Test Location',
    'notes' => 'Simulated check-in test'
]);

// Create controller instance and call checkIn method
$controller = new ParamedisDashboardController();

try {
    echo "\nCalling checkIn method...\n";
    $response = $controller->checkIn($request);
    
    $responseData = json_decode($response->getContent(), true);
    
    echo "Response Status: " . $response->getStatusCode() . "\n";
    echo "Success: " . ($responseData['success'] ? 'YES' : 'NO') . "\n";
    echo "Message: " . $responseData['message'] . "\n";
    
    if ($responseData['success']) {
        echo "\n🎉 CHECK-IN SUCCESSFUL!\n";
        if (isset($responseData['data']['attendance_id'])) {
            echo "Attendance ID: " . $responseData['data']['attendance_id'] . "\n";
            echo "Time In: " . $responseData['data']['time_in'] . "\n";
            echo "Status: " . $responseData['data']['status'] . "\n";
        }
    } else {
        echo "\n❌ CHECK-IN FAILED!\n";
        if (isset($responseData['data'])) {
            echo "Additional data:\n";
            print_r($responseData['data']);
        }
    }
    
} catch (Exception $e) {
    echo "\n💥 EXCEPTION OCCURRED!\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    
    // Show more details
    echo "\nStack trace:\n";
    echo $e->getTraceAsString() . "\n";
}

// Check if attendance was created
echo "\n=== CHECKING ATTENDANCE RECORD ===\n";
$attendance = App\Models\Attendance::where('user_id', $user->id)
    ->whereDate('date', today())
    ->first();
    
if ($attendance) {
    echo "Attendance created: YES\n";
    echo "ID: " . $attendance->id . "\n";
    echo "Check-in: " . ($attendance->time_in ? $attendance->time_in->format('H:i:s') : 'NULL') . "\n";
} else {
    echo "Attendance created: NO\n";
}