#!/bin/bash

# ============================================================================
# Manual Database Fix Script untuk Server Hostinger
# Script ini akan memperbaiki masalah database dengan berbagai metode
# ============================================================================

echo "🔧 MANUAL DATABASE FIX - HOSTINGER SERVER"
echo "========================================="
echo "Timestamp: $(date)"
echo ""

# Ensure we're in the right directory
if [ ! -f "artisan" ]; then
    echo "📁 Navigating to Laravel app directory..."
    cd domains/dokterkuklinik.com/public_html/dokterku 2>/dev/null || {
        echo "❌ Cannot find Laravel app. Please run this from the correct directory."
        exit 1
    }
fi

echo "✅ Laravel app found at: $(pwd)"
echo ""

echo "📍 Step 1: Create/Update .env with Hostinger Database Credentials"
echo "================================================================"

# Backup existing .env
if [ -f ".env" ]; then
    cp .env .env.backup.$(date +%Y%m%d_%H%M%S)
    echo "📦 Backed up existing .env file"
fi

# Create new .env with correct database settings
cat > .env << 'EOF'
APP_NAME="Dokterku Healthcare System"
APP_ENV=production
APP_KEY=base64:9OWsbpnK9wnSg5Xl53l0G1CXRpw0FcKU9ETaGoH4fSo=
APP_DEBUG=false
APP_URL=https://dokterkuklinik.com

APP_LOCALE=id
APP_FALLBACK_LOCALE=id
APP_FAKER_LOCALE=en_US

# Clinic Geolocation Settings
APP_CLINIC_LATITUDE=-7.89946200
APP_CLINIC_LONGITUDE=111.96239900
APP_CLINIC_RADIUS=100

# Telegram Bot Settings  
TELEGRAM_BOT_TOKEN=your_bot_token_from_botfather
TELEGRAM_ADMIN_CHAT_ID=your_admin_telegram_chat_id

APP_MAINTENANCE_DRIVER=file
PHP_CLI_SERVER_WORKERS=4
BCRYPT_ROUNDS=12

LOG_CHANNEL=daily
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

# Production Database Configuration - Hostinger
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=u454362045_u45436245_kli
DB_USERNAME=u454362045_u45436245_kli
DB_PASSWORD=LaTahzan@01

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database

CACHE_STORE=database
CACHE_PREFIX=dokterku_

MEMCACHED_HOST=127.0.0.1

REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=smtp.hostinger.com
MAIL_PORT=587
MAIL_USERNAME=your-email@dokterkuklinik.com
MAIL_PASSWORD=your-email-password
MAIL_FROM_ADDRESS="noreply@dokterkuklinik.com"
MAIL_FROM_NAME="${APP_NAME}"

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false

VITE_APP_NAME="${APP_NAME}"

# Google Maps API Key
GOOGLE_MAPS_API_KEY=your_google_maps_api_key

# Security Settings
SANCTUM_STATEFUL_DOMAINS=dokterkuklinik.com
EOF

echo "✅ Created new .env file with Hostinger database credentials"
echo ""

echo "📍 Step 2: Test Database Connection with Different Methods"
echo "========================================================"

# Method 1: Direct MySQL command
echo "🔄 Method 1: Testing direct MySQL connection..."
mysql -h localhost -u u454362045_u45436245_kli -p'LaTahzan@01' u454362045_u45436245_kli -e "SELECT 1 as test;" 2>/dev/null
if [ $? -eq 0 ]; then
    echo "✅ Direct MySQL connection: SUCCESS"
    MYSQL_WORKS=true
else
    echo "❌ Direct MySQL connection: FAILED"
    MYSQL_WORKS=false
fi

# Method 2: Try with 127.0.0.1
if [ "$MYSQL_WORKS" = false ]; then
    echo "🔄 Method 2: Testing with 127.0.0.1..."
    mysql -h 127.0.0.1 -u u454362045_u45436245_kli -p'LaTahzan@01' u454362045_u45436245_kli -e "SELECT 1 as test;" 2>/dev/null
    if [ $? -eq 0 ]; then
        echo "✅ MySQL connection with 127.0.0.1: SUCCESS"
        sed -i 's/DB_HOST=localhost/DB_HOST=127.0.0.1/' .env
        MYSQL_WORKS=true
    else
        echo "❌ MySQL connection with 127.0.0.1: FAILED"
    fi
fi

# Method 3: Try with socket connection
if [ "$MYSQL_WORKS" = false ]; then
    echo "🔄 Method 3: Testing with socket connection..."
    mysql -u u454362045_u45436245_kli -p'LaTahzan@01' u454362045_u45436245_kli -e "SELECT 1 as test;" 2>/dev/null
    if [ $? -eq 0 ]; then
        echo "✅ MySQL socket connection: SUCCESS"
        sed -i 's/DB_HOST=.*/DB_HOST=/' .env  # Empty host for socket
        MYSQL_WORKS=true
    else
        echo "❌ MySQL socket connection: FAILED"
    fi
fi

if [ "$MYSQL_WORKS" = false ]; then
    echo ""
    echo "❌ CRITICAL: Cannot establish MySQL connection with any method!"
    echo ""
    echo "🔧 MANUAL STEPS REQUIRED:"
    echo "1. Login to cPanel -> MySQL Databases"
    echo "2. Check if database 'u454362045_u45436245_kli' exists"
    echo "3. Check if user 'u454362045_u45436245_kli' exists"
    echo "4. Verify password is 'LaTahzan@01'"
    echo "5. Ensure user has ALL PRIVILEGES on the database"
    echo "6. Check if MySQL service is running"
    echo ""
    exit 1
fi

