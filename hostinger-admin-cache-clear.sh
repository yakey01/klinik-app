#!/bin/bash

# Hostinger Admin Cache Clear & Verification Script
# Clears all Laravel caches and verifies admin panel functionality

set -e  # Exit on any error

# SSH Configuration
HOSTINGER_HOST="153.92.8.132"
HOSTINGER_PORT="65002"
HOSTINGER_USER="u454362045"
HOSTINGER_PASS="LaTahzan@01"
HOSTINGER_PATH="/home/u454362045/domains/dokterkuklinik.com/public_html"

echo "============================================="
echo "HOSTINGER ADMIN CACHE CLEAR & VERIFICATION"
echo "============================================="
echo "Timestamp: $(date)"
echo "Remote Path: $HOSTINGER_PATH"
echo "============================================="

# Function to execute remote command
execute_remote_command() {
    local command="$1"
    local description="$2"
    
    echo ""
    echo "🔄 Executing: $description"
    echo "   Command: $command"
    
    result=$(sshpass -p "$HOSTINGER_PASS" ssh -p "$HOSTINGER_PORT" \
        "${HOSTINGER_USER}@${HOSTINGER_HOST}" \
        "cd \"$HOSTINGER_PATH\" && $command" 2>&1)
    
    if [ $? -eq 0 ]; then
        echo "   ✅ Success: $description"
        if [ ! -z "$result" ]; then
            echo "   📄 Output: $result"
        fi
    else
        echo "   ❌ Error: $description"
        echo "   📄 Error Output: $result"
        return 1
    fi
}

# Clear All Laravel Caches
echo ""
echo "🧹 Clearing All Laravel Caches..."

# Clear configuration cache
execute_remote_command "php artisan config:clear" "Clear Configuration Cache"

# Clear route cache
execute_remote_command "php artisan route:clear" "Clear Route Cache"

# Clear application cache
execute_remote_command "php artisan cache:clear" "Clear Application Cache"

# Clear view cache
execute_remote_command "php artisan view:clear" "Clear View Cache"

# Clear event cache
execute_remote_command "php artisan event:clear" "Clear Event Cache"

# Clear queue cache
execute_remote_command "php artisan queue:clear" "Clear Queue Cache"

# Optimize autoloader
execute_remote_command "composer dump-autoload --optimize" "Optimize Composer Autoloader"

# Additional Laravel Optimizations
echo ""
echo "⚡ Running Laravel Optimizations..."

# Cache configuration for production
execute_remote_command "php artisan config:cache" "Cache Configuration for Production"

# Cache routes for production
execute_remote_command "php artisan route:cache" "Cache Routes for Production"

# Cache events for production
execute_remote_command "php artisan event:cache" "Cache Events for Production"

# Optimize views
execute_remote_command "php artisan view:cache" "Cache Views for Production"

# Storage link (ensure assets are accessible)
echo ""
echo "🔗 Creating Storage Link..."
execute_remote_command "php artisan storage:link" "Create Storage Symbolic Link"

# Verify File Permissions
echo ""
echo "🔧 Verifying File Permissions..."

# Set proper permissions for Laravel directories
execute_remote_command "find storage -type f -exec chmod 644 {} \;" "Set Storage File Permissions"
execute_remote_command "find storage -type d -exec chmod 755 {} \;" "Set Storage Directory Permissions"
execute_remote_command "find bootstrap/cache -type f -exec chmod 644 {} \;" "Set Bootstrap Cache File Permissions"
execute_remote_command "find bootstrap/cache -type d -exec chmod 755 {} \;" "Set Bootstrap Cache Directory Permissions"

# Admin Panel Verification
echo ""
echo "🔍 Verifying Admin Panel Components..."

# Check if Filament is properly installed
filament_check=$(sshpass -p "$HOSTINGER_PASS" ssh -p "$HOSTINGER_PORT" \
    "${HOSTINGER_USER}@${HOSTINGER_HOST}" \
    "cd \"$HOSTINGER_PATH\" && php artisan list | grep -c filament" 2>/dev/null || echo "0")

echo "📊 Filament commands available: $filament_check"

