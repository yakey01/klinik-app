#!/bin/bash

# Update .env with REAL database credentials from Hostinger cPanel

cd /home/u454362045/domains/dokterkuklinik.com/public_html

echo "🔧 Updating .env with REAL database credentials from Hostinger cPanel..."

echo "📋 1. Current (incorrect) database configuration:"
grep "DB_" .env | head -6

echo ""
echo "📋 2. Backup current .env file:"
cp .env .env.backup.$(date +%Y%m%d_%H%M%S)
echo "✅ Backup created"

echo ""
echo "📋 3. Updating with REAL credentials from cPanel:"
echo "   Database Name: u454362045_u45436245_kli"
echo "   Username: u454362045_u45436245_kli"
echo "   Password: KlinikApp2025!"

# Update with actual database credentials from cPanel
sed -i 's/DB_DATABASE=.*/DB_DATABASE=u454362045_u45436245_kli/' .env
sed -i 's/DB_USERNAME=.*/DB_USERNAME=u454362045_u45436245_kli/' .env
sed -i 's/DB_PASSWORD=.*/DB_PASSWORD=KlinikApp2025!/' .env

echo ""
echo "📋 4. New (corrected) database configuration:"
grep "DB_" .env | head -6

echo ""
echo "📋 5. Testing database connection with REAL credentials:"
if mysql -h "127.0.0.1" -u "u454362045_u45436245_kli" -p"KlinikApp2025!" -e "USE u454362045_u45436245_kli; SELECT 1;" 2>/dev/null; then
    echo "✅ Database connection: SUCCESS!"
    
    echo ""
    echo "📋 6. Testing Laravel database access:"
    php artisan migrate:status 2>&1 | head -5
    
    echo ""
    echo "📋 7. Testing tables exist:"
    mysql -h "127.0.0.1" -u "u454362045_u45436245_kli" -p"KlinikApp2025!" -e "
    USE u454362045_u45436245_kli; 
    SHOW TABLES LIKE 'users';
    SELECT COUNT(*) as user_count FROM users;
    " 2>&1
    
else
    echo "❌ Database connection still failed"
    mysql -h "127.0.0.1" -u "u454362045_u45436245_kli" -p"KlinikApp2025!" -e "USE u454362045_u45436245_kli; SELECT 1;" 2>&1
fi

echo ""
echo "📋 8. Clear Laravel caches:"
php artisan config:clear 2>&1
php artisan cache:clear 2>&1
php artisan route:clear 2>&1

echo ""
echo "📋 9. Test login endpoint after fix:"
curl -X POST \
     -H "Accept: application/json" \
     -H "Content-Type: application/json" \
     -d '{"login":"admin","password":"admin","device_id":"test"}' \
     -w "\nHTTP Status: %{http_code}\n" \
     -s https://dokterkuklinik.com/api/v2/auth/login

echo ""
echo "📋 10. Test attendance endpoint after fix:"
curl -H "Accept: application/json" \
     -w "\nHTTP Status: %{http_code}\n" \
     -s https://dokterkuklinik.com/api/v2/dashboards/dokter/attendance

echo ""
echo "🎉 REAL database credentials updated! 500 errors should now be resolved!"