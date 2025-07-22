<?php
// Test Dashboard API

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🧪 TESTING DOKTER DASHBOARD API\n";
echo "===============================\n";

// Find Dr. Yaya
$yayaUser = App\Models\User::whereHas('dokter', function($q) {
    $q->where('username', 'yaya');
})->first();

if ($yayaUser) {
    echo "✅ Dr. Yaya Found: " . $yayaUser->name . "\n";
    
    // Login as Dr. Yaya
    auth()->login($yayaUser);
    
    try {
        // Test the controller
        $controller = new App\Http\Controllers\Api\V2\Dashboards\DokterDashboardController();
        $request = new Illuminate\Http\Request();
        
        $response = $controller->index($request);
        $data = $response->getData(true);
        
        echo "API Status: " . ($data['success'] ? 'SUCCESS' : 'FAILED') . "\n";
        
        if ($data['success']) {
            if (isset($data['data']['performance'])) {
                $perf = $data['data']['performance'];
                echo "\n🎯 ATTENDANCE RANKING:\n";
                echo "  Rank: " . ($perf['attendance_rank'] ?? 'NULL') . "\n";
                echo "  Total Staff: " . ($perf['total_staff'] ?? 'NULL') . "\n";
                echo "  Attendance %: " . ($perf['attendance_percentage'] ?? 'NULL') . "%\n";
                echo "  Attendance Rate: " . ($perf['attendance_rate'] ?? 'NULL') . "\n";
            } else {
                echo "❌ Performance data missing\n";
            }
            
            if (isset($data['data']['dokter'])) {
                $dokter = $data['data']['dokter'];
                echo "\n👨‍⚕️ DOKTER INFO:\n";
                echo "  Nama Lengkap: " . ($dokter['nama_lengkap'] ?? 'NULL') . "\n";
                echo "  Username: " . ($dokter['username'] ?? 'NULL') . "\n";
            }
        } else {
            echo "❌ API Failed: " . ($data['message'] ?? 'Unknown error') . "\n";
        }
        
    } catch (Exception $e) {
        echo "❌ API Error: " . $e->getMessage() . "\n";
    }
    
    auth()->logout();
} else {
    echo "❌ Dr. Yaya user not found!\n";
}

echo "\n✅ TEST COMPLETED\n";