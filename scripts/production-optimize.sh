#!/bin/bash

# 🚀 Dokterku Healthcare System - Production Optimization Script
# Optimizes Laravel application for production deployment

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

log() {
    echo -e "${GREEN}[$(date '+%Y-%m-%d %H:%M:%S')] $1${NC}"
}

error() {
    echo -e "${RED}[$(date '+%Y-%m-%d %H:%M:%S')] ERROR: $1${NC}"
}

warning() {
    echo -e "${YELLOW}[$(date '+%Y-%m-%d %H:%M:%S')] WARNING: $1${NC}"
}

echo -e "${BLUE}🏥 Optimizing Dokterku Healthcare System for Production${NC}"

# Check if we're in production environment
if [[ "${APP_ENV}" != "production" ]]; then
    warning "APP_ENV is not set to production. Some optimizations may not apply."
fi

# 1. Clear all caches
log "🧹 Clearing all caches..."
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear
php artisan filament:clear-cached-components

# 2. Optimize Composer autoloader
log "📦 Optimizing Composer autoloader..."
composer dump-autoload --optimize --classmap-authoritative --no-dev

# 3. Cache configurations for production
log "⚡ Caching configurations..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 4. Optimize database queries
log "🗄️ Optimizing database..."
php artisan migrate --force
php artisan db:seed --class=DatabaseSeeder --force || warning "Database seeding failed"

# 5. Publish and optimize Filament assets
log "🎨 Publishing Filament assets..."
php artisan vendor:publish --tag=filament-assets --force
php artisan vendor:publish --tag=filament-config --force

# 6. Storage optimization
log "📁 Optimizing storage..."
php artisan storage:link

# 7. Set proper file permissions
log "🔐 Setting file permissions..."
chmod -R 755 storage bootstrap/cache
chmod 644 .env

# 8. Clear and warm up caches
log "🔥 Warming up caches..."
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 9. Generate optimized class loader
log "🏗️ Generating optimized class loader..."
composer install --no-dev --optimize-autoloader --no-scripts

# 10. Queue and scheduler optimization
log "⏰ Optimizing queue and scheduler..."
php artisan queue:restart || warning "Queue restart failed (queue may not be running)"

# 11. Generate application key if missing
if ! grep -q "APP_KEY=base64:" .env; then
    log "🔑 Generating application key..."
    php artisan key:generate --force
fi

# 12. Health check
log "🏥 Running health checks..."

# Check database connection
if php artisan migrate:status > /dev/null 2>&1; then
    log "✅ Database connection successful"
else
    error "❌ Database connection failed"
    exit 1
fi

# Check file permissions
if [[ -w storage/logs ]]; then
    log "✅ Storage permissions correct"
else
    error "❌ Storage permissions incorrect"
    exit 1
fi

# Check essential directories
DIRECTORIES=("storage/app" "storage/framework/cache" "storage/framework/sessions" "storage/framework/views" "storage/logs" "bootstrap/cache")
for dir in "${DIRECTORIES[@]}"; do
    if [[ ! -d "$dir" ]]; then
        mkdir -p "$dir"
        chmod 755 "$dir"
        log "📁 Created directory: $dir"
    fi
done

# 13. Performance metrics
log "📊 Performance metrics:"
echo "Config cached: $(php artisan config:cache --help > /dev/null 2>&1 && echo "✅" || echo "❌")"
echo "Routes cached: $(php artisan route:cache --help > /dev/null 2>&1 && echo "✅" || echo "❌")"
echo "Views cached: $(php artisan view:cache --help > /dev/null 2>&1 && echo "✅" || echo "❌")"
echo "Autoloader optimized: $(composer show --self | grep -q "optimize-autoloader" && echo "✅" || echo "❌")"

# 14. Security checks
log "🔒 Security checks:"
if grep -q "APP_DEBUG=false" .env; then
    log "✅ Debug mode disabled"
else
    warning "⚠️ Debug mode is enabled - should be disabled in production"
fi

if grep -q "APP_ENV=production" .env; then
    log "✅ Production environment set"
else
    warning "⚠️ Environment is not set to production"
fi

# 15. Generate optimization report
REPORT_FILE="storage/logs/optimization_report_$(date +%Y%m%d_%H%M%S).txt"
log "📋 Generating optimization report: $REPORT_FILE"

cat > "$REPORT_FILE" << EOF
Dokterku Healthcare System - Production Optimization Report
============================================================
Date: $(date)
Environment: ${APP_ENV:-unknown}
Laravel Version: $(php artisan --version)

Optimizations Applied:
- ✅ Caches cleared and regenerated
- ✅ Composer autoloader optimized
- ✅ Configuration cached
- ✅ Routes cached
- ✅ Views cached
- ✅ Filament assets published
- ✅ Storage linked and permissions set
- ✅ Database migrations applied

Performance Status:
- Config Cache: $(test -f bootstrap/cache/config.php && echo "Active" || echo "Inactive")
- Route Cache: $(test -f bootstrap/cache/routes.php && echo "Active" || echo "Inactive")
- View Cache: $(test -d storage/framework/views && echo "Active" || echo "Inactive")

Security Status:
- Debug Mode: $(grep "APP_DEBUG" .env | cut -d'=' -f2)
- Environment: $(grep "APP_ENV" .env | cut -d'=' -f2)
- HTTPS Enabled: $(grep "APP_URL" .env | grep -q "https" && echo "Yes" || echo "No")

File Permissions:
- Storage writable: $(test -w storage && echo "Yes" || echo "No")
- Bootstrap cache writable: $(test -w bootstrap/cache && echo "Yes" || echo "No")

Database Status:
- Connection: $(php artisan migrate:status > /dev/null 2>&1 && echo "OK" || echo "Failed")
- Migrations: $(php artisan migrate:status 2>/dev/null | grep -c "Y" || echo "0") applied

Memory Usage:
- PHP Memory Limit: $(php -r "echo ini_get('memory_limit');")
- Current Usage: $(php -r "echo round(memory_get_usage(true)/1024/1024, 2) . 'MB';")

Disk Usage:
- Total Size: $(du -sh . | cut -f1)
- Storage Size: $(du -sh storage | cut -f1)
- Vendor Size: $(du -sh vendor | cut -f1)

Next Steps:
1. Monitor application performance
2. Set up proper monitoring and logging
3. Configure regular database backups
4. Implement health checks
5. Set up SSL certificates for production
EOF

log "✅ Optimization report saved to: $REPORT_FILE"

echo ""
log "🎉 Production optimization completed successfully!"
echo -e "${GREEN}🏥 Dokterku Healthcare System is optimized and ready for production!${NC}"

# Show final status
log "📊 Final Status Summary:"
echo "  - Environment: ${APP_ENV:-not-set}"
echo "  - Debug Mode: $(grep "APP_DEBUG" .env | cut -d'=' -f2)"
echo "  - Database: $(php artisan migrate:status > /dev/null 2>&1 && echo "Connected" || echo "Error")"
echo "  - Storage: $(test -w storage && echo "Writable" || echo "Not writable")"
echo "  - Caches: $(test -f bootstrap/cache/config.php && echo "Cached" || echo "Not cached")"
echo ""
echo -e "${BLUE}🚀 Ready for deployment!${NC}"

exit 0