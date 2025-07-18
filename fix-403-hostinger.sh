#!/bin/bash

# 🔧 Fix 403 Forbidden Error on Hostinger
# This script diagnoses and fixes common 403 errors

echo "🔍 DIAGNOSING 403 FORBIDDEN ERROR..."
echo "================================================"

# Check if we're in the right directory
echo "📁 Current directory structure:"
ls -la

echo ""
echo "📁 Checking public_html directory:"
ls -la public_html/ 2>/dev/null || echo "❌ public_html directory not found"

echo ""
echo "📁 Checking for Laravel files:"
ls -la index.php 2>/dev/null || echo "❌ No index.php in root"
ls -la public/index.php 2>/dev/null || echo "❌ No index.php in public/"

echo ""
echo "🔧 FIXING PERMISSIONS..."
echo "================================================"

# Fix directory permissions
find . -type d -exec chmod 755 {} \;
echo "✅ Directory permissions set to 755"

# Fix file permissions
find . -type f -exec chmod 644 {} \;
echo "✅ File permissions set to 644"

# Make storage writable
chmod -R 775 storage/
chmod -R 775 bootstrap/cache/
echo "✅ Storage and cache directories made writable"

echo ""
echo "🔄 SETTING UP DOCUMENT ROOT..."
echo "================================================"

# If public_html exists, copy Laravel public files there
if [ -d "public_html" ]; then
    echo "📁 Found public_html directory"
    
    # Copy Laravel public files to public_html
    cp -r public/* public_html/
    echo "✅ Copied Laravel public files to public_html"
    
    # Create symlink for storage
    if [ ! -L "public_html/storage" ]; then
        ln -s ../storage/app/public public_html/storage
        echo "✅ Created storage symlink"
    fi
    
    # Update index.php to point to correct paths
    sed -i 's|__DIR__\.\.\/\.\./vendor/autoload\.php|__DIR__\.\.\/vendor/autoload\.php|g' public_html/index.php
    sed -i 's|__DIR__\.\.\/\.\./bootstrap/app\.php|__DIR__\.\.\/bootstrap/app\.php|g' public_html/index.php
    echo "✅ Updated index.php paths"
else
    echo "❌ No public_html directory found"
    echo "📁 Creating public_html and copying files..."
    
    mkdir -p public_html
    cp -r public/* public_html/
    ln -s ../storage/app/public public_html/storage
    
    # Update paths in index.php
    sed -i 's|__DIR__\.\.\/\.\./vendor/autoload\.php|__DIR__\.\.\/vendor/autoload\.php|g' public_html/index.php
    sed -i 's|__DIR__\.\.\/\.\./bootstrap/app\.php|__DIR__\.\.\/bootstrap/app\.php|g' public_html/index.php
    
    echo "✅ Created public_html with Laravel files"
fi

echo ""
echo "🔐 CHECKING .HTACCESS..."
echo "================================================"

# Ensure .htaccess exists in public_html
if [ ! -f "public_html/.htaccess" ]; then
    echo "❌ No .htaccess in public_html"
    echo "📝 Creating .htaccess..."
    
    cat > public_html/.htaccess << 'EOF'
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
EOF
    
    echo "✅ Created .htaccess file"
else
    echo "✅ .htaccess already exists"
fi

echo ""
echo "🔍 FINAL CHECK..."
echo "================================================"

echo "📁 public_html contents:"
ls -la public_html/

echo ""
echo "📄 Checking index.php:"
head -n 5 public_html/index.php

echo ""
echo "🎯 NEXT STEPS:"
echo "1. SSH to your Hostinger server"
echo "2. Run this script: bash fix-403-hostinger.sh"
echo "3. Check if the website loads"
echo "4. If still 403, check Hostinger control panel document root settings"

echo ""
echo "✅ DIAGNOSTIC COMPLETE!"