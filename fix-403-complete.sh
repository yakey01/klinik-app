#!/bin/bash

echo "🔧 Complete 403 Fix Script"
echo "=========================="

# Navigate to the correct directory
cd domains/dokterkuklinik.com/public_html

echo "1. Setting correct permissions..."
chmod -R 755 storage/
chmod -R 755 bootstrap/cache/
chmod 644 public/.htaccess
chmod 644 .env

echo "2. Creating/updating .htaccess files..."

# Create root .htaccess to redirect to public
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

echo "3. Checking .env file..."
if [ ! -f .env ]; then
    echo "   Creating .env from .env.example..."
    cp .env.example .env
    php artisan key:generate
else
    echo "   .env file exists"
fi

echo "4. Clearing all caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

echo "5. Optimizing for production..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "6. Setting final permissions..."
find storage -type d -exec chmod 755 {} \;
find storage -type f -exec chmod 644 {} \;
find bootstrap/cache -type d -exec chmod 755 {} \;
find bootstrap/cache -type f -exec chmod 644 {} \;

echo "7. Testing Laravel..."
php artisan --version

echo "8. Checking file structure..."
ls -la public/
ls -la storage/
ls -la bootstrap/cache/

echo "✅ 403 fix completed!"
echo "🌐 Test your website: https://dokterkuklinik.com" 