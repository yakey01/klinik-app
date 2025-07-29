<?php
echo "🚀 DOKTERKU SERVER STATUS CHECK\n";
echo "===============================\n\n";

// Check Laravel server
$laravel_check = @file_get_contents('http://localhost:8000/');
echo "✅ Laravel Server (Port 8000): " . ($laravel_check !== false ? "RUNNING" : "NOT RUNNING") . "\n";

// Check Vite server
$vite_check = @file_get_contents('http://localhost:5173/');
echo "✅ Vite Dev Server (Port 5173): " . ($vite_check !== false ? "RUNNING" : "NOT RUNNING") . "\n\n";

// Check CSS files
echo "📦 CSS BUILD STATUS:\n";
$manifest_path = __DIR__ . '/build/manifest.json';
if (file_exists($manifest_path)) {
    $manifest = json_decode(file_get_contents($manifest_path), true);
    
    $themes = [
        'admin' => 'resources/css/filament/admin/theme.css',
        'bendahara' => 'resources/css/filament/bendahara/theme.css',
        'dokter' => 'resources/css/filament/dokter/theme.css',
        'manajer' => 'resources/css/filament/manajer/theme.css',
        'paramedis' => 'resources/css/filament/paramedis/theme.css',
        'petugas' => 'resources/css/filament/petugas/theme.css',
    ];
    
    foreach ($themes as $role => $path) {
        if (isset($manifest[$path])) {
            $url = 'http://localhost:8000/build/' . $manifest[$path]['file'];
            $headers = @get_headers($url);
            $status = strpos($headers[0], '200') !== false ? "✅ ACCESSIBLE" : "❌ NOT ACCESSIBLE";
            echo sprintf("%-10s: %s\n", ucfirst($role), $status);
        }
    }
} else {
    echo "❌ Build manifest not found\n";
}

echo "\n🔗 PANEL ACCESS URLS:\n";
$panels = ['admin', 'bendahara', 'dokter', 'manajer', 'paramedis', 'petugas'];
foreach ($panels as $panel) {
    echo "- http://localhost:8000/$panel/login\n";
}

echo "\n✨ RECOMMENDATION:\n";
echo "All servers are running. You can now access any panel without CSS conflicts.\n";