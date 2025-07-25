<?php
/**
 * Direct API Test for Production Server
 * Tests the dokter dashboard API endpoint directly
 */

require_once __DIR__ . '/vendor/autoload.php';

// Load Laravel application
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== DIRECT API TEST FOR DOKTER DASHBOARD ===\n";
echo "Timestamp: " . date('Y-m-d H:i:s') . "\n\n";

try {
    // Find Dr. Yaya
    $yayaUser = App\Models\User::where('email', 'yaya@dokterkuklinik.com')->first();
    
    if (!$yayaUser) {
        echo "ERROR: Dr. Yaya user not found\n";
        exit(1);
    }

    echo "Testing with Dr. Yaya (ID: {$yayaUser->id})\n";
    echo "Name: {$yayaUser->nama_lengkap}\n";
    echo "Role: {$yayaUser->role}\n\n";

    // Simulate authentication
    auth()->login($yayaUser);
    
    // Create controller instance
    $controller = new App\Http\Controllers\Api\V2\Dashboards\DokterDashboardController();
    
    // Create mock request
    $request = new Illuminate\Http\Request();
    
    // Call the index method
    echo "Calling DokterDashboardController::index()...\n";
    $response = $controller->index($request);
    
    if ($response instanceof Illuminate\Http\JsonResponse) {
        $data = $response->getData(true);
        $statusCode = $response->getStatusCode();
        
        echo "Response Status Code: {$statusCode}\n\n";
        echo "Response Data Structure:\n";
        echo "========================\n";
        
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                echo "{$key}: Array with " . count($value) . " items\n";
                if (!empty($value) && count($value) <= 5) {
                    echo "  Sample items:\n";
                    foreach (array_slice($value, 0, 3) as $index => $item) {
                        if (is_array($item)) {
                            $itemStr = json_encode($item, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                            echo "    [{$index}]: {$itemStr}\n";
                        } else {
                            echo "    [{$index}]: {$item}\n";
                        }
                    }
                }
            } else {
                echo "{$key}: {$value}\n";
            }
        }
        
        echo "\nFull JSON Response:\n";
        echo "==================\n";
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
        
        // Specific checks for orange card logic
        echo "\nORANGE CARD ANALYSIS:\n";
        echo "====================\n";
        
        if (isset($data['attendanceRanking'])) {
            $attendanceRanking = $data['attendanceRanking'];
            echo "AttendanceRanking found: " . count($attendanceRanking) . " items\n";
            
            if (empty($attendanceRanking)) {
                echo "  -> This should trigger ORANGE CARD (empty ranking)\n";
            } else {
                echo "  -> This should show NORMAL RANKING (has data)\n";
                echo "  -> First few rankings:\n";
                foreach (array_slice($attendanceRanking, 0, 3) as $rank) {
                    echo "     - " . ($rank['nama_lengkap'] ?? 'N/A') . ": " . ($rank['total_kehadiran'] ?? 0) . " kehadiran\n";
                }
            }
        } else {
            echo "AttendanceRanking NOT FOUND in response\n";
            echo "  -> This might cause issues in frontend\n";
        }
        
    } else {
        echo "ERROR: Unexpected response type\n";
        var_dump($response);
    }

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "\nStack Trace:\n";
    echo $e->getTraceAsString() . "\n";
}