<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Http\Controllers\ParamedisJaspelController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

echo "🔄 Testing ParamedisJaspelController...\n";

// Test with Naning user (ID: 17)
$user = User::find(17);
if (!$user) {
    echo "❌ User not found\n";
    exit(1);
}

echo "👤 Testing with user: {$user->name} (ID: {$user->id})\n";

// Simulate login
Auth::login($user);

try {
    $controller = new ParamedisJaspelController(
        new \App\Repositories\JaspelRepository(),
        new \App\Services\Jaspel\JaspelService(new \App\Repositories\JaspelRepository())
    );

    // Test API endpoint
    $request = new Request();
    $response = $controller->apiData($request);
    
    if ($response->getStatusCode() === 200) {
        $data = json_decode($response->getContent(), true);
        
        echo "✅ API Response successful\n";
        echo "📊 Response data:\n";
        echo "   Success: " . ($data['success'] ? 'true' : 'false') . "\n";
        
        if (isset($data['data'])) {
            $summary = $data['data']['summary'] ?? [];
            echo "   Total Jaspel: Rp " . number_format($summary['total_jaspel'] ?? 0, 0, ',', '.') . "\n";
            echo "   Total Records: " . ($summary['total_records'] ?? 0) . "\n";
            echo "   Recent Records: " . count($data['data']['recent'] ?? []) . "\n";
        }
        
        if (isset($data['error'])) {
            echo "⚠️  Warning: " . $data['error'] . "\n";
        }
    } else {
        echo "❌ API Response failed with status: " . $response->getStatusCode() . "\n";
        echo "Response: " . $response->getContent() . "\n";
    }

} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

// Test direct Jaspel query
echo "\n🔍 Direct database check for user {$user->id}:\n";
$userJaspel = \App\Models\Jaspel::where('user_id', $user->id)
    ->where('jenis_jaspel', 'paramedis')
    ->where('status_validasi', 'disetujui')
    ->sum('nominal');

echo "   Direct query result: Rp " . number_format($userJaspel, 0, ',', '.') . "\n";

$userJaspelCount = \App\Models\Jaspel::where('user_id', $user->id)
    ->where('jenis_jaspel', 'paramedis')
    ->where('status_validasi', 'disetujui')
    ->count();

echo "   Total records: {$userJaspelCount}\n";

echo "\n🎉 Controller test completed!\n";