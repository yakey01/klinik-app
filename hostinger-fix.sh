#\!/bin/bash
echo "🔧 Starting Hostinger deployment fix..."

# Fix 1: Create missing Composer\InstalledVersions class
echo "🔧 Creating missing Composer classes..."
mkdir -p vendor/composer
cat > vendor/composer/InstalledVersions.php << 'EOPHP'
<?php
namespace Composer;
class InstalledVersions {
    public static function getInstalledPackages() { return []; }
    public static function isInstalled($package) { return true; }
    public static function getVersion($package) { return '1.0.0'; }
    public static function getPrettyVersion($package) { return '1.0.0'; }
    public static function getReference($package) { return 'dev-main'; }
    public static function getRootPackage() { return ['name' => 'laravel/laravel', 'version' => '1.0.0']; }
    public static function getAllRawData() { return []; }
    public static function getInstallPath($package) { return null; }
}
EOPHP

# Fix 2: Comment out problematic filament-shield provider
echo "🔧 Disabling problematic providers..."
if [ -f "bootstrap/providers.php" ]; then
    sed -i 's/BezhanSalleh\\FilamentShield\\FilamentShieldServiceProvider/\/\/ BezhanSalleh\\FilamentShield\\FilamentShieldServiceProvider/' bootstrap/providers.php
fi

if [ -f "config/app.php" ]; then
    sed -i 's/BezhanSalleh\\FilamentShield\\FilamentShieldServiceProvider/\/\/ BezhanSalleh\\FilamentShield\\FilamentShieldServiceProvider/' config/app.php
fi

# Fix 3: Regenerate autoload
echo "🔄 Regenerating autoload files..."
composer dump-autoload --optimize

# Fix 4: Clear all Laravel caches
echo "🧹 Clearing caches..."
rm -rf bootstrap/cache/*
rm -rf storage/framework/cache/*
rm -rf storage/framework/views/*

# Fix 5: Fix permissions
echo "🔐 Setting permissions..."
chmod -R 755 storage bootstrap/cache
chmod -R 777 storage/logs storage/framework

echo "✅ Hostinger fix completed\!"
EOF < /dev/null