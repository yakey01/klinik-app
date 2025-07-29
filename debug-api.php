<?php
require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

// Manually set up the application without HTTP
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Login as user Naning
$user = \App\Models\User::find(13);
if (!$user) {
    echo "User Naning not found!\n";
    exit(1);
}

Auth::login($user);
echo "✅ Logged in as: {$user->name} (ID: {$user->id})\n\n";

// Test the getAttendanceRecap method directly
try {
    $controller = new \App\Http\Controllers\Api\V2\Dashboards\ParamedisDashboardController();
    $request = new \Illuminate\Http\Request([
        'month' => 7,
        'year' => 2025
    ]);
    
    echo "🔍 Testing getAttendanceRecap method...\n";
    $response = $controller->getAttendanceRecap($request);
    $data = json_decode($response->content(), true);
    
    echo "📊 API Response Status: " . ($data['success'] ? 'SUCCESS' : 'FAILED') . "\n";
    echo "📊 Message: " . $data['message'] . "\n\n";
    
    if ($data['success'] && isset($data['data']['recap'])) {
        $recap = $data['data']['recap'];
        echo "👤 Staff Info:\n";
        echo "   - Name: {$recap['staff_name']}\n";
        echo "   - Position: {$recap['position']}\n";
        echo "   - Days Present: {$recap['days_present']}\n";
        echo "   - Scheduled Days: {$recap['scheduled_days']}\n";
        echo "   - Attendance %: {$recap['attendance_percentage']}%\n";
        echo "   - Status: {$recap['status']}\n\n";
        
        echo "📅 Daily Breakdown Count: " . count($data['data']['daily_breakdown']) . "\n";
        
        // Show first few days with attendance
        $attendedDays = array_filter($data['data']['daily_breakdown'], fn($day) => !is_null($day['attendance_info']));
        echo "📈 Days with Attendance: " . count($attendedDays) . "\n";
        
        if (!empty($attendedDays)) {
            echo "\n🎯 Sample Attended Day:\n";
            $sample = array_values($attendedDays)[0];
            echo "   - Date: {$sample['date']}\n";
            echo "   - Day: {$sample['day_name']}\n";
            echo "   - Scheduled: " . ($sample['is_scheduled'] ? 'Yes' : 'No') . "\n";
            echo "   - Compliance: {$sample['compliance_status']}\n";
            if ($sample['attendance_info']) {
                echo "   - Time In: {$sample['attendance_info']['time_in']}\n";
                echo "   - Time Out: {$sample['attendance_info']['time_out']}\n";
                echo "   - Status: {$sample['attendance_info']['status']}\n";
            }
        }
    } else {
        echo "❌ No recap data found or API failed\n";
        if (isset($data['data'])) {
            echo "Data keys: " . implode(', ', array_keys($data['data'])) . "\n";
        }
    }
    
} catch (\Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}