#!/bin/bash
# Hostinger Deployment Verification Script
# Run after deployment to verify admin dashboard is working

echo "🔍 Verifying Hostinger deployment..."

# Check if EnhancedAdminDashboard exists
if [ -f "app/Filament/Pages/EnhancedAdminDashboard.php" ]; then
    echo "✅ EnhancedAdminDashboard.php found"
    echo "📝 Last modified: $(stat -c %y app/Filament/Pages/EnhancedAdminDashboard.php 2>/dev/null || stat -f %Sm app/Filament/Pages/EnhancedAdminDashboard.php)"
else
    echo "❌ EnhancedAdminDashboard.php NOT FOUND"
fi

# Check git status
echo "📋 Current Git status:"
git log --oneline -3

# Check if admin routes are available
echo "🛣️ Checking admin routes:"
php artisan route:list | grep -i admin | head -5

# Test basic Laravel functionality
echo "🧪 Testing Laravel application:"
php artisan --version

echo "📊 Cache status:"
php artisan config:cache
php artisan route:cache

echo "✅ Verification completed!"
echo "🌐 Test admin access at: https://dokterku.devplop.com/admin"