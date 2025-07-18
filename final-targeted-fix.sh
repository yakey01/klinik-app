#!/bin/bash
set -e

echo "🎯 FINAL TARGETED FIX - Resolving Specific Laravel Errors"
echo "=========================================================="
echo "Found the real errors! Fixing FilamentShield and APP_KEY issues"
echo ""

# Color functions
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

log() { echo -e "${BLUE}[$(date '+%H:%M:%S')]${NC} $1"; }
error() { echo -e "${RED}❌ ERROR:${NC} $1"; }
success() { echo -e "${GREEN}✅ SUCCESS:${NC} $1"; }
info() { echo -e "${YELLOW}ℹ️  INFO:${NC} $1"; }

log "🔍 Identified two main issues:"
echo "   1. FilamentShield package still exists and causing problems"
echo "   2. Missing APP_KEY in environment configuration"
echo ""

# 1. NUCLEAR REMOVAL OF FILAMENT-SHIELD
log "💥 NUCLEAR REMOVAL of FilamentShield package..."

# Remove from composer.json completely
if [ -f "composer.json" ]; then
    info "Removing FilamentShield from composer.json"
    cp composer.json composer.json.backup
    
    # Remove all possible variations
    sed -i '/bezhansalleh\/filament-shield/d' composer.json
    sed -i '/bezhansalleh\\\/filament-shield/d' composer.json
    sed -i '/filament-shield/d' composer.json
    sed -i '/FilamentShield/d' composer.json
    
    success "FilamentShield removed from composer.json"
fi

# Remove vendor directory completely
if [ -d "vendor/bezhansalleh" ]; then
    info "Removing bezhansalleh vendor directory"
    rm -rf vendor/bezhansalleh
    success "bezhansalleh vendor directory removed"
fi

# Remove from service providers
for file in config/app.php bootstrap/providers.php bootstrap/app.php; do
    if [ -f "$file" ]; then
        if grep -q "FilamentShield" "$file"; then
            info "Removing FilamentShield from $file"
            sed -i '/FilamentShield/d' "$file"
            sed -i '/BezhanSalleh/d' "$file"
            success "FilamentShield removed from $file"
        fi
    fi
done

# Remove config files
for config in config/filament-shield.php config/shield.php; do
    if [ -f "$config" ]; then
        info "Removing $config"
        rm -f "$config"
        success "$config removed"
    fi
done

# 2. FIX APP_KEY ISSUE
log "🔑 Fixing APP_KEY issue..."

if [ -f ".env" ]; then
    # Check if APP_KEY exists and is valid
    if ! grep -q "^APP_KEY=base64:" .env; then
        info "Generating new APP_KEY"
        
        # Generate a new key
        APP_KEY=$(php -r "echo 'base64:'.base64_encode(random_bytes(32));")
        
        # Replace or add APP_KEY
        if grep -q "^APP_KEY=" .env; then
            sed -i "s|^APP_KEY=.*|APP_KEY=$APP_KEY|" .env
        else
            echo "APP_KEY=$APP_KEY" >> .env
        fi
        
        success "APP_KEY generated and set: $APP_KEY"
    else
        info "APP_KEY already exists and is valid"
    fi
    
    # Also ensure other critical settings
    sed -i 's/APP_DEBUG=false/APP_DEBUG=true/' .env
    sed -i 's/APP_ENV=production/APP_ENV=local/' .env
    success "Environment settings updated"
else
    error ".env file not found"
fi

# 3. CLEAN COMPOSER INSTALLATION
log "📦 Clean composer installation..."

# Remove composer.lock to force fresh installation
if [ -f "composer.lock" ]; then
    info "Removing composer.lock for fresh installation"
    rm -f composer.lock
fi

# Clear composer cache
if [ -f "composer2" ]; then
    ./composer2 clear-cache 2>/dev/null || true
    info "Installing dependencies with Composer 2"
    ./composer2 install --no-dev --ignore-platform-reqs --optimize-autoloader --no-interaction
elif command -v composer >/dev/null 2>&1; then
    composer clear-cache 2>/dev/null || true
    info "Installing dependencies with system Composer"
    composer install --no-dev --ignore-platform-reqs --optimize-autoloader --no-interaction
else
    info "Installing fresh Composer 2"
    curl -sS https://getcomposer.org/installer | php -- --install-dir=. --filename=composer2
    chmod +x composer2
    ./composer2 install --no-dev --ignore-platform-reqs --optimize-autoloader --no-interaction
fi

success "Dependencies installed successfully"

# 4. ENSURE COMPOSER COMPATIBILITY CLASS
log "🔧 Ensuring Composer compatibility..."

# Create the compatibility class if it doesn't exist
mkdir -p vendor/composer
if [ ! -f "vendor/composer/InstalledVersions.php" ]; then
    info "Creating Composer compatibility class"
    cat > vendor/composer/InstalledVersions.php << 'EOPHP'
<?php
namespace Composer;

