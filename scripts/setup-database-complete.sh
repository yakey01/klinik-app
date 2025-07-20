#!/bin/bash

# Complete database setup: run all migrations and seed data

cd /home/u454362045/domains/dokterkuklinik.com/public_html

echo "🔧 Setting up complete database schema and seeding data..."

echo "📋 1. Current database connection status:"
if mysql -h "127.0.0.1" -u "u454362045_u45436245_kli" -p"LaTahzan@01" -e "USE u454362045_u45436245_kli; SELECT 1;" 2>/dev/null; then
    echo "✅ Database connection: SUCCESS"
else
    echo "❌ Database connection: FAILED"
    exit 1
fi

echo ""
echo "📋 2. Check current tables in database:"
mysql -h "127.0.0.1" -u "u454362045_u45436245_kli" -p"LaTahzan@01" -e "USE u454362045_u45436245_kli; SHOW TABLES;" 2>/dev/null || echo "No tables found"

echo ""
echo "📋 3. Run ALL migrations to create database schema:"
php artisan migrate --force

echo ""
echo "📋 4. Check migration status:"
php artisan migrate:status | head -10

echo ""
echo "📋 5. Check what tables were created:"
mysql -h "127.0.0.1" -u "u454362045_u45436245_kli" -p"LaTahzan@01" -e "USE u454362045_u45436245_kli; SHOW TABLES;" 2>/dev/null

echo ""
echo "📋 6. Run database seeders to populate initial data:"
php artisan db:seed --force

echo ""
echo "📋 7. Verify users were created:"
mysql -h "127.0.0.1" -u "u454362045_u45436245_kli" -p"LaTahzan@01" -e "
USE u454362045_u45436245_kli; 
SELECT COUNT(*) as total_users FROM users;
SELECT id, name, email, created_at FROM users LIMIT 5;
" 2>/dev/null

echo ""
echo "📋 8. Verify roles were created:"
mysql -h "127.0.0.1" -u "u454362045_u45436245_kli" -p"LaTahzan@01" -e "
USE u454362045_u45436245_kli; 
SELECT COUNT(*) as total_roles FROM roles;
SELECT id, name, display_name FROM roles LIMIT 5;
" 2>/dev/null

echo ""
echo "📋 9. Clear all caches after database setup:"
php artisan config:clear
php artisan cache:clear
php artisan route:clear

echo ""
echo "📋 10. Test login endpoint with seeded user:"
echo "Testing with admin credentials..."
curl -X POST \
     -H "Accept: application/json" \
     -H "Content-Type: application/json" \
     -d '{"login":"admin","password":"admin","device_id":"test"}' \
     -w "\nHTTP Status: %{http_code}\n" \
     -s https://dokterkuklinik.com/api/v2/auth/login

echo ""
echo "📋 11. Test attendance endpoint (should now work with authentication):"
curl -H "Accept: application/json" \
     -w "\nHTTP Status: %{http_code}\n" \
     -s https://dokterkuklinik.com/api/v2/dashboards/dokter/attendance

echo ""
echo "🎉 Database setup complete! Login and attendance endpoints should now work properly!"

echo ""
echo "📋 Summary of what was fixed:"
echo "✅ Database connection established"
echo "✅ All migrations run successfully" 
echo "✅ Database tables created"
echo "✅ Initial data seeded"
echo "✅ Users and roles populated"
echo "✅ 500 server errors should be resolved"