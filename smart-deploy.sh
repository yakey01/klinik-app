#!/bin/bash

echo "🏆 SMART DEPLOYMENT: Bulletproof Dokter Fix"
echo "==========================================="

cd domains/dokterkuklinik.com/public_html

# Backup existing files
mkdir -p ~/backups/smart-fix/$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="~/backups/smart-fix/$(date +%Y%m%d_%H%M%S)"

[ -f "app/Http/Controllers/Api/DokterStatsController.php" ] && cp app/Http/Controllers/Api/DokterStatsController.php "$BACKUP_DIR/"
[ -f "routes/api.php" ] && cp routes/api.php "$BACKUP_DIR/"

# Deploy bulletproof controller
echo "📦 Deploying bulletproof controller..."
mkdir -p app/Http/Controllers/Api
cp bulletproof-DokterStatsController.php app/Http/Controllers/Api/DokterStatsController.php

# Replace routes/api.php entirely with bulletproof version
echo "🔧 Deploying bulletproof routes..."
cp bulletproof-routes.php routes/api.php

# Set permissions
chmod 644 app/Http/Controllers/Api/DokterStatsController.php
chmod 644 routes/api.php

# Clear ALL caches (nuclear option)
echo "🧹 Nuclear cache clear..."
rm -rf bootstrap/cache/*.php
rm -rf storage/framework/cache/data/*
rm -rf storage/framework/views/*

# Rebuild everything
php artisan config:clear
php artisan route:clear  
php artisan view:clear
php artisan cache:clear

# Rebuild caches
php artisan config:cache
php artisan route:cache

# Run composer dump-autoload to ensure class loading
composer dump-autoload --optimize

echo "✅ Smart deployment completed!"

# Test endpoints
echo "🧪 Testing endpoints..."
curl -s -o /dev/null -w "HTTP %{http_code} " "http://localhost/dokter" && echo ""
curl -s -o /dev/null -w "HTTP %{http_code} " "http://localhost/api/dokter/stats" && echo ""

echo "🏆 Smart fix deployment complete!"
