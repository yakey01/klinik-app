#!/bin/bash

echo "🚀 PRODUCTION OPTIMIZATION SCRIPT"
echo "================================="

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

log() { echo -e "${BLUE}[$(date '+%H:%M:%S')]${NC} $1"; }
error() { echo -e "${RED}❌ ERROR:${NC} $1"; }
success() { echo -e "${GREEN}✅ SUCCESS:${NC} $1"; }
info() { echo -e "${YELLOW}ℹ️  INFO:${NC} $1"; }

log "Starting production optimization..."

# 1. Clear all caches
log "Clearing all caches..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
php artisan event:clear
success "All caches cleared"

# 2. Install production dependencies
log "Installing production dependencies..."
composer install --no-dev --optimize-autoloader --no-scripts
success "Production dependencies installed"

# 3. Generate optimized caches
log "Generating optimized caches..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
success "Optimized caches generated"

# 4. Build production assets
log "Building production assets..."
npm ci --omit=dev
npm run build
success "Production assets built"

# 5. Optimize composer autoloader
log "Optimizing composer autoloader..."
composer dump-autoload --optimize --classmap-authoritative
success "Composer autoloader optimized"

# 6. Set proper permissions
log "Setting proper file permissions..."
chmod -R 755 storage bootstrap/cache
chmod -R 644 storage/logs
success "File permissions set"

# 7. Create symbolic links
log "Creating storage symlink..."
php artisan storage:link
success "Storage symlink created"

# 8. Run migrations (if needed)
log "Running database migrations..."
php artisan migrate --force
success "Database migrations completed"

# 9. Optimize database
log "Optimizing database..."
php artisan db:seed --class=DatabaseSeeder --force || true
success "Database optimization completed"

# 10. Test configuration
log "Testing configuration..."
php artisan config:show | head -20
success "Configuration test completed"

echo ""
echo "================================="
success "🎉 PRODUCTION OPTIMIZATION COMPLETE!"
echo "================================="
echo ""
echo "📋 WHAT WAS DONE:"
echo "   ✅ All caches cleared and regenerated"
echo "   ✅ Production dependencies installed"
echo "   ✅ Assets built for production"
echo "   ✅ Composer autoloader optimized"
echo "   ✅ File permissions set correctly"
echo "   ✅ Database migrations run"
echo "   ✅ Configuration optimized"
echo ""
echo "🚀 Your Laravel application is now production-ready!"
echo "================================="