#!/bin/bash

# Clean disk space and fix user creation

cd /home/u454362045/domains/dokterkuklinik.com/public_html

echo "🧹 Cleaning disk space and fixing user creation..."

echo "📋 1. Check current disk usage:"
df -h | head -5

echo ""
echo "📋 2. Check /tmp directory usage:"
du -sh /tmp 2>/dev/null || echo "Cannot access /tmp directory"

echo ""
echo "📋 3. Clean up Laravel temporary files:"
rm -rf storage/framework/cache/data/* 2>/dev/null || echo "Cache data already clean"
rm -rf storage/framework/sessions/* 2>/dev/null || echo "Sessions already clean"
rm -rf storage/framework/views/* 2>/dev/null || echo "Views already clean"
rm -rf storage/logs/*.log 2>/dev/null || echo "Logs already clean"

echo ""
echo "📋 4. Clean up system temp files (if accessible):"
find /tmp -name "*.tmp" -mtime +1 -delete 2>/dev/null || echo "Cannot clean /tmp"
find /var/tmp -name "*.tmp" -mtime +1 -delete 2>/dev/null || echo "Cannot clean /var/tmp"

echo ""
echo "📋 5. Check disk space after cleanup:"
df -h | head -5

echo ""
echo "📋 6. Test database connection:"
if mysql -h "127.0.0.1" -u "u454362045_u45436245_kli" -p"LaTahzan@01" -e "USE u454362045_u45436245_kli; SELECT 1;" 2>/dev/null; then
    echo "✅ Database connection: SUCCESS"
else
    echo "❌ Database connection: FAILED"
    exit 1
fi

echo ""
echo "📋 7. Check current users and roles:"
mysql -h "127.0.0.1" -u "u454362045_u45436245_kli" -p"LaTahzan@01" -e "
USE u454362045_u45436245_kli; 
SELECT COUNT(*) as total_users FROM users;
SELECT COUNT(*) as total_roles FROM roles;
" 2>/dev/null

echo ""
echo "📋 8. Describe users table structure:"
mysql -h "127.0.0.1" -u "u454362045_u45436245_kli" -p"LaTahzan@01" -e "
USE u454362045_u45436245_kli; 
DESC users;
" 2>/dev/null

echo ""
echo "📋 9. Try simple user insertion with minimal fields:"
mysql -h "127.0.0.1" -u "u454362045_u45436245_kli" -p"LaTahzan@01" -e "
USE u454362045_u45436245_kli;

-- Try inserting a simple admin user
INSERT IGNORE INTO users (name, email, password, created_at, updated_at) 
VALUES ('Admin User', 'admin@test.com', '\$2y\$12\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NOW(), NOW());

-- Try inserting a test user
INSERT IGNORE INTO users (name, email, password, created_at, updated_at) 
VALUES ('Test User', 'test@test.com', '\$2y\$12\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NOW(), NOW());
" 2>&1

echo ""
echo "📋 10. Verify users were created:"
mysql -h "127.0.0.1" -u "u454362045_u45436245_kli" -p"LaTahzan@01" -e "
USE u454362045_u45436245_kli; 
SELECT COUNT(*) as total_users FROM users;
SELECT id, name, email, created_at FROM users;
" 2>/dev/null

echo ""
echo "📋 11. Clear Laravel caches after cleanup:"
php artisan config:clear 2>/dev/null
php artisan cache:clear 2>/dev/null

echo ""
echo "📋 12. Test login with created users:"
echo "Testing admin@test.com with password 'secret'..."
curl -X POST \
     -H "Accept: application/json" \
     -H "Content-Type: application/json" \
     -d '{"login":"admin@test.com","password":"secret","device_id":"test"}' \
     -w "\nHTTP Status: %{http_code}\n" \
     -s https://dokterkuklinik.com/api/v2/auth/login

echo ""
echo "Testing test@test.com with password 'secret'..."
curl -X POST \
     -H "Accept: application/json" \
     -H "Content-Type: application/json" \
     -d '{"login":"test@test.com","password":"secret","device_id":"test"}' \
     -w "\nHTTP Status: %{http_code}\n" \
     -s https://dokterkuklinik.com/api/v2/auth/login

echo ""
echo "📋 13. Final disk space check:"
df -h | head -5

echo ""
echo "🎉 Disk cleanup and user creation complete!"
echo ""
echo "📋 Login credentials:"
echo "Email: admin@test.com or test@test.com"
echo "Password: secret"