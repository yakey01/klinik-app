<?php
// Check API Response

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🧪 CHECKING API RESPONSE STRUCTURE\n";
echo "=================================\n";

// Find Dr. Yaya
$yayaUser = App\Models\User::whereHas('dokter', function($q) {
    $q->where('username', 'yaya');
})->first();

if ($yayaUser) {
    echo "✅ Dr. Yaya Found: " . $yayaUser->name . "\n\n";
    
    // Login as Dr. Yaya
    auth()->login($yayaUser);
    
    try {
        // Test the controller
        $controller = new App\Http\Controllers\Api\V2\Dashboards\DokterDashboardController();
        $request = new Illuminate\Http\Request();
        
        $response = $controller->index($request);
        $data = $response->getData(true);
        
        echo "📊 FULL API RESPONSE STRUCTURE:\n";
        echo "==============================\n";
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
        echo "\n\n🎯 PERFORMANCE DATA DETAIL:\n";
        echo "===========================\n";
        if (isset($data['data']['performance'])) {
            $perf = $data['data']['performance'];
            foreach ($perf as $key => $value) {
                echo "  $key: " . (is_null($value) ? 'NULL' : $value) . "\n";
            }
        } else {
            echo "❌ No performance data found\n";
        }
        
    } catch (Exception $e) {
        echo "❌ API Error: " . $e->getMessage() . "\n";
        echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    }
    
    auth()->logout();
} else {
    echo "❌ Dr. Yaya user not found!\n";
}

echo "\n✅ CHECK COMPLETED\n";