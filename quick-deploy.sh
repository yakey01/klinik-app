#!/bin/bash

# Ultra-simple deployment script
echo "🚀 Quick Deploy: Pegawai Email Migration"
echo "========================================"

read -s -p "🔐 SSH Password: " PASSWORD
echo

echo "📡 Deploying migration to production..."

sshpass -p "$PASSWORD" ssh -o StrictHostKeyChecking=no u196138154@srv556.hstgr.io << 'DEPLOY_SCRIPT'
cd /home/u196138154/domains/dokterkuklinik.com/public_html
echo "🔄 Pulling latest code..."
git pull origin main
echo "🗃️  Running migration..."
php artisan migrate --force
echo "🧹 Clearing caches..."
php artisan optimize:clear
echo "✅ Done! Test at: https://dokterkuklinik.com/admin/pegawais/1/edit"
DEPLOY_SCRIPT

echo "🎉 Deployment complete!"