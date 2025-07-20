#!/bin/bash

# Find Hostinger database credentials

cd /home/u454362045/domains/dokterkuklinik.com/public_html

echo "🔍 Finding Hostinger database credentials..."

echo "📋 1. Check for Hostinger configuration files:"
echo "Looking in user home directory..."
find /home/u454362045 -name "*.conf" -o -name "*.cfg" -o -name "*.ini" 2>/dev/null | head -10

echo ""
echo "📋 2. Check for WordPress or other CMS configs that might have DB info:"
find /home/u454362045/domains -name "wp-config.php" -o -name "config.php" -o -name "database.php" 2>/dev/null | head -5

echo ""
echo "📋 3. Check for .my.cnf or MySQL config files:"
ls -la /home/u454362045/.my.cnf 2>/dev/null || echo "No .my.cnf found"
ls -la /home/u454362045/mysql.conf 2>/dev/null || echo "No mysql.conf found"

echo ""
echo "📋 4. Look for backup files that might contain working credentials:"
find /home/u454362045 -name "*.sql" -o -name "*backup*" -o -name "*.bak" 2>/dev/null | head -10

echo ""
echo "📋 5. Check Laravel .env backup files for working password:"
ls -la .env.* 2>/dev/null | head -5
echo ""
if [ -f .env.backup.* ]; then
    latest_backup=$(ls -t .env.backup.* | head -1)
    echo "Latest backup: $latest_backup"
    echo "Password from backup:"
    grep "DB_PASSWORD" "$latest_backup" 2>/dev/null || echo "No password found"
fi

echo ""
echo "📋 6. Check environment variables:"
env | grep -i db | head -5

echo ""
echo "📋 7. Look for Hostinger-specific files:"
find /home/u454362045 -name "*hostinger*" -o -name "*hpanel*" 2>/dev/null | head -5

echo ""
echo "📋 8. Test if we can connect with empty password:"
mysql -h 127.0.0.1 -u u454362045_klinik_app_usr -p'' -e "SELECT 1;" 2>&1 | head -2

echo ""
echo "📋 9. Try to get password from Laravel itself (if it's cached):"
php -r "
try {
    // Try to get cached config
    if (file_exists('bootstrap/cache/config.php')) {
        \$config = include 'bootstrap/cache/config.php';
        if (isset(\$config['database']['connections']['mysql']['password'])) {
            echo 'Cached password found: [' . strlen(\$config['database']['connections']['mysql']['password']) . ' chars]' . PHP_EOL;
        }
    }
} catch (Exception \$e) {
    echo 'Could not read cached config' . PHP_EOL;
}
"

echo ""
echo "📋 10. Check if there are any MySQL processes running that might give hints:"
ps aux | grep mysql | head -3

echo ""
echo "📋 11. Check phpinfo for database configuration (if available):"
php -r "
if (function_exists('phpinfo')) {
    ob_start();
    phpinfo();
    \$info = ob_get_clean();
    if (strpos(\$info, 'mysql') !== false) {
        echo 'MySQL support detected in PHP' . PHP_EOL;
    }
}
echo 'PHP MySQL extensions: ';
echo extension_loaded('mysql') ? 'mysql ' : '';
echo extension_loaded('mysqli') ? 'mysqli ' : '';
echo extension_loaded('pdo_mysql') ? 'pdo_mysql ' : '';
echo PHP_EOL;
"

echo ""
echo "🎯 NEXT STEPS if automatic detection failed:"
echo "1. Log into Hostinger control panel (hpanel)"
echo "2. Go to 'Databases' or 'MySQL Databases'"
echo "3. Find database user: u454362045_klinik_app_usr"
echo "4. Either:"
echo "   a) View the current password, or"
echo "   b) Change the password to something new (e.g., 'NewPassword2025!')"
echo "5. Update the .env file with the correct password"
echo ""
echo "🔧 Manual .env update command:"
echo "sed -i 's/DB_PASSWORD=.*/DB_PASSWORD=YourActualPassword/' .env"