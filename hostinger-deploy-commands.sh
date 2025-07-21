#!/bin/bash
# Hostinger Deployment Commands for Admin Dashboard Restore
# Run these commands on Hostinger server via SSH

echo "🚀 Starting Hostinger deployment for restored admin dashboard..."

# Navigate to project directory
cd /home/u476871830/domains/dokterku.devplop.com/public_html

# Backup current state before pulling
echo "📦 Creating backup of current state..."
cp -r app/Filament/Pages/EnhancedAdminDashboard.php app/Filament/Pages/EnhancedAdminDashboard.php.backup.$(date +%Y%m%d_%H%M%S) 2>/dev/null || echo "No existing dashboard to backup"

# Pull the latest changes from main branch
echo "⬇️ Pulling restored admin dashboard from Git..."
git pull origin main

# Clear all Laravel caches
echo "🧹 Clearing Laravel caches..."
php artisan config:clear
php artisan cache:clear  
php artisan route:clear
php artisan view:clear

# Optimize for production
echo "⚡ Optimizing for production..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "✅ Deployment completed! Admin dashboard restore deployed to Hostinger."
echo "🌐 Access admin panel at: https://dokterku.devplop.com/admin"