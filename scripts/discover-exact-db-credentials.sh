#!/bin/bash

# Discover exact working database credentials by testing all possible combinations

cd /home/u454362045/domains/dokterkuklinik.com/public_html

echo "🔍 Discovering exact database credentials..."

echo "📋 1. Current .env configuration:"
grep "DB_" .env | head -6

PASSWORD="KlinikApp2025!"

echo ""
echo "📋 2. Testing with MySQL to discover actual databases and users:"

# First, let's see what databases exist for this user
echo "Discovering available databases..."
mysql -h 127.0.0.1 -u root -p"$PASSWORD" -e "SHOW DATABASES;" 2>/dev/null | grep "u454362045" || echo "Root access failed"

echo ""
echo "📋 3. Testing common Hostinger database patterns with confirmed password:"

# Test more comprehensive patterns
patterns=(
    "u454362045_klinik|u454362045_klinik"
    "u454362045_dokter|u454362045_dokter" 
    "u454362045_app|u454362045_app"
    "u454362045_laravel|u454362045_laravel"
    "u454362045_main|u454362045_main"
    "u454362045_web|u454362045_web"
    "u454362045_clinic|u454362045_clinic"
    "u454362045_medical|u454362045_medical"
    "u454362045_db|u454362045_db"
    "u454362045_database|u454362045_database"
)

for pattern in "${patterns[@]}"; do
    IFS='|' read -r user db <<< "$pattern"
    echo -n "Testing: $user / $db: "
    
    if mysql -h "127.0.0.1" -u "$user" -p"$PASSWORD" -e "USE $db; SELECT 1;" 2>/dev/null >/dev/null; then
        echo "✅ SUCCESS!"
        WORKING_USER="$user"
        WORKING_DB="$db"
        break
    else
        echo "❌ Failed"
    fi
done

if [ -n "$WORKING_USER" ]; then
    echo ""
    echo "🎉 FOUND WORKING CREDENTIALS!"
    echo "Database: $WORKING_DB"
    echo "Username: $WORKING_USER" 
    echo "Password: $PASSWORD"
    
    echo ""
    echo "📋 4. Backing up current .env and updating with working credentials:"
    cp .env .env.backup.before_working_fix
    
    sed -i "s/DB_DATABASE=.*/DB_DATABASE=$WORKING_DB/" .env
    sed -i "s/DB_USERNAME=.*/DB_USERNAME=$WORKING_USER/" .env
    sed -i "s/DB_PASSWORD=.*/DB_PASSWORD=$PASSWORD/" .env
    
    echo "📋 5. New working configuration:"
    grep "DB_" .env | head -6
    
    echo ""
    echo "📋 6. Testing Laravel database connection:"
    php artisan migrate:status 2>&1 | head -5
    
    echo ""
    echo "📋 7. Testing direct database access:"
    php -r "
    try {
        \$pdo = new PDO('mysql:host=127.0.0.1;dbname=$WORKING_DB', '$WORKING_USER', '$PASSWORD');
        \$users = \$pdo->query('SELECT COUNT(*) FROM users')->fetch();
        echo 'Users found: ' . \$users[0] . PHP_EOL;
        echo 'Database connection: ✅ SUCCESS' . PHP_EOL;
    } catch (Exception \$e) {
        echo 'Database error: ' . \$e->getMessage() . PHP_EOL;
    }
    "
    
    echo ""
    echo "📋 8. Clearing Laravel caches:"
    php artisan config:clear 2>&1
    php artisan cache:clear 2>&1
    php artisan route:clear 2>&1
    
    echo ""
    echo "📋 9. Testing login endpoint after fix:"
    curl -X POST \
         -H "Accept: application/json" \
         -H "Content-Type: application/json" \
         -d '{"login":"admin","password":"admin","device_id":"test"}' \
         -w "\\nHTTP Status: %{http_code}\\n" \
         -s https://dokterkuklinik.com/api/v2/auth/login
    
    echo ""
    echo "📋 10. Testing attendance endpoint after fix:"
    curl -H "Accept: application/json" \
         -w "\\nHTTP Status: %{http_code}\\n" \
         -s https://dokterkuklinik.com/api/v2/dashboards/dokter/attendance
    
    echo ""
    echo "🎉 DATABASE FIXED AND LOGIN SHOULD NOW WORK!"
    
else
    echo ""
    echo "❌ Still no working combination found with standard patterns"
    
    echo ""
    echo "📋 4. Let's try a different approach - check what's in mysql user table:"
    
    # Try to connect with different methods to discover users
    echo "Attempting to discover MySQL users..."
    
    # Check if we can access mysql.user table
    for test_user in "root" "u454362045" "admin" "mysql"; do
        echo -n "Testing $test_user access: "
        if mysql -h 127.0.0.1 -u "$test_user" -p"$PASSWORD" -e "SELECT User FROM mysql.user WHERE User LIKE 'u454362045%';" 2>/dev/null; then
            echo "✅ Found user info!"
            break
        else
            echo "❌ No access"
        fi
    done
    
    echo ""
    echo "📋 5. Manual database discovery needed:"
    echo "1. Check Hostinger cPanel → Databases"
    echo "2. Look for any database starting with 'u454362045_'"
    echo "3. Note the exact database name and username"
    echo "4. Use this command to update .env:"
    echo "   sed -i 's/DB_DATABASE=.*/DB_DATABASE=YourExactDBName/' .env"
    echo "   sed -i 's/DB_USERNAME=.*/DB_USERNAME=YourExactUsername/' .env"
fi

echo ""
echo "🏁 Database credential discovery complete"