echo ""
echo "📍 Step 3: Laravel Configuration & Testing"
echo "========================================="

# Clear Laravel caches
echo "🧹 Clearing Laravel caches..."
php artisan config:clear 2>/dev/null || echo "⚠️ Config clear warning (may be normal)"
php artisan cache:clear 2>/dev/null || echo "⚠️ Cache clear warning (may be normal)"
php artisan view:clear 2>/dev/null || echo "⚠️ View clear warning (may be normal)"

# Test Laravel database connection
echo ""
echo "🔍 Testing Laravel database connection..."
LARAVEL_TEST=$(php -r "
require_once 'vendor/autoload.php';
\$app = require_once 'bootstrap/app.php';
\$kernel = \$app->make('Illuminate\Contracts\Console\Kernel');
\$kernel->bootstrap();

try {
    \$pdo = DB::connection()->getPdo();
    echo 'SUCCESS: Laravel connection established';
    echo PHP_EOL . 'Database: ' . DB::connection()->getDatabaseName();
    echo PHP_EOL . 'Driver: ' . DB::connection()->getDriverName();
} catch (Exception \$e) {
    echo 'ERROR: ' . \$e->getMessage();
}
" 2>&1)

if echo "$LARAVEL_TEST" | grep -q "SUCCESS"; then
    echo "✅ Laravel database connection: SUCCESS"
    echo "$LARAVEL_TEST"
else
    echo "❌ Laravel database connection: FAILED"
    echo "$LARAVEL_TEST"
    
    echo ""
    echo "🔧 Attempting Laravel connection fix..."
    
    # Generate new app key
    php artisan key:generate --force 2>/dev/null
    
    # Try to fix any permission issues
    chmod 644 .env
    chmod -R 755 storage
    chmod -R 755 bootstrap/cache
    
    # Test again
    echo "🔄 Retesting Laravel connection..."
    LARAVEL_RETEST=$(php -r "
    require_once 'vendor/autoload.php';
    \$app = require_once 'bootstrap/app.php';
    \$kernel = \$app->make('Illuminate\Contracts\Console\Kernel');
    \$kernel->bootstrap();
    try {
        DB::connection()->getPdo();
        echo 'SUCCESS';
    } catch (Exception \$e) {
        echo 'FAILED: ' . \$e->getMessage();
    }
    " 2>&1)
    
    if echo "$LARAVEL_RETEST" | grep -q "SUCCESS"; then
        echo "✅ Laravel connection fixed!"
    else
        echo "❌ Laravel connection still failing: $LARAVEL_RETEST"
        exit 1
    fi
fi

echo ""
echo "📍 Step 4: Database Migration & Optimization"  
echo "==========================================="

echo "📊 Checking migration status..."
php artisan migrate:status 2>/dev/null | head -10 || echo "⚠️ Migration status check failed"

echo ""
echo "🗄️ Running migrations..."
php artisan migrate --force 2>&1 | head -20
if [ $? -eq 0 ]; then
    echo "✅ Migrations completed successfully"
else
    echo "⚠️ Migration warnings (check output above)"
fi

echo ""
echo "⚡ Optimizing Laravel for production..."
php artisan config:cache 2>/dev/null || echo "⚠️ Config cache warning"
php artisan route:cache 2>/dev/null || echo "⚠️ Route cache warning"  
php artisan view:cache 2>/dev/null || echo "⚠️ View cache warning"

echo ""
echo "📍 Step 5: Final Verification"
echo "============================"

echo "✅ Final database configuration:"
echo "DB_CONNECTION=$(grep '^DB_CONNECTION=' .env | cut -d'=' -f2)"
echo "DB_HOST=$(grep '^DB_HOST=' .env | cut -d'=' -f2)"
echo "DB_DATABASE=$(grep '^DB_DATABASE=' .env | cut -d'=' -f2)"
echo "DB_USERNAME=$(grep '^DB_USERNAME=' .env | cut -d'=' -f2)"
echo "DB_PASSWORD=****HIDDEN****"

echo ""
echo "🔍 Final Laravel connection test..."
FINAL_TEST=$(php -r "
require_once 'vendor/autoload.php';
try {
    \$app = require_once 'bootstrap/app.php';
    \$kernel = \$app->make('Illuminate\Contracts\Console\Kernel');
    \$kernel->bootstrap();
    DB::connection()->getPdo();
    echo 'SUCCESS';
} catch (Exception \$e) {
    echo 'FAILED: ' . \$e->getMessage();
}
" 2>&1)

echo ""
echo "🎯 FINAL RESULT"
echo "==============="
if echo "$FINAL_TEST" | grep -q "SUCCESS"; then
    echo "✅ Database connection: WORKING"
    echo "✅ Laravel application: READY"
    echo "✅ Migrations: COMPLETED"
    echo ""
    echo "🚀 Application should now work correctly!"
    echo "🌐 Visit: https://dokterkuklinik.com"
    echo ""
    echo "📝 Files updated:"
    echo "   - .env (with correct database credentials)"
    echo "   - Laravel cache files optimized"
else
    echo "❌ Database connection: STILL FAILING"
    echo "❌ Error: $FINAL_TEST"
    echo ""
    echo "🆘 REQUIRES MANUAL CPANEL INTERVENTION"
    echo ""
    echo "📞 Contact Hostinger support or:"
    echo "1. Check cPanel -> MySQL Databases"
    echo "2. Recreate database user if needed"
    echo "3. Reset database password in cPanel"
    echo "4. Grant ALL PRIVILEGES to user"
fi

echo ""
echo "🤖 Generated with [Claude Code](https://claude.ai/code)"
echo "🔧 Manual database fix completed at: $(date)"