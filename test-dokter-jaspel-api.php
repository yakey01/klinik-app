<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\User;
use App\Http\Controllers\Api\V2\Dashboards\DokterDashboardController;
use Illuminate\Http\Request;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🧪 Testing Enhanced DokterDashboardController getJaspel API..." . PHP_EOL;

// Find Dr. Yaya
$user = User::find(1); // Dr. Yaya
if (!$user) {
    echo "❌ Dr. Yaya not found!" . PHP_EOL;
    exit;
}

echo "✅ Testing for Dr. Yaya: " . $user->name . " (ID: " . $user->id . ")" . PHP_EOL;

// Mock authentication
auth()->login($user);

// Create mock request
$request = new Request();
$request->merge([
    'month' => date('n'), // Current month
    'year' => date('Y')   // Current year
]);

echo "📅 Testing for: " . date('F Y') . PHP_EOL;

try {
    // Create controller instance
    $controller = new DokterDashboardController();
    
    // Call the getJaspel method
    $response = $controller->getJaspel($request);
    
    // Get response data
    $responseData = $response->getData(true);
    
    echo PHP_EOL . "🎯 API RESPONSE:" . PHP_EOL;
    echo "Status: " . ($responseData['success'] ? '✅ SUCCESS' : '❌ FAILED') . PHP_EOL;
    echo "Message: " . $responseData['message'] . PHP_EOL;
    
    // Debug: Show full response structure
    echo PHP_EOL . "🔍 FULL RESPONSE STRUCTURE:" . PHP_EOL;
    echo json_encode($responseData, JSON_PRETTY_PRINT) . PHP_EOL;
    
    if ($responseData['success'] && isset($responseData['data'])) {
        $data = $responseData['data'];
        
        echo PHP_EOL . "📊 JASPEL STATS:" . PHP_EOL;
        if (isset($data['stats'])) {
            $stats = $data['stats'];
            echo "Total: Rp " . number_format($stats['total'] ?? 0) . PHP_EOL;
            echo "Approved: Rp " . number_format($stats['approved'] ?? 0) . PHP_EOL;
            echo "Pending: Rp " . number_format($stats['pending'] ?? 0) . PHP_EOL;
            echo "Count Tindakan: " . ($stats['count_tindakan'] ?? 0) . PHP_EOL;
        }
        
        echo PHP_EOL . "📈 PERFORMANCE METRICS:" . PHP_EOL;
        if (isset($data['performance'])) {
            $perf = $data['performance'];
            echo "Query Execution Time: " . ($perf['query_execution_time'] ?? 'N/A') . " ms" . PHP_EOL;
            echo "Total Database Queries: " . ($perf['total_queries'] ?? 'N/A') . PHP_EOL;
            echo "Cache Hit Rate: " . ($perf['cache_hit_rate'] ?? 'N/A') . "%" . PHP_EOL;
        }
        
        echo PHP_EOL . "📋 DAILY BREAKDOWN:" . PHP_EOL;
        if (isset($data['daily_breakdown']) && is_array($data['daily_breakdown'])) {
            foreach ($data['daily_breakdown'] as $daily) {
                echo "Date: " . ($daily['date'] ?? 'N/A') . " - Jaspel Count: " . ($daily['total_jaspel'] ?? 0) . " - Amount: Rp " . number_format($daily['total_amount'] ?? 0) . PHP_EOL;
            }
        } else {
            echo "No daily breakdown data" . PHP_EOL;
        }
        
        echo PHP_EOL . "🎯 RECENT JASPEL:" . PHP_EOL;
        if (isset($data['recent_jaspel']) && is_array($data['recent_jaspel'])) {
            foreach ($data['recent_jaspel'] as $jaspel) {
                echo "Jaspel ID: " . ($jaspel['id'] ?? 'N/A') . " - " . ($jaspel['tanggal'] ?? 'N/A') . " - Rp " . number_format($jaspel['nominal'] ?? 0) . " - Status: " . ($jaspel['status_validasi'] ?? 'N/A') . PHP_EOL;
            }
        } else {
            echo "No recent jaspel data" . PHP_EOL;
        }
        
        echo PHP_EOL . "🧪 MODEL INTEGRATION TEST:" . PHP_EOL;
        if (isset($data['model_integration'])) {
            $integration = $data['model_integration'];
            echo "Jaspel Model Used: " . ($integration['jaspel_model_used'] ? '✅ YES' : '❌ NO') . PHP_EOL;
            echo "Relations Working: " . ($integration['relations_working'] ? '✅ YES' : '❌ NO') . PHP_EOL;
            echo "Validated Tindakan Filter: " . ($integration['validated_filter'] ? '✅ YES' : '❌ NO') . PHP_EOL;
        }
        
    } else {
        echo "❌ No data returned or API failed" . PHP_EOL;
        if (isset($responseData['error'])) {
            echo "Error: " . $responseData['error'] . PHP_EOL;
        }
    }
    
} catch (Exception $e) {
    echo "❌ Exception occurred: " . $e->getMessage() . PHP_EOL;
    echo "Trace: " . $e->getTraceAsString() . PHP_EOL;
}

echo PHP_EOL . "🏁 Test completed!" . PHP_EOL;