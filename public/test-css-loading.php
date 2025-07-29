<?php
// Test CSS loading for all role panels

$manifest_path = __DIR__ . '/build/manifest.json';

if (!file_exists($manifest_path)) {
    die("❌ Build manifest not found. Please run: npm run build\n");
}

$manifest = json_decode(file_get_contents($manifest_path), true);

echo "🔍 CSS LOADING TEST - STABLE VERSION\n";
echo "=====================================\n\n";

$themes = [
    'admin' => 'resources/css/filament/admin/theme.css',
    'bendahara' => 'resources/css/filament/bendahara/theme.css',
    'dokter' => 'resources/css/filament/dokter/theme.css',
    'manajer' => 'resources/css/filament/manajer/theme.css',
    'paramedis' => 'resources/css/filament/paramedis/theme.css',
    'petugas' => 'resources/css/filament/petugas/theme.css',
];

echo "📦 MANIFEST CHECK:\n";
foreach ($themes as $role => $path) {
    if (isset($manifest[$path])) {
        $built_file = $manifest[$path]['file'];
        $full_path = __DIR__ . '/build/' . $built_file;
        $exists = file_exists($full_path);
        $size = $exists ? filesize($full_path) : 0;
        $size_kb = round($size / 1024, 2);
        
        echo sprintf("✅ %-10s: %s (%s KB)\n", 
            ucfirst($role), 
            $built_file,
            $size_kb
        );
    } else {
        echo sprintf("❌ %-10s: NOT IN MANIFEST\n", ucfirst($role));
    }
}

echo "\n🌐 CSS URLS FOR TESTING:\n";
foreach ($themes as $role => $path) {
    if (isset($manifest[$path])) {
        $url = '/build/' . $manifest[$path]['file'];
        echo sprintf("%-10s: http://localhost:8000%s\n", ucfirst($role), $url);
    }
}

echo "\n📊 BUILD SUMMARY:\n";
echo "Total entries in manifest: " . count($manifest) . "\n";
echo "Theme CSS files found: " . count(array_intersect_key($manifest, array_flip($themes))) . " of " . count($themes) . "\n";

// Check if any CSS files are actually accessible
echo "\n🔗 ACCESSIBILITY CHECK:\n";
foreach ($themes as $role => $path) {
    if (isset($manifest[$path])) {
        $built_file = $manifest[$path]['file'];
        $full_path = __DIR__ . '/build/' . $built_file;
        
        if (file_exists($full_path)) {
            $content = file_get_contents($full_path);
            $has_filament_import = strpos($content, 'filament/resources/css/theme.css') !== false;
            $has_custom_css = strlen($content) > 1000; // Rough check for custom CSS
            
            echo sprintf("%-10s: %s | %s\n", 
                ucfirst($role),
                $has_filament_import ? "✅ Has Filament" : "❌ No Filament",
                $has_custom_css ? "✅ Has Custom CSS" : "❌ No Custom CSS"
            );
        }
    }
}

echo "\n✨ RECOMMENDATION:\n";
echo "Access your panels at:\n";
foreach ($themes as $role => $path) {
    echo "- http://localhost:8000/$role/login\n";
}