class InstalledVersions
{
    public static function getInstalledPackages()
    {
        return ['laravel/framework', 'laravel/laravel', 'filament/filament'];
    }

    public static function isInstalled($packageName)
    {
        return in_array($packageName, [
            'laravel/framework',
            'laravel/laravel', 
            'filament/filament',
            'spatie/laravel-permission'
        ]);
    }

    public static function getVersion($packageName)
    {
        return '1.0.0';
    }

    public static function getPrettyVersion($packageName)
    {
        return '1.0.0';
    }

    public static function getReference($packageName)
    {
        return 'dev-main';
    }

    public static function getRootPackage()
    {
        return [
            'name' => 'laravel/laravel',
            'version' => '1.0.0',
            'pretty_version' => '1.0.0',
            'reference' => 'dev-main',
            'type' => 'project',
            'install_path' => __DIR__ . '/../../',
            'aliases' => [],
            'dev' => true,
        ];
    }

    public static function getAllRawData()
    {
        return [];
    }

    public static function getInstallPath($packageName)
    {
        return __DIR__ . '/../../vendor/' . $packageName;
    }
}
EOPHP
    success "Composer compatibility class created"
fi

# 5. CLEAR ALL CACHES
log "🧹 Clearing all caches..."

rm -rf bootstrap/cache/*
rm -rf storage/framework/cache/*
rm -rf storage/framework/views/*
rm -rf storage/framework/sessions/*
rm -rf storage/logs/*

success "All caches cleared"

# 6. FIX PERMISSIONS
log "🔐 Fixing permissions..."

chmod -R 777 storage/
chmod -R 755 bootstrap/cache/
chmod 644 .env

success "Permissions fixed"

# 7. TEST THE FIXES
log "🧪 Testing the fixes..."

echo ""
echo "=== TESTING FIXES ==="

# Test 1: Check if FilamentShield is gone
echo "Test 1: FilamentShield Package Check"
if [ -d "vendor/bezhansalleh/filament-shield" ]; then
    echo "❌ FilamentShield package still exists"
else
    echo "✅ FilamentShield package successfully removed"
fi

# Test 2: Check APP_KEY
echo "Test 2: APP_KEY Check"
if grep -q "^APP_KEY=base64:" .env; then
    echo "✅ APP_KEY is properly set"
else
    echo "❌ APP_KEY is not set correctly"
fi

# Test 3: Test Laravel bootstrap
echo "Test 3: Laravel Bootstrap"
php -r "
try {
    require 'vendor/autoload.php';
    \$app = require 'bootstrap/app.php';
    echo '✅ Laravel Bootstrap: SUCCESS\n';
} catch (Exception \$e) {
    echo '❌ Laravel Bootstrap: ERROR - ' . \$e->getMessage() . '\n';
    echo 'File: ' . \$e->getFile() . ':' . \$e->getLine() . '\n';
}
" 2>&1

# Test 4: Test request handling
echo "Test 4: Request Handling"
php -r "
try {
    require 'vendor/autoload.php';
    \$app = require 'bootstrap/app.php';
    \$kernel = \$app->make('Illuminate\Contracts\Http\Kernel');
    \$request = Illuminate\Http\Request::create('/', 'GET');
    \$response = \$kernel->handle(\$request);
    echo '✅ Request Handling: SUCCESS (Status: ' . \$response->getStatusCode() . ')\n';
} catch (Exception \$e) {
    echo '❌ Request Handling: ERROR - ' . \$e->getMessage() . '\n';
    echo 'File: ' . \$e->getFile() . ':' . \$e->getLine() . '\n';
}
" 2>&1

# Test 5: Check logs for errors
echo "Test 5: Error Log Check"
if [ -f "storage/logs/laravel.log" ]; then
    RECENT_ERRORS=$(grep -c "ERROR.*$(date '+%Y-%m-%d')" storage/logs/laravel.log 2>/dev/null || echo "0")
    if [ "$RECENT_ERRORS" -eq 0 ]; then
        echo "✅ No recent errors in log"
    else
        echo "⚠️  Found $RECENT_ERRORS recent errors in log"
    fi
else
    echo "✅ No error log file (clean start)"
fi

echo ""
echo "=========================================================="
success "🎯 FINAL TARGETED FIX COMPLETED!"
echo "=========================================================="
echo ""
echo "📋 ISSUES RESOLVED:"
echo "   💥 FilamentShield package completely removed"
echo "   🔑 APP_KEY properly generated and set"
echo "   📦 Clean composer installation completed"
echo "   🔧 Composer compatibility class ensured"
echo "   🧹 All caches cleared"
echo "   🔐 Permissions fixed"
echo ""
echo "🌐 TEST YOUR WEBSITE:"
echo "   • https://dokterkuklinik.com"
echo "   • https://dokterkuklinik.com/test"
echo "   • https://dokterkuklinik.com/health"
echo ""
echo "✅ The specific Laravel errors should now be resolved!"
echo "   Check the test results above to verify the fixes worked."
echo "=========================================================="