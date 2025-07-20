#!/bin/bash

# Fix Production 500 Error Script
# This script fixes Laravel bootstrap issues on production

echo "🔧 Starting Production 500 Error Fix..."
echo "======================================"

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

# Production path
PROD_PATH="/home/u454362045/domains/dokterkuklinik.com/public_html"

echo -e "${YELLOW}Step 1: Checking PHP Version${NC}"
php -v

echo -e "\n${YELLOW}Step 2: Backup current .env${NC}"
cp $PROD_PATH/.env $PROD_PATH/.env.backup.$(date +%Y%m%d_%H%M%S) 2>/dev/null || echo "No existing .env to backup"

echo -e "\n${YELLOW}Step 3: Generate proper .env file${NC}"
cd $PROD_PATH

# Create a working .env file
cat > .env.production << 'EOF'
APP_NAME="KLINIK DOKTERKU"
APP_ENV=production
APP_KEY=base64:YOUR_KEY_WILL_BE_GENERATED
APP_DEBUG=false
APP_URL=https://dokterkuklinik.com

# Database - CRITICAL: Must match production
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=u454362045_klinik_app_db
DB_USERNAME=u454362045_klinik_app_usr
DB_PASSWORD="YourActualPasswordHere"

# Session & Cache - Use file driver for stability
SESSION_DRIVER=file
SESSION_LIFETIME=120
CACHE_STORE=file
QUEUE_CONNECTION=sync

# Logging
LOG_CHANNEL=single
LOG_LEVEL=error

# Other settings
BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
MEMCACHED_HOST=127.0.0.1
REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Mail (disabled for now)
MAIL_MAILER=log
EOF

echo -e "\n${YELLOW}Step 4: Merge with existing .env if exists${NC}"
if [ -f .env ]; then
    # Extract database password from existing .env
    DB_PASS=$(grep "^DB_PASSWORD=" .env | cut -d'=' -f2- | tr -d '"')
    if [ ! -z "$DB_PASS" ]; then
        sed -i "s/YourActualPasswordHere/$DB_PASS/" .env.production
        echo "✅ Database password preserved from existing .env"
    fi
fi

echo -e "\n${YELLOW}Step 5: Replace .env with fixed version${NC}"
mv .env.production .env

echo -e "\n${YELLOW}Step 6: Generate APP_KEY${NC}"
php artisan key:generate --force

echo -e "\n${YELLOW}Step 7: Fix permissions${NC}"
chmod 644 .env
chmod -R 755 storage bootstrap/cache
chown -R $(whoami):$(whoami) storage bootstrap/cache

echo -e "\n${YELLOW}Step 8: Clear everything${NC}"
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

echo -e "\n${YELLOW}Step 9: Rebuild composer autoload${NC}"
composer dump-autoload --optimize --no-scripts

echo -e "\n${YELLOW}Step 10: Discover packages${NC}"
php artisan package:discover --ansi || echo "Package discovery failed, continuing..."

echo -e "\n${YELLOW}Step 11: Cache for production${NC}"
php artisan config:cache
php artisan route:cache

echo -e "\n${YELLOW}Step 12: Test Laravel${NC}"
php artisan --version && echo -e "${GREEN}✅ Laravel is working!${NC}" || echo -e "${RED}❌ Laravel still broken${NC}"

echo -e "\n${YELLOW}Step 13: Create test endpoint${NC}"
cat > public/test-production.php << 'EOF'
<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "PHP Version: " . PHP_VERSION . "\n";
echo "Server: " . $_SERVER['SERVER_SOFTWARE'] . "\n";

// Test autoloader
if (file_exists(__DIR__.'/../vendor/autoload.php')) {
    echo "✅ Vendor autoload exists\n";
    require __DIR__.'/../vendor/autoload.php';
    
    // Test Laravel bootstrap
    try {
        $app = require_once __DIR__.'/../bootstrap/app.php';
        echo "✅ Laravel bootstrap successful\n";
    } catch (Exception $e) {
        echo "❌ Laravel bootstrap failed: " . $e->getMessage() . "\n";
    }
} else {
    echo "❌ Vendor autoload missing!\n";
}

// Check .env
if (file_exists(__DIR__.'/../.env')) {
    echo "✅ .env file exists\n";
    $env = parse_ini_file(__DIR__.'/../.env');
    echo "APP_NAME: " . ($env['APP_NAME'] ?? 'NOT SET') . "\n";
    echo "APP_KEY: " . (empty($env['APP_KEY']) ? 'EMPTY!' : 'SET') . "\n";
    echo "DB_CONNECTION: " . ($env['DB_CONNECTION'] ?? 'NOT SET') . "\n";
} else {
    echo "❌ .env file missing!\n";
}
EOF

echo -e "\n${GREEN}✅ Fix script completed!${NC}"
echo "======================================"
echo "Test URLs:"
echo "1. https://dokterkuklinik.com/test-production.php"
echo "2. https://dokterkuklinik.com/login"
echo "3. https://dokterkuklinik.com/paramedis"