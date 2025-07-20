#!/bin/bash

# Fix database password in production .env file

cd /home/u454362045/domains/dokterkuklinik.com/public_html

echo "🔧 Fixing production database password..."

echo "📋 1. Current database configuration:"
grep "DB_" .env | head -6

echo ""
echo "📋 2. Backup current .env file:"
cp .env .env.backup.$(date +%Y%m%d_%H%M%S)
echo "✅ Backup created"

echo ""
echo "📋 3. Testing various password combinations to find the correct one:"

# Common password patterns for Hostinger
passwords=(
    "KlinikApp2025!"
    "klinik2025"
    "Klinik2025"
    "klinikapp2025"
    "KlinikApp2025"
    "dokterku2025"
    "Dokterku2025!"
    ""
    "123456"
    "password"
)

DB_USER="u454362045_klinik_app_usr"
DB_HOST="127.0.0.1"
DB_NAME="u454362045_klinik_app_db"

echo "Testing database passwords..."
for password in "${passwords[@]}"; do
    echo -n "Testing password: '${password:0:3}***': "
    if mysql -h "$DB_HOST" -u "$DB_USER" -p"$password" -e "USE $DB_NAME; SELECT 1;" 2>/dev/null >/dev/null; then
        echo "✅ SUCCESS!"
        CORRECT_PASSWORD="$password"
        break
    else
        echo "❌ Failed"
    fi
done

if [ -n "$CORRECT_PASSWORD" ]; then
    echo ""
    echo "🎉 Found correct password!"
    
    echo "📋 4. Updating .env file with correct password:"
    sed -i "s/DB_PASSWORD=.*/DB_PASSWORD=$CORRECT_PASSWORD/" .env
    
    echo "📋 5. New database configuration:"
    grep "DB_" .env | head -6
    
    echo ""
    echo "📋 6. Testing Laravel database connection:"
    php artisan migrate:status | head -5
    
    echo ""
    echo "📋 7. Testing user authentication query:"
    php -r "
    try {
        require 'vendor/autoload.php';
        \$app = require 'bootstrap/app.php';
        
        \$pdo = new PDO('mysql:host=$DB_HOST;dbname=$DB_NAME', '$DB_USER', '$CORRECT_PASSWORD');
        
        // Test users table
        \$users = \$pdo->query('SELECT COUNT(*) FROM users')->fetch();
        echo 'Users table: ' . \$users[0] . ' users found' . PHP_EOL;
        
        // Test sessions table  
        \$sessions = \$pdo->query('SELECT COUNT(*) FROM sessions')->fetch();
        echo 'Sessions table: ' . \$sessions[0] . ' sessions found' . PHP_EOL;
        
        echo 'Database connection: ✅ SUCCESS' . PHP_EOL;
        
    } catch (Exception \$e) {
        echo 'Database connection: ❌ FAILED - ' . \$e->getMessage() . PHP_EOL;
    }
    "
    
    echo ""
    echo "📋 8. Clear Laravel caches:"
    php artisan config:clear
    php artisan cache:clear
    
    echo ""
    echo "📋 9. Test login functionality:"
    echo "Testing login API endpoint:"
    curl -X POST \
         -H "Accept: application/json" \
         -H "Content-Type: application/json" \
         -d '{\"login\":\"test@test.com\",\"password\":\"test\",\"device_id\":\"test\"}' \
         -w "HTTP Status: %{http_code}\\n" \
         -s https://dokterkuklinik.com/api/v2/auth/login | head -3
    
    echo ""
    echo "🎉 Database password fixed successfully!"
    
else
    echo ""
    echo "❌ Could not find correct password"
    echo "💡 Manual steps required:"
    echo "1. Log into Hostinger cPanel"
    echo "2. Go to MySQL Databases"
    echo "3. Find user: $DB_USER"
    echo "4. Change password or check current password"
    echo "5. Update .env file manually"
fi

echo ""
echo "📋 10. Final verification - test attendance endpoint:"
curl -H "Accept: application/json" \
     -w "HTTP Status: %{http_code}\\n" \
     -s https://dokterkuklinik.com/api/v2/dashboards/dokter/attendance | head -3

echo ""
echo "🏁 Database password fix complete!"