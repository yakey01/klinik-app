<?php

echo "🔍 DEBUG: Hostinger Routes & Controller\n";
echo "=====================================\n\n";

// Check if we're on Hostinger
$isHostinger = strpos($_SERVER['HTTP_HOST'] ?? '', 'dokterkuklinic.com') !== false;
echo "Environment: " . ($isHostinger ? 'Hostinger Production' : 'Local Development') . "\n\n";

// Check if controller file exists
$controllerPath = 'app/Http/Controllers/Api/DokterStatsController.php';
if (file_exists($controllerPath)) {
    echo "✅ DokterStatsController.php exists\n";
    echo "   Size: " . filesize($controllerPath) . " bytes\n";
    echo "   Modified: " . date('Y-m-d H:i:s', filemtime($controllerPath)) . "\n";
} else {
    echo "❌ DokterStatsController.php missing\n";
}

echo "\n";

// Check if api.php has our routes
$apiRoutePath = 'routes/api.php';
if (file_exists($apiRoutePath)) {
    echo "✅ routes/api.php exists\n";
    echo "   Size: " . filesize($apiRoutePath) . " bytes\n";
    echo "   Modified: " . date('Y-m-d H:i:s', filemtime($apiRoutePath)) . "\n";
    
    // Check if our routes are in the file
    $apiContent = file_get_contents($apiRoutePath);
    if (strpos($apiContent, 'DokterStatsController') !== false) {
        echo "✅ DokterStatsController routes found in api.php\n";
    } else {
        echo "❌ DokterStatsController routes NOT found in api.php\n";
    }
    
    if (strpos($apiContent, '/dokter/stats') !== false) {
        echo "✅ /dokter/stats route found\n";
    } else {
        echo "❌ /dokter/stats route NOT found\n";
    }
} else {
    echo "❌ routes/api.php missing\n";
}

echo "\n";

// If this is Laravel, try to check routes
if (function_exists('app') || class_exists('Illuminate\Support\Facades\Route')) {
    echo "🔍 Checking Laravel routes...\n";
    
    try {
        // This won't work outside Laravel context, but let's try
        if (function_exists('route')) {
            echo "✅ Laravel context available\n";
        }
    } catch (Exception $e) {
        echo "ℹ️ Not in Laravel context: " . $e->getMessage() . "\n";
    }
}

echo "\n";
echo "🧪 Manual Test URLs:\n";
echo "   https://dokterkuklinic.com/api/public/dokter/stats\n";
echo "   https://dokterkuklinic.com/api/dokter/stats\n";
echo "   https://dokterkuklinic.com/api/api/dokter/stats\n";

echo "\n";
echo "🔧 Next Steps:\n";
echo "1. Run 'php artisan route:list | grep dokter' to see if routes are registered\n";
echo "2. Check Laravel logs: tail -f storage/logs/laravel.log\n";
echo "3. Test controller directly: php artisan tinker → app('App\\Http\\Controllers\\Api\\DokterStatsController')\n";
echo "4. Clear all caches: php artisan optimize:clear\n";

echo "\n✅ Debug completed at: " . date('Y-m-d H:i:s') . "\n";
?>