#!/bin/bash

echo "🔧 Comprehensive Laravel Fix"
echo "============================"

cd domains/dokterkuklinik.com/public_html

echo "1. Fixing .htaccess files..."
# Create root .htaccess
cat > .htaccess << 'EOF'
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^(.*)$ public/$1 [L]
</IfModule>
EOF

# Update public/.htaccess
cat > public/.htaccess << 'EOF'
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>
    RewriteEngine On
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>
EOF

echo "2. Fixing Pail service provider error..."
# Remove Pail service provider from config/app.php
sed -i '/Laravel\\Pail\\PailServiceProvider/d' config/app.php

echo "3. Setting permissions..."
chmod -R 755 storage/
chmod -R 755 bootstrap/cache/
chmod 644 public/.htaccess
chmod 644 .env

echo "4. Checking .env file..."
if [ ! -f .env ]; then
    cp .env.example .env
    php artisan key:generate
fi

echo "5. Installing dependencies..."
composer install --no-dev --optimize-autoloader

echo "6. Clearing all caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

echo "7. Regenerating autoload..."
composer dump-autoload

echo "8. Testing Laravel..."
php artisan --version

echo "9. Creating test file..."
cat > public/test.php << 'EOF'
<?php
echo "PHP is working!<br>";
echo "PHP version: " . phpversion() . "<br>";
echo "Laravel should be working now!";
?>
EOF

echo "✅ Comprehensive fix completed!"
echo "🌐 Test your website: https://dokterkuklinik.com"
echo "🧪 Test PHP: https://dokterkuklinik.com/test.php" 