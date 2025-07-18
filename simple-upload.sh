#!/bin/bash

echo "🚀 SIMPLE UPLOAD TO HOSTINGER"
echo "=============================="

# Create a clean deployment package
cd /Users/kym/Herd/Dokterku

# Clean up previous builds
rm -rf /tmp/dokterku-clean
mkdir -p /tmp/dokterku-clean

# Copy essential files only
echo "📁 Copying essential files..."
cp -r app /tmp/dokterku-clean/
cp -r bootstrap /tmp/dokterku-clean/
cp -r config /tmp/dokterku-clean/
cp -r database /tmp/dokterku-clean/
cp -r lang /tmp/dokterku-clean/
cp -r public /tmp/dokterku-clean/
cp -r resources /tmp/dokterku-clean/
cp -r routes /tmp/dokterku-clean/
cp -r storage /tmp/dokterku-clean/
cp artisan /tmp/dokterku-clean/
cp composer.json /tmp/dokterku-clean/
cp .env.production /tmp/dokterku-clean/.env
cp package.json /tmp/dokterku-clean/
cp vite.config.js /tmp/dokterku-clean/
cp tailwind.config.js /tmp/dokterku-clean/
cp postcss.config.js /tmp/dokterku-clean/

# Create basic .htaccess
echo "📝 Creating .htaccess..."
cat > /tmp/dokterku-clean/.htaccess << 'EOF'
<IfModule mod_rewrite.c>
    Options -MultiViews -Indexes
    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>
EOF

# Create a simple index.php in root
echo "📄 Creating root index.php..."
cat > /tmp/dokterku-clean/index.php << 'EOF'
<?php
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Kernel::class);

$response = $kernel->handle(
    $request = Request::capture()
)->send();

$kernel->terminate($request, $response);
EOF

echo "✅ Clean deployment package created in /tmp/dokterku-clean"
echo ""
echo "📋 Next steps:"
echo "1. Zip the /tmp/dokterku-clean directory"
echo "2. Upload via Hostinger File Manager"
echo "3. Extract in public_html"
echo "4. Install dependencies via SSH"
echo ""
echo "🎯 Manual commands for after upload:"
echo "composer install --no-dev --optimize-autoloader"
echo "npm install && npm run build"
echo "php artisan key:generate"
echo "php artisan migrate"
echo "php artisan storage:link"
echo "php artisan optimize"

# Create zip file
cd /tmp
zip -r dokterku-clean.zip dokterku-clean/

echo ""
echo "✅ Created /tmp/dokterku-clean.zip"
echo "📁 Upload this file to Hostinger and extract to public_html"