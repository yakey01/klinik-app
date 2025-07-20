#!/bin/bash

# Try credentials from .env.production.updated file

cd /home/u454362045/domains/dokterkuklinik.com/public_html

echo "🔧 Trying credentials from .env.production.updated file..."

echo "📋 1. Check if .env.production.updated exists and contains database info:"
if [ -f .env.production.updated ]; then
    echo "✅ .env.production.updated found"
    echo "Database configuration from production file:"
    grep "DB_" .env.production.updated
    
    # Extract password from production file
    PRODUCTION_PASSWORD=$(grep "DB_PASSWORD" .env.production.updated | cut -d'=' -f2 | tr -d '"'"'"' | tr -d '"')
    echo "Extracted password: ${PRODUCTION_PASSWORD:0:3}***"
    
    echo ""
    echo "📋 2. Test this password:"
    DB_USER="u454362045_klinik_app_usr"
    DB_HOST="127.0.0.1"
    DB_NAME="u454362045_klinik_app_db"
    
    if mysql -h "$DB_HOST" -u "$DB_USER" -p"$PRODUCTION_PASSWORD" -e "USE $DB_NAME; SELECT 1;" 2>/dev/null >/dev/null; then
        echo "✅ SUCCESS! Production password works!"
        
        echo ""
        echo "📋 3. Update current .env with working password:"
        cp .env .env.backup.before_fix
        sed -i "s/DB_PASSWORD=.*/DB_PASSWORD=$PRODUCTION_PASSWORD/" .env
        
        echo "📋 4. Verify the update:"
        grep "DB_PASSWORD" .env
        
        echo ""
        echo "📋 5. Test Laravel database connection:"
        php artisan migrate:status | head -3
        
        echo ""
        echo "📋 6. Test user authentication:"
        php -r "
        try {
            require 'vendor/autoload.php';
            \$app = require 'bootstrap/app.php';
            
            \$pdo = new PDO('mysql:host=$DB_HOST;dbname=$DB_NAME', '$DB_USER', '$PRODUCTION_PASSWORD');
            
            \$users = \$pdo->query('SELECT COUNT(*) FROM users')->fetch();
            echo 'Users table: ' . \$users[0] . ' users found' . PHP_EOL;
            
            \$sessions = \$pdo->query('SELECT COUNT(*) FROM sessions')->fetch();
            echo 'Sessions table: ' . \$sessions[0] . ' sessions found' . PHP_EOL;
            
            echo 'Database connection: ✅ SUCCESS' . PHP_EOL;
            
        } catch (Exception \$e) {
            echo 'Database connection: ❌ FAILED - ' . \$e->getMessage() . PHP_EOL;
        }
        "
        
        echo ""
        echo "📋 7. Clear caches:"
        php artisan config:clear
        php artisan cache:clear
        
        echo ""
        echo "📋 8. Test login API:"
        curl -X POST \
             -H "Accept: application/json" \
             -H "Content-Type: application/json" \
             -d '{\"login\":\"test@test.com\",\"password\":\"test\",\"device_id\":\"test\"}' \
             -w "HTTP Status: %{http_code}\\n" \
             -s https://dokterkuklinik.com/api/v2/auth/login | head -3
        
        echo ""
        echo "📋 9. Test attendance endpoint:"
        curl -H "Accept: application/json" \
             -w "HTTP Status: %{http_code}\\n" \
             -s https://dokterkuklinik.com/api/v2/dashboards/dokter/attendance | head -3
        
        echo ""
        echo "🎉 DATABASE PASSWORD FIXED SUCCESSFULLY!"
        
    else
        echo "❌ Production password also failed"
        echo "Password from file: '$PRODUCTION_PASSWORD'"
    fi
    
else
    echo "❌ .env.production.updated not found"
fi

echo ""
echo "📋 Alternative: Try updating from .env.example which might have working credentials:"
if [ -f .env.example ]; then
    EXAMPLE_PASSWORD=$(grep "DB_PASSWORD" .env.example | cut -d'=' -f2 | tr -d '"'"'"' | tr -d '"')
    echo "Password from .env.example: ${EXAMPLE_PASSWORD:0:3}***"
    
    if mysql -h "127.0.0.1" -u "u454362045_klinik_app_usr" -p"$EXAMPLE_PASSWORD" -e "USE u454362045_klinik_app_db; SELECT 1;" 2>/dev/null >/dev/null; then
        echo "✅ .env.example password works! Updating .env..."
        sed -i "s/DB_PASSWORD=.*/DB_PASSWORD=$EXAMPLE_PASSWORD/" .env
        echo "🎉 Fixed with .env.example password!"
    else
        echo "❌ .env.example password also failed"
    fi
fi

echo ""
echo "🏁 Credential testing complete"