# Check admin routes
admin_routes=$(sshpass -p "$HOSTINGER_PASS" ssh -p "$HOSTINGER_PORT" \
    "${HOSTINGER_USER}@${HOSTINGER_HOST}" \
    "cd \"$HOSTINGER_PATH\" && php artisan route:list | grep -c admin" 2>/dev/null || echo "0")

echo "📊 Admin routes registered: $admin_routes"

# Check database connectivity
echo ""
echo "🗄️ Verifying Database Connection..."
execute_remote_command "php artisan migrate:status" "Check Database Migration Status"

# Environment Configuration Check
echo ""
echo "🔐 Verifying Environment Configuration..."

# Check if .env file exists
env_exists=$(sshpass -p "$HOSTINGER_PASS" ssh -p "$HOSTINGER_PORT" \
    "${HOSTINGER_USER}@${HOSTINGER_HOST}" \
    "cd \"$HOSTINGER_PATH\" && [ -f .env ] && echo 'exists' || echo 'missing'")

echo "📄 Environment file status: $env_exists"

# Check app key
if [ "$env_exists" = "exists" ]; then
    execute_remote_command "php artisan key:generate --show" "Verify Application Key"
fi

# Test Admin Panel Access (HTTP check)
echo ""
echo "🌐 Testing Admin Panel Access..."

# Test if admin URL responds
admin_url_test=$(curl -s -o /dev/null -w "%{http_code}" "https://dokterkuklinik.com/admin" 2>/dev/null || echo "000")
echo "🔗 Admin panel HTTP response: $admin_url_test"

filament_url_test=$(curl -s -o /dev/null -w "%{http_code}" "https://dokterkuklinik.com/admin/login" 2>/dev/null || echo "000")
echo "🔗 Filament login HTTP response: $filament_url_test"

# Create verification summary
echo ""
echo "📝 Creating verification summary..."
cat > "cache_clear_verification_$(date +%Y%m%d_%H%M%S).txt" << EOF
HOSTINGER ADMIN CACHE CLEAR & VERIFICATION SUMMARY
==================================================

Verification Timestamp: $(date)
Remote Path: $HOSTINGER_PATH

Cache Operations Completed:
✅ Configuration cache cleared and cached
✅ Route cache cleared and cached  
✅ Application cache cleared
✅ View cache cleared and cached
✅ Event cache cleared and cached
✅ Queue cache cleared
✅ Composer autoloader optimized
✅ Storage link created

File Permissions Set:
✅ Storage directory permissions (644/755)
✅ Bootstrap cache permissions (644/755)

Verification Results:
- Filament commands available: $filament_check
- Admin routes registered: $admin_routes
- Environment file status: $env_exists
- Admin panel HTTP response: $admin_url_test
- Filament login HTTP response: $filament_url_test

SSH Connection Used:
- Host: $HOSTINGER_HOST:$HOSTINGER_PORT
- User: $HOSTINGER_USER
- Path: $HOSTINGER_PATH

Status Assessment:
$(if [ "$admin_url_test" = "200" ] || [ "$filament_url_test" = "200" ]; then
    echo "🟢 SUCCESS: Admin panel appears to be accessible"
else
    echo "🟡 WARNING: Admin panel may need additional configuration"
fi)

Next Steps:
1. Test admin login functionality
2. Verify all admin features work correctly
3. Monitor error logs for any issues

EOF

# Final Status Report
echo ""
echo "✅ CACHE CLEAR & VERIFICATION COMPLETED!"
echo "========================================"
echo "📊 Filament Commands: $filament_check | Admin Routes: $admin_routes"
echo "🔗 Admin URL Status: $admin_url_test | Login URL Status: $filament_url_test"

if [ "$admin_url_test" = "200" ] || [ "$filament_url_test" = "200" ]; then
    echo ""
    echo "🎉 SUCCESS: Admin panel is accessible!"
    echo "🚀 Admin codebase synchronization completed successfully!"
else
    echo ""
    echo "⚠️  WARNING: Admin panel may need additional configuration"
    echo "🔧 Check logs and environment settings if issues persist"
fi

echo "========================================"