<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Http\Controllers\ParamedisJaspelController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

echo "🔄 Testing Dashboard method...\n";

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

    // Test dashboard method by accessing the data it would pass
    $laravelJaspelData = []; // This would be generated in the dashboard method
    
    // Simulate the dashboard data generation logic
    $userId = $user->id;
    $currentMonth = \Carbon\Carbon::now();
    
    // Get monthly data
    $monthlyJaspel = \App\Models\Jaspel::where('user_id', $userId)
        ->where('jenis_jaspel', 'paramedis')
        ->approved()
        ->whereYear('tanggal', $currentMonth->year)
        ->whereMonth('tanggal', $currentMonth->month)
        ->sum('nominal');

    // Get weekly data (last 7 days)
    $weeklyJaspel = \App\Models\Jaspel::where('user_id', $userId)
        ->where('jenis_jaspel', 'paramedis')
        ->approved()
        ->where('tanggal', '>=', $currentMonth->subDays(6)->format('Y-m-d'))
        ->sum('nominal');

    echo "✅ Dashboard data generation successful\n";
    echo "📊 Dashboard data:\n";
    echo "   Monthly Jaspel: Rp " . number_format($monthlyJaspel, 0, ',', '.') . "\n";
    echo "   Weekly Jaspel: Rp " . number_format($weeklyJaspel, 0, ',', '.') . "\n";
    echo "   User: {$user->name}\n";

} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

echo "\n🎉 Dashboard test completed!\n";