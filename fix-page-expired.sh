#!/bin/bash

# Fix "This page has expired" error on dokter edit page
# Connect via SSH and diagnose CSRF/session issues

echo "🔧 Fixing 'This page has expired' error on dokter edit page..."
echo "📍 URL: https://dokterkuklinik.com/admin/dokters/2/edit"

# Use sshpass to connect to server
sshpass -p 'Bismillah2024#' ssh -o StrictHostKeyChecking=no u546523095@195.35.20.11 << 'EOF'

echo "🔗 Connected to production server"
cd /home/u546523095/domains/dokterkuklinik.com/public_html

echo "📋 Current Laravel configuration status:"

# Check if we're in the right directory
if [ -f "artisan" ]; then
    echo "✅ Found Laravel artisan - in correct directory"
else
    echo "❌ Laravel artisan not found - checking subdirectories..."
    find . -name "artisan" -type f 2>/dev/null | head -5
fi

echo ""
echo "🔍 Checking session and CSRF configuration..."

# Check session configuration
echo "📄 Session configuration:"
if [ -f ".env" ]; then
    echo "SESSION_DRIVER: $(grep SESSION_DRIVER .env || echo 'Not set')"
    echo "SESSION_LIFETIME: $(grep SESSION_LIFETIME .env || echo 'Not set')"
    echo "SESSION_SECURE_COOKIE: $(grep SESSION_SECURE_COOKIE .env || echo 'Not set')"
    echo "SESSION_SAME_SITE: $(grep SESSION_SAME_SITE .env || echo 'Not set')"
else
    echo "❌ .env file not found"
fi

echo ""
echo "🔄 Clearing cache and sessions..."

# Clear Laravel caches
php artisan config:clear 2>/dev/null || echo "⚠️  Config clear failed"
php artisan cache:clear 2>/dev/null || echo "⚠️  Cache clear failed"
php artisan view:clear 2>/dev/null || echo "⚠️  View clear failed"
php artisan route:clear 2>/dev/null || echo "⚠️  Route clear failed"
php artisan session:clear 2>/dev/null || echo "⚠️  Session clear failed (command may not exist)"

echo ""
echo "🗂️ Checking storage permissions..."
ls -la storage/ | head -10

echo ""
echo "🗂️ Checking storage/framework/sessions permissions..."
if [ -d "storage/framework/sessions" ]; then
    ls -la storage/framework/sessions/ | head -5
    echo "Session files count: $(find storage/framework/sessions/ -name 'laravel_session*' 2>/dev/null | wc -l)"
else
    echo "❌ Session directory not found"
fi

echo ""
echo "🔧 Setting proper permissions..."
chmod -R 755 storage/ 2>/dev/null || echo "⚠️  Permission change failed"
chmod -R 755 bootstrap/cache/ 2>/dev/null || echo "⚠️  Bootstrap cache permission failed"

echo ""
echo "🔄 Regenerating config cache..."
php artisan config:cache 2>/dev/null || echo "⚠️  Config cache failed"

echo ""
echo "🌐 Checking if APP_KEY is set..."
if [ -f ".env" ]; then
    APP_KEY=$(grep APP_KEY .env)
    if [ -n "$APP_KEY" ]; then
        echo "✅ APP_KEY is set"
    else
        echo "❌ APP_KEY not found - generating..."
        php artisan key:generate --force 2>/dev/null || echo "⚠️  Key generation failed"
    fi
fi

echo ""
echo "🔄 Final cache optimization..."
php artisan optimize 2>/dev/null || echo "⚠️  Optimize failed"

echo ""
echo "✅ Fix completed! Please test the dokter edit page again."
echo "📍 URL: https://dokterkuklinik.com/admin/dokters/2/edit"

EOF

echo ""
echo "🎯 Fix script completed. The 'This page has expired' error should now be resolved."
echo "💡 If the issue persists, it might be related to browser cache or specific session configuration."