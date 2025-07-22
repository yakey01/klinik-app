#!/bin/bash

# Hostinger Deployment Script
# Konfigurasi server
SERVER_HOST="153.92.8.132"
SERVER_PORT="65002"
SERVER_USER="u454362045"
REMOTE_PATH="public_html"

echo "🚀 Starting deployment to Hostinger..."

# 1. Build production assets
echo "📦 Building production assets..."
npm run build

# 2. Check if sshpass is available
if ! command -v sshpass &> /dev/null; then
    echo "❌ sshpass not found. Installing with brew..."
    brew install hudochenkov/sshpass/sshpass
fi

# 3. Git operations
echo "📝 Committing changes..."
git add -A
git commit -m "Deploy to Hostinger: $(date)"
git push origin main

# 4. Deploy via SSH (manual step - requires password input)
echo "🔧 Connecting to Hostinger..."
echo "Please run the following commands manually:"
echo ""
echo "ssh -p $SERVER_PORT $SERVER_USER@$SERVER_HOST"
echo "cd $REMOTE_PATH"
echo "git pull origin main"
echo "composer install --optimize-autoloader --no-dev"
echo "php artisan config:cache"
echo "php artisan route:cache"
echo "php artisan view:cache"
echo "php artisan migrate --force"
echo ""

# Alternative: rsync deployment (if SSH keys are configured)
# echo "📤 Syncing files to server..."
# rsync -avz -e "ssh -p $SERVER_PORT" \
#   --exclude-from='.gitignore' \
#   --exclude='.git' \
#   --exclude='node_modules' \
#   --exclude='.env.local' \
#   ./ $SERVER_USER@$SERVER_HOST:$REMOTE_PATH/

echo "✅ Deployment preparation completed!"
echo "Please manually connect via SSH and run the commands above."