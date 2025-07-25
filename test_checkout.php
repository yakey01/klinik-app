<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Http\Controllers\Api\V2\Dashboards\ParamedisDashboardController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

echo "=== TESTING BITA CHECK-OUT ===\n";

$user = User::find(20);
Auth::login($user);

// Check current attendance status
$attendance = App\Models\Attendance::where('user_id', $user->id)
    ->whereDate('date', today())
    ->first();

if ($attendance) {
    echo "Current attendance status:\n";
    echo "Check-in: " . ($attendance->time_in ? $attendance->time_in->format('H:i:s') : 'NULL') . "\n";
    echo "Check-out: " . ($attendance->time_out ? $attendance->time_out->format('H:i:s') : 'NULL') . "\n";
    
    if ($attendance->time_in && !$attendance->time_out) {
        echo "\nUser can check-out. Testing check-out API...\n";
        
        $request = new Request([
            'latitude' => -6.2088,
            'longitude' => 106.8456,
            'accuracy' => 10,
            'location_name' => 'Test Location',
            'notes' => 'Simulated check-out test'
        ]);
        
        $controller = new ParamedisDashboardController();
        
        try {
            $response = $controller->checkOut($request);
            $responseData = json_decode($response->getContent(), true);
            
            echo "Check-out Response:\n";
            echo "Success: " . ($responseData['success'] ? 'YES' : 'NO') . "\n";
            echo "Message: " . $responseData['message'] . "\n";
            
            if ($responseData['success']) {
                echo "\n🎉 CHECK-OUT SUCCESSFUL!\n";
                if (isset($responseData['data']['time_out'])) {
                    echo "Time Out: " . $responseData['data']['time_out'] . "\n";
                    if (isset($responseData['data']['work_duration'])) {
                        echo "Work Duration: " . $responseData['data']['work_duration']['formatted'] . "\n";
                    }
                }
            }
            
        } catch (Exception $e) {
            echo "Check-out failed: " . $e->getMessage() . "\n";
        }
        
    } else {
        echo "\nUser already checked out or not checked in.\n";
    }
} else {
    echo "No attendance record found for today.\n";
}

// Final status
echo "\n=== FINAL ATTENDANCE STATUS ===\n";
$finalAttendance = App\Models\Attendance::where('user_id', $user->id)
    ->whereDate('date', today())
    ->first();
    
if ($finalAttendance) {
    echo "Check-in: " . ($finalAttendance->time_in ? $finalAttendance->time_in->format('H:i:s') : 'NULL') . "\n";
    echo "Check-out: " . ($finalAttendance->time_out ? $finalAttendance->time_out->format('H:i:s') : 'NULL') . "\n";
    echo "Work Duration: " . ($finalAttendance->formatted_work_duration ?? 'NULL') . "\n";
    echo "Status: " . $finalAttendance->status . "\n";
}