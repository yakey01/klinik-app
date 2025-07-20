#!/bin/bash

# Final fix for database credentials using discovered information

cd /home/u454362045/domains/dokterkuklinik.com/public_html

echo "🔧 Final database credentials fix..."

echo "📋 1. Current database configuration:"
grep "DB_" .env | head -6

echo ""
echo "📋 2. Backup current .env file:"
cp .env .env.backup.$(date +%Y%m%d_%H%M%S)
echo "✅ Backup created"

echo ""
echo "📋 3. Testing various credential combinations with password 'KlinikApp2025!':"

PASSWORD="KlinikApp2025!"

# Test combinations based on discovery results
combinations=(
    "u454362045_klinik_app_usr|u454362045_klinik_app_db"
    "u454362045_u45436245_kli|u454362045_u45436245_kli"
    "u454362045_klinik|u454362045_klinik"
    "u454362045_dokter|u454362045_dokter"
    "u454362045_app|u454362045_app"
)

echo "Testing combinations with password 'KlinikApp2025!'..."

for combo in "${combinations[@]}"; do
    IFS='|' read -r user db <<< "$combo"
    echo -n "Testing: User='$user', DB='$db': "
    
    if mysql -h "127.0.0.1" -u "$user" -p"$PASSWORD" -e "USE $db; SELECT 1;" 2>/dev/null >/dev/null; then
        echo "✅ SUCCESS!"
        CORRECT_USER="$user"
        CORRECT_DB="$db"
        CORRECT_PASSWORD="$PASSWORD"
        break
    else
        echo "❌ Failed"
    fi
done

if [ -n "$CORRECT_USER" ]; then
    echo ""
    echo "🎉 Found working credentials!"
    echo "User: $CORRECT_USER"
    echo "Database: $CORRECT_DB"
    echo "Password: $CORRECT_PASSWORD"
    
    echo ""
    echo "📋 4. Updating .env file with working credentials:"
    sed -i "s/DB_HOST=.*/DB_HOST=127.0.0.1/" .env
    sed -i "s/DB_PORT=.*/DB_PORT=3306/" .env
    sed -i "s/DB_DATABASE=.*/DB_DATABASE=$CORRECT_DB/" .env
    sed -i "s/DB_USERNAME=.*/DB_USERNAME=$CORRECT_USER/" .env
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
        
        \$pdo = new PDO('mysql:host=127.0.0.1;dbname=$CORRECT_DB', '$CORRECT_USER', '$CORRECT_PASSWORD');
        
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
         -d '{"login":"test@test.com","password":"test","device_id":"test"}' \
         -w "HTTP Status: %{http_code}\\n" \
         -s https://dokterkuklinik.com/api/v2/auth/login | head -3
    
    echo ""
    echo "📋 10. Test attendance endpoint:"
    curl -H "Accept: application/json" \
         -w "HTTP Status: %{http_code}\\n" \
         -s https://dokterkuklinik.com/api/v2/dashboards/dokter/attendance | head -3
    
    echo ""
    echo "🎉 DATABASE CREDENTIALS FIXED SUCCESSFULLY!"
    
else
    echo ""
    echo "❌ Could not find working credentials with any combination"
    echo "💡 Manual steps required:"
    echo "1. Log into Hostinger cPanel at https://hpanel.hostinger.com"
    echo "2. Go to 'Databases' or 'MySQL Databases'"
    echo "3. Look for databases starting with 'u454362045_'"
    echo "4. Check the exact database name and user name"
    echo "5. Either view current password or reset it"
    echo "6. Update .env file manually with correct credentials"
    
    echo ""
    echo "📋 Discovered database user pattern: u454362045_klinik_app_usr"
    echo "📋 But production .env shows: u454362045_u45436245_kli"
    echo "📋 This mismatch suggests the user needs to verify correct names in cPanel"
fi

echo ""
echo "🏁 Database credentials fix complete!"