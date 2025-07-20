#!/bin/bash

# Force deployment script to handle git conflicts and deploy attendance fix

cd /home/u454362045/domains/dokterkuklinik.com/public_html

echo "🚨 FORCE DEPLOYMENT: Resolving git conflicts..."

# Backup conflicting files
echo "📦 Backing up conflicting files..."
mkdir -p backup/$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="backup/$(date +%Y%m%d_%H%M%S)"

# Backup script files that have local changes
[ -f clean-slate.sh ] && cp clean-slate.sh $BACKUP_DIR/
[ -f database/migrations/backup_migrations.sh ] && cp database/migrations/backup_migrations.sh $BACKUP_DIR/
[ -f database/migrations/test_migrations.sh ] && cp database/migrations/test_migrations.sh $BACKUP_DIR/
[ -f deep-hostinger-fix.sh ] && cp deep-hostinger-fix.sh $BACKUP_DIR/
[ -f deploy-fix.sh ] && cp deploy-fix.sh $BACKUP_DIR/
[ -f deploy.sh ] && cp deploy.sh $BACKUP_DIR/
[ -f fix-env.sh ] && cp fix-env.sh $BACKUP_DIR/
[ -f fix-server.sh ] && cp fix-server.sh $BACKUP_DIR/
[ -f production-optimize.sh ] && cp production-optimize.sh $BACKUP_DIR/
[ -f public/.htaccess ] && cp public/.htaccess $BACKUP_DIR/
[ -f run_nonparamedis_tests.sh ] && cp run_nonparamedis_tests.sh $BACKUP_DIR/
[ -f ssl-setup.sh ] && cp ssl-setup.sh $BACKUP_DIR/
[ -f ultimate-forensic-fix.sh ] && cp ultimate-forensic-fix.sh $BACKUP_DIR/
[ -f .htaccess ] && cp .htaccess $BACKUP_DIR/

echo "✅ Files backed up to $BACKUP_DIR"

# Stash local changes and clean untracked files
echo "🧹 Stashing local changes and cleaning untracked files..."
git stash
git clean -fd

# Force reset to origin/main
echo "🔄 Force resetting to origin/main..."
git fetch origin
git reset --hard origin/main

echo "✅ Git conflicts resolved"

# Run composer install
echo "📦 Installing dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

# Run artisan commands
echo "🔧 Running Laravel setup..."
php artisan key:generate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run migrations
echo "🗄️ Running migrations..."
php artisan migrate --force

# Clear caches
echo "🧹 Clearing caches..."
php artisan cache:clear
php artisan config:clear

# Set permissions
echo "🔒 Setting permissions..."
chmod 755 storage/ -R
chmod 755 bootstrap/cache/ -R

# Test endpoints
echo "🌐 Testing critical endpoints..."
echo "Login page:"
curl -I https://dokterkuklinik.com/login 2>/dev/null | head -1

echo "Paramedis page:"
curl -I https://dokterkuklinik.com/paramedis 2>/dev/null | head -1

echo "Dokter page:"
curl -I https://dokterkuklinik.com/dokter 2>/dev/null | head -1

echo "Attendance API:"
curl -s https://dokterkuklinik.com/api/v2/dashboards/dokter/attendance 2>/dev/null | head -c 100

echo ""
echo "🚀 FORCE DEPLOYMENT COMPLETED!"
echo "📊 Backup location: $BACKUP_DIR"