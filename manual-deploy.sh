#!/bin/bash

echo "🚀 MANUAL DEPLOYMENT TO HOSTINGER"
echo "=================================="

# Variables
REMOTE_USER="u454362045"
REMOTE_HOST="dokterkuklinik.com"
LOCAL_PATH="/Users/kym/Herd/Dokterku"
REMOTE_PATH="~/public_html"

echo "📋 Deployment Info:"
echo "- Source: $LOCAL_PATH"
echo "- Target: $REMOTE_USER@$REMOTE_HOST:$REMOTE_PATH"
echo ""

# Create a temporary deployment directory
echo "📦 Preparing deployment package..."
cd "$LOCAL_PATH"

# Create temporary directory
rm -rf /tmp/dokterku-deploy
mkdir -p /tmp/dokterku-deploy

# Copy all files except excluded ones
echo "📁 Copying files..."
rsync -av --exclude='.git' \
          --exclude='node_modules' \
          --exclude='tests' \
          --exclude='storage/logs/*.log' \
          --exclude='.env' \
          --exclude='database/database.sqlite' \
          . /tmp/dokterku-deploy/

# Copy production env
cp .env.production /tmp/dokterku-deploy/.env

cd /tmp/dokterku-deploy

# Install dependencies
echo "📦 Installing Composer dependencies..."
composer install --no-dev --optimize-autoloader --no-scripts

# Install NPM and build assets
echo "🏗️ Building assets..."
npm ci
npm run build

# Remove development files
echo "🧹 Cleaning up..."
rm -rf node_modules
rm -rf .git
rm -rf tests
rm -f package-lock.json

# Create deployment archive
echo "📦 Creating deployment archive..."
cd /tmp
tar -czf dokterku-deploy.tar.gz dokterku-deploy/

# Upload to server
echo "🚀 Uploading to server..."
scp -i ~/.ssh/dokterku_deploy dokterku-deploy.tar.gz $REMOTE_USER@$REMOTE_HOST:~/

# Execute deployment on server
echo "🔧 Executing deployment on server..."
ssh -i ~/.ssh/dokterku_deploy $REMOTE_USER@$REMOTE_HOST << 'EOF'
    echo "🗑️ Backing up current site..."
    if [ -d "public_html_backup" ]; then
        rm -rf public_html_backup
    fi
    if [ -d "public_html" ]; then
        mv public_html public_html_backup
    fi
    
    echo "📦 Extracting new files..."
    tar -xzf dokterku-deploy.tar.gz
    mv dokterku-deploy public_html
    
    cd public_html
    
    echo "🔧 Setting up Laravel..."
    
    # Generate app key
    php artisan key:generate --force
    
    # Set permissions
    find . -type d -exec chmod 755 {} \;
    find . -type f -exec chmod 644 {} \;
    chmod -R 775 storage bootstrap/cache
    chmod 644 .env
    
    # Move Laravel public files to document root
    if [ -d "public" ] && [ ! -f "index.php" ]; then
        echo "📁 Moving Laravel public files to document root..."
        cp -r public/* .
        # Update index.php paths
        sed -i 's|__DIR__\.\.\/\.\./vendor/autoload\.php|__DIR__\.\.\/vendor/autoload\.php|g' index.php
        sed -i 's|__DIR__\.\.\/\.\./bootstrap/app\.php|__DIR__\.\.\/bootstrap/app\.php|g' index.php
        echo "✅ Laravel public files moved to document root"
    fi
    
    # Create .htaccess
    if [ ! -f ".htaccess" ]; then
        echo "📝 Creating .htaccess..."
        cat > .htaccess << 'HTACCESS'
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>

    RewriteEngine On

    # Handle Authorization Header
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

    # Redirect Trailing Slashes If Not A Folder...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]

    # Send Requests To Front Controller...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>
HTACCESS
        echo "✅ .htaccess created"
    fi
    
    # Run Laravel setup
    echo "🗄️ Setting up database..."
    php artisan migrate --force
    
    echo "🔗 Creating storage symlink..."
    php artisan storage:link
    
    echo "⚡ Optimizing for production..."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    php artisan event:cache
    
    # Final permissions
    chmod -R 755 storage bootstrap/cache
    
    echo "🧪 Testing deployment..."
    php -l index.php
    
    echo "📋 Final directory structure:"
    ls -la
    
    echo "✅ DEPLOYMENT COMPLETE!"
    echo "🌐 Visit: https://dokterkuklinik.com"
    
    # Cleanup
    rm -f ~/dokterku-deploy.tar.gz
EOF

# Cleanup local temp files
echo "🧹 Cleaning up local temp files..."
rm -rf /tmp/dokterku-deploy*

echo ""
echo "🎉 MANUAL DEPLOYMENT COMPLETED!"
echo "=================================="
echo "🌐 Website: https://dokterkuklinik.com"
echo "🔧 Admin: https://dokterkuklinik.com/admin"
echo ""
echo "If there are still issues, check the server manually:"
echo "ssh -i ~/.ssh/dokterku_deploy $REMOTE_USER@$REMOTE_HOST"