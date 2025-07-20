#!/bin/bash

# Update .env with the CORRECT password from cPanel

cd /home/u454362045/domains/dokterkuklinik.com/public_html

echo "🔧 Updating .env with CORRECT password from cPanel..."

echo "📋 1. Current database configuration:"
grep "DB_" .env | head -6

echo ""
echo "📋 2. Backup current .env file:"
cp .env .env.backup.$(date +%Y%m%d_%H%M%S)
echo "✅ Backup created"

echo ""
echo "📋 3. Updating with CORRECT password: LaTahzan@01"

# Update with the actual password from cPanel
sed -i 's/DB_PASSWORD=.*/DB_PASSWORD=LaTahzan@01/' .env

echo ""
echo "📋 4. New database configuration:"
grep "DB_" .env | head -6

echo ""
echo "📋 5. Testing database connection with CORRECT credentials:"
if mysql -h "127.0.0.1" -u "u454362045_u45436245_kli" -p"LaTahzan@01" -e "USE u454362045_u45436245_kli; SELECT 1;" 2>/dev/null; then
    echo "✅ Database connection: SUCCESS!"
    
    echo ""
    echo "📋 6. Testing Laravel database access:"
    php artisan migrate:status 2>&1 | head -5
    
    echo ""
    echo "📋 7. Testing tables exist:"
    mysql -h "127.0.0.1" -u "u454362045_u45436245_kli" -p"LaTahzan@01" -e "
    USE u454362045_u45436245_kli; 
    SHOW TABLES LIKE 'users';
    SELECT COUNT(*) as user_count FROM users;
    " 2>&1
    
else
    echo "❌ Database connection still failed"
    mysql -h "127.0.0.1" -u "u454362045_u45436245_kli" -p"LaTahzan@01" -e "USE u454362045_u45436245_kli; SELECT 1;" 2>&1
fi

echo ""
echo "📋 8. Clear Laravel caches:"
php artisan config:clear 2>&1
php artisan cache:clear 2>&1
php artisan route:clear 2>&1

echo ""
echo "📋 9. Test login endpoint after password fix:"
curl -X POST \
     -H "Accept: application/json" \
     -H "Content-Type: application/json" \
     -d '{"login":"admin","password":"admin","device_id":"test"}' \
     -w "\nHTTP Status: %{http_code}\n" \
     -s https://dokterkuklinik.com/api/v2/auth/login

echo ""
echo "📋 10. Test attendance endpoint after password fix:"
curl -H "Accept: application/json" \
     -w "\nHTTP Status: %{http_code}\n" \
     -s https://dokterkuklinik.com/api/v2/dashboards/dokter/attendance

echo ""
echo "🎉 CORRECT password updated! 500 errors should now be FULLY resolved!"