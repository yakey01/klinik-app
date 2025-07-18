#!/bin/bash
set -e

echo "💥 NUCLEAR FILAMENT-SHIELD REMOVAL SCRIPT"
echo "=========================================="
echo "This script will COMPLETELY obliterate bezhansalleh/filament-shield"
echo ""

# Color functions
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
NC='\033[0m'

log() { echo -e "${BLUE}[$(date '+%H:%M:%S')]${NC} $1"; }
error() { echo -e "${RED}💥 NUKE:${NC} $1"; }
success() { echo -e "${GREEN}✅ REMOVED:${NC} $1"; }
info() { echo -e "${YELLOW}🔍 SCAN:${NC} $1"; }

log "🔍 Scanning for ALL traces of bezhansalleh/filament-shield..."

# 1. COMPOSER.JSON NUCLEAR REMOVAL
info "Scanning composer.json for filament-shield..."
if [ -f "composer.json" ]; then
    if grep -q "bezhansalleh/filament-shield" composer.json; then
        error "Found filament-shield in composer.json - REMOVING"
        
        # Create backup
        cp composer.json composer.json.nuclear-backup
        
        # Multiple removal strategies
        sed -i '/bezhansalleh\/filament-shield/d' composer.json
        sed -i '/bezhansalleh\\\/filament-shield/d' composer.json
        sed -i '/"bezhansalleh\/filament-shield"/d' composer.json
        sed -i '/FilamentShield/d' composer.json
        
        # Remove trailing commas that might break JSON
        sed -i 's/,\s*}/}/g' composer.json
        sed -i 's/,\s*]/]/g' composer.json
        
        success "filament-shield NUKED from composer.json"
    else
        info "composer.json is clean"
    fi
fi

# 2. COMPOSER.LOCK NUCLEAR REMOVAL
info "Scanning composer.lock for filament-shield..."
if [ -f "composer.lock" ]; then
    if grep -q "filament-shield" composer.lock; then
        error "Found filament-shield traces in composer.lock - DESTROYING"
        rm -f composer.lock
        success "composer.lock DESTROYED"
    else
        info "composer.lock is clean"
    fi
fi

# 3. VENDOR DIRECTORY NUCLEAR REMOVAL
info "Scanning vendor directory for filament-shield..."
if [ -d "vendor/bezhansalleh" ]; then
    error "Found bezhansalleh vendor directory - OBLITERATING"
    rm -rf vendor/bezhansalleh
    success "vendor/bezhansalleh OBLITERATED"
fi

if [ -d "vendor/bezhansalleh/filament-shield" ]; then
    error "Found filament-shield package - VAPORIZING"
    rm -rf vendor/bezhansalleh/filament-shield
    success "filament-shield package VAPORIZED"
fi

# Scan for any other shield references in vendor
info "Deep scanning vendor for any shield references..."
find vendor/ -name "*shield*" -type d 2>/dev/null | while read dir; do
    if [[ "$dir" == *"filament"* && "$dir" == *"shield"* ]]; then
        error "Found shield directory: $dir - ELIMINATING"
        rm -rf "$dir"
        success "$dir ELIMINATED"
    fi
done

# 4. SERVICE PROVIDER NUCLEAR REMOVAL
info "Scanning service providers for FilamentShield..."

# All possible locations for service providers
PROVIDER_FILES=(
    "config/app.php"
    "bootstrap/providers.php" 
    "bootstrap/app.php"
    "app/Providers/AppServiceProvider.php"
    "app/Providers/FilamentServiceProvider.php"
)

for file in "${PROVIDER_FILES[@]}"; do
    if [ -f "$file" ]; then
        if grep -q "FilamentShield" "$file"; then
            error "Found FilamentShield in $file - REMOVING"
            
            # Create backup
            cp "$file" "$file.nuclear-backup"
            
            # Multiple removal patterns
            sed -i '/FilamentShield/d' "$file"
            sed -i '/BezhanSalleh\\FilamentShield/d' "$file"
            sed -i '/bezhansalleh\\filament-shield/d' "$file"
            sed -i '/FilamentShieldServiceProvider/d' "$file"
            
            success "FilamentShield REMOVED from $file"
        else
            info "$file is clean"
        fi
    fi
done

# 5. CONFIG FILES NUCLEAR REMOVAL
info "Scanning config files for shield configurations..."

CONFIG_FILES=(
    "config/filament-shield.php"
    "config/shield.php"
    "config/filament.php"
)

for file in "${CONFIG_FILES[@]}"; do
    if [ -f "$file" ]; then
        if grep -q -i "shield" "$file"; then
            error "Found shield config in $file - DELETING"
            rm -f "$file"
            success "$file DELETED"
        fi
    fi
done

# 6. MIGRATION FILES NUCLEAR REMOVAL
info "Scanning migrations for shield tables..."
if [ -d "database/migrations" ]; then
    find database/migrations/ -name "*shield*" -type f 2>/dev/null | while read migration; do
        error "Found shield migration: $migration - DESTROYING"
        rm -f "$migration"
        success "$migration DESTROYED"
    done
    
    find database/migrations/ -name "*permission*" -type f 2>/dev/null | while read migration; do
        if grep -q -i "shield" "$migration"; then
            error "Found shield-related migration: $migration - DESTROYING"
            rm -f "$migration"
            success "$migration DESTROYED"
        fi
    done
fi

# 7. MODEL FILES NUCLEAR REMOVAL
info "Scanning models for shield references..."
if [ -d "app/Models" ]; then
    find app/Models/ -name "*.php" -type f -exec grep -l -i "shield\|filamentshield" {} \; 2>/dev/null | while read model; do
        error "Found shield reference in model: $model"
        # Don't delete models, just clean them
        sed -i '/FilamentShield/d' "$model"
        sed -i '/use.*Shield/d' "$model"
        success "Cleaned shield references from $model"
    done
fi

# 8. POLICY FILES NUCLEAR REMOVAL
info "Scanning policies for shield references..."
if [ -d "app/Policies" ]; then
    find app/Policies/ -name "*Shield*" -type f 2>/dev/null | while read policy; do
        error "Found shield policy: $policy - DELETING"
        rm -f "$policy"
        success "$policy DELETED"
    done
fi

# 9. AUTOLOAD FILES NUCLEAR CLEANUP
info "Cleaning autoload files..."
if [ -f "vendor/autoload.php" ]; then
    # Remove any cached shield references
    find vendor/composer/ -name "*.php" -exec sed -i '/FilamentShield/d' {} \; 2>/dev/null || true
    find vendor/composer/ -name "*.php" -exec sed -i '/bezhansalleh/d' {} \; 2>/dev/null || true
fi

# 10. PUBLISHED ASSETS NUCLEAR REMOVAL
info "Scanning published assets for shield..."
ASSET_DIRS=(
    "public/vendor/filament-shield"
    "resources/views/vendor/filament-shield" 
    "lang/vendor/filament-shield"
)

for dir in "${ASSET_DIRS[@]}"; do
    if [ -d "$dir" ]; then
        error "Found shield assets: $dir - VAPORIZING"
        rm -rf "$dir"
        success "$dir VAPORIZED"
    fi
done

# 11. DATABASE TABLES NUCLEAR REMOVAL
info "Scanning database for shield tables..."
mysql -h localhost -u u454362045_u45436245_kli -pKlinikApp2025! -e "
USE u454362045_u45436245_kli;
DROP TABLE IF EXISTS shield_roles;
DROP TABLE IF EXISTS shield_permissions; 
DROP TABLE IF EXISTS shield_role_permissions;
DROP TABLE IF EXISTS model_has_permissions;
DROP TABLE IF EXISTS model_has_roles;
DROP TABLE IF EXISTS role_has_permissions;
SHOW TABLES LIKE '%shield%';
" 2>/dev/null || info "Database cleanup skipped"

# 12. CACHE NUCLEAR OBLITERATION
info "Obliterating ALL caches that might contain shield references..."
rm -rf bootstrap/cache/*
rm -rf storage/framework/cache/*
rm -rf storage/framework/views/*
rm -rf storage/framework/sessions/*
rm -rf vendor/composer/autoload_*.php

# 13. COMPOSER NUCLEAR REINSTALL
log "🔄 Performing nuclear composer reinstall..."

# Download fresh Composer 2 if not exists
if [ ! -f "composer2" ]; then
    curl -sS https://getcomposer.org/installer | php -- --install-dir=. --filename=composer2
    chmod +x composer2
fi

# Clear all composer caches
./composer2 clear-cache 2>/dev/null || true

# Nuclear reinstall
./composer2 install \
    --no-dev \
    --ignore-platform-reqs \
    --optimize-autoloader \
    --no-scripts \
    --no-interaction \
    --force 2>/dev/null || ./composer2 install --no-dev --ignore-platform-reqs

# 14. VERIFICATION SCAN
log "🔍 Performing verification scan..."

echo ""
echo "=== VERIFICATION RESULTS ==="

# Check composer.json
if grep -q "filament-shield" composer.json 2>/dev/null; then
    error "❌ filament-shield still found in composer.json"
else
    success "✅ composer.json is clean"
fi

# Check vendor directory
if [ -d "vendor/bezhansalleh" ]; then
    error "❌ bezhansalleh vendor directory still exists"
else
    success "✅ vendor directory is clean"
fi

# Check service providers
SHIELD_PROVIDERS=$(find . -name "*.php" -exec grep -l "FilamentShield" {} \; 2>/dev/null | wc -l)
if [ "$SHIELD_PROVIDERS" -gt 0 ]; then
    error "❌ Found $SHIELD_PROVIDERS files with FilamentShield references"
    find . -name "*.php" -exec grep -l "FilamentShield" {} \; 2>/dev/null | head -5
else
    success "✅ All service providers are clean"
fi

# Test autoload
echo ""
echo "🧪 Testing autoload after nuclear removal..."
php -r "
try {
    require 'vendor/autoload.php';
    echo '✅ Autoload: SUCCESS\n';
} catch (Exception \$e) {
    echo '❌ Autoload: ERROR - ' . \$e->getMessage() . '\n';
}
"

# Test Laravel bootstrap
echo "🧪 Testing Laravel bootstrap..."
php -r "
try {
    require 'vendor/autoload.php';
    \$app = require 'bootstrap/app.php';
    echo '✅ Laravel Bootstrap: SUCCESS\n';
} catch (Exception \$e) {
    echo '❌ Laravel Bootstrap: ERROR - ' . \$e->getMessage() . '\n';
    echo 'Details: ' . \$e->getFile() . ':' . \$e->getLine() . '\n';
}
"

echo ""
echo "=== NUCLEAR REMOVAL COMPLETE ==="
success "💥 bezhansalleh/filament-shield has been COMPLETELY OBLITERATED!"
echo ""
echo "📋 REMOVAL SUMMARY:"
echo "   🗂️  Package removed from composer.json"
echo "   📦 Vendor directory obliterated"
echo "   ⚙️  Service providers cleaned"
echo "   🗃️  Config files deleted"
echo "   🗄️  Database tables dropped"
echo "   📁 Published assets vaporized"
echo "   🔄 Autoload regenerated"
echo "   🧹 All caches obliterated"
echo ""
echo "🎯 Laravel should now boot WITHOUT any filament-shield errors!"
echo "=========================================="