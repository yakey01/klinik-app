<?php
// Force clear production cache script

echo "🔧 Force clearing production caches...\n\n";

// 1. Clear OPcache
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "✅ OPcache reset\n";
} else {
    echo "⚠️  OPcache not available\n";
}

// 2. Clear APCu cache if available
if (function_exists('apcu_clear_cache')) {
    apcu_clear_cache();
    echo "✅ APCu cache cleared\n";
}

// 3. Force reload timestamp on critical files
$files = [
    __DIR__ . '/resources/js/components/dokter/Dashboard.tsx',
    __DIR__ . '/app/Http/Controllers/Api/V2/Dashboards/DokterDashboardController.php',
    __DIR__ . '/app/Http/Controllers/Api/V2/Dashboards/ParamedisDashboardController.php',
];

foreach ($files as $file) {
    if (file_exists($file)) {
        touch($file);
        echo "✅ Touched: " . basename($file) . "\n";
    }
}

// 4. Clear Laravel cache directories
$cacheDirectories = [
    __DIR__ . '/storage/framework/cache/data',
    __DIR__ . '/storage/framework/views',
    __DIR__ . '/storage/framework/sessions',
    __DIR__ . '/bootstrap/cache',
];

foreach ($cacheDirectories as $dir) {
    if (is_dir($dir)) {
        $files = glob($dir . '/*');
        foreach ($files as $file) {
            if (is_file($file) && basename($file) !== '.gitignore') {
                unlink($file);
            }
        }
        echo "✅ Cleared: " . basename($dir) . "\n";
    }
}

// 5. Output cache busting parameters
$timestamp = time();
echo "\n📍 Cache busting timestamp: " . $timestamp . "\n";
echo "📍 Add ?v=" . $timestamp . " to asset URLs to force refresh\n";

echo "\n✅ All caches cleared successfully!\n";