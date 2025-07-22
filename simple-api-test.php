<?php

// Simple API test for Hostinger
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$yayaUser = App\Models\User::whereHas('dokter', function($q) {
    $q->where('username', 'yaya');
})->first();

if ($yayaUser) {
    auth()->login($yayaUser);
    
    $controller = new App\Http\Controllers\Api\V2\Dashboards\DokterDashboardController();
    $request = new Illuminate\Http\Request();
    
    $response = $controller->index($request);
    $data = $response->getData(true);
    
    echo "HOSTINGER API RESPONSE:\n";
    echo "======================\n";
    
    if (isset($data['data']['performance'])) {
        $perf = $data['data']['performance'];
        echo "attendance_rank: " . ($perf['attendance_rank'] ?? 'NULL') . "\n";
        echo "total_staff: " . ($perf['total_staff'] ?? 'NULL') . "\n";
        echo "attendance_percentage: " . ($perf['attendance_percentage'] ?? 'NULL') . "\n";
        
        // Show what should display
        if ($perf['attendance_rank'] && $perf['total_staff']) {
            echo "\nSHOULD DISPLAY:\n";
            echo "Rank: #" . $perf['attendance_rank'] . "\n";
            echo "Text: dari " . $perf['total_staff'] . " dokter\n";
        }
    } else {
        echo "NO PERFORMANCE DATA!\n";
    }
    
    auth()->logout();
}

?>