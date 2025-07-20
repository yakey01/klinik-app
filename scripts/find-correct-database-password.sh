#!/bin/bash

# Find the correct database password for production

cd /home/u454362045/domains/dokterkuklinik.com/public_html

echo "🔍 Finding correct database password..."

echo "📋 Current database issue:"
php artisan migrate:status 2>&1 | head -3

echo ""
echo "🔍 Checking Hostinger database configuration files..."

# Check common hostinger configuration locations
if [ -f /home/u454362045/.my.cnf ]; then
    echo "Found .my.cnf file:"
    cat /home/u454362045/.my.cnf | grep -i pass
fi

echo ""
echo "🔍 Checking for database info in home directory..."
ls -la /home/u454362045/ | grep -E "\.(txt|info|config)" | head -5

echo ""
echo "🔍 Looking for database setup files..."
find /home/u454362045/ -name "*database*" -o -name "*mysql*" -o -name "*db*" 2>/dev/null | head -10

echo ""
echo "🔍 Checking previous .env backups for working password..."
if ls .env.* 1> /dev/null 2>&1; then
    echo "Found .env backup files:"
    for file in .env.*; do
        echo "=== $file ==="
        grep "DB_PASSWORD" "$file" 2>/dev/null || echo "No DB_PASSWORD found"
    done
fi

echo ""
echo "🔍 Trying to extract database info from Laravel configuration..."
# Try to get database config from Laravel
php -r "
try {
    require 'vendor/autoload.php';
    \$app = require 'bootstrap/app.php';
    \$config = \$app->make('config');
    echo 'DB Host: ' . \$config->get('database.connections.mysql.host') . PHP_EOL;
    echo 'DB Database: ' . \$config->get('database.connections.mysql.database') . PHP_EOL;
    echo 'DB Username: ' . \$config->get('database.connections.mysql.username') . PHP_EOL;
    echo 'DB Password: [length: ' . strlen(\$config->get('database.connections.mysql.password')) . ']' . PHP_EOL;
} catch (Exception \$e) {
    echo 'Error loading config: ' . \$e->getMessage() . PHP_EOL;
}
" 2>/dev/null || echo "Could not load Laravel config"

echo ""
echo "🔍 Checking if password might be in environment or elsewhere..."
# Try common Hostinger password patterns
echo "Testing common password patterns..."

# Try empty password
mysql -h 127.0.0.1 -u u454362045_klinik_app_usr -p'' -e "SELECT 1;" 2>&1 | head -1

# Try username as password
mysql -h 127.0.0.1 -u u454362045_klinik_app_usr -p'u454362045_klinik_app_usr' -e "SELECT 1;" 2>&1 | head -1

# Try database name as password
mysql -h 127.0.0.1 -u u454362045_klinik_app_usr -p'u454362045_klinik_app_db' -e "SELECT 1;" 2>&1 | head -1

echo ""
echo "💡 Recommendation: Check Hostinger cPanel for correct database password"
echo "   1. Log into Hostinger cPanel"
echo "   2. Go to MySQL Databases"
echo "   3. Find user: u454362045_klinik_app_usr"
echo "   4. Update password or check current one"