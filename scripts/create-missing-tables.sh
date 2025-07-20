#!/bin/bash

# Create missing tables and run basic seeders

cd /home/u454362045/domains/dokterkuklinik.com/public_html

echo "🔧 Creating missing database tables and basic data..."

echo "📋 1. Check what tables currently exist:"
mysql -h "127.0.0.1" -u "u454362045_u45436245_kli" -p"LaTahzan@01" -e "USE u454362045_u45436245_kli; SHOW TABLES;" 2>/dev/null

echo ""
echo "📋 2. Check if roles table exists:"
if mysql -h "127.0.0.1" -u "u454362045_u45436245_kli" -p"LaTahzan@01" -e "USE u454362045_u45436245_kli; DESC roles;" 2>/dev/null; then
    echo "✅ Roles table exists"
else
    echo "❌ Roles table missing - creating it manually"
    
    echo "📋 3. Create roles table manually:"
    mysql -h "127.0.0.1" -u "u454362045_u45436245_kli" -p"LaTahzan@01" -e "
    USE u454362045_u45436245_kli;
    CREATE TABLE IF NOT EXISTS roles (
        id bigint unsigned NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        display_name varchar(255) DEFAULT NULL,
        description text,
        permissions json DEFAULT NULL,
        created_at timestamp NULL DEFAULT NULL,
        updated_at timestamp NULL DEFAULT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY roles_name_unique (name)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    " 2>&1
fi

echo ""
echo "📋 4. Create admin user manually since seeders are failing:"
mysql -h "127.0.0.1" -u "u454362045_u45436245_kli" -p"LaTahzan@01" -e "
USE u454362045_u45436245_kli;

-- Insert admin role
INSERT IGNORE INTO roles (name, display_name, description, permissions, created_at, updated_at) 
VALUES ('admin', 'Administrator', 'Super admin dengan akses penuh ke semua fitur sistem', 
'[\"manage_users\",\"manage_roles\",\"manage_clinic\",\"view_reports\",\"manage_finance\",\"validate_transactions\",\"export_data\"]', 
NOW(), NOW());

-- Insert admin user
INSERT IGNORE INTO users (name, email, password, role_id, created_at, updated_at) 
VALUES ('Administrator', 'admin@admin.com', '\$2y\$12\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW());

-- Insert test user for login testing
INSERT IGNORE INTO users (name, email, password, created_at, updated_at) 
VALUES ('Test User', 'test@test.com', '\$2y\$12\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NOW(), NOW());
" 2>&1

echo ""
echo "📋 5. Verify users were created:"
mysql -h "127.0.0.1" -u "u454362045_u45436245_kli" -p"LaTahzan@01" -e "
USE u454362045_u45436245_kli; 
SELECT COUNT(*) as total_users FROM users;
SELECT id, name, email FROM users;
" 2>/dev/null

echo ""
echo "📋 6. Verify roles were created:"
mysql -h "127.0.0.1" -u "u454362045_u45436245_kli" -p"LaTahzan@01" -e "
USE u454362045_u45436245_kli; 
SELECT COUNT(*) as total_roles FROM roles;
SELECT id, name, display_name FROM roles;
" 2>/dev/null

echo ""
echo "📋 7. Clear caches:"
php artisan config:clear
php artisan cache:clear

echo ""
echo "📋 8. Test login with created admin user:"
echo "Testing admin@admin.com with password 'secret'..."
curl -X POST \
     -H "Accept: application/json" \
     -H "Content-Type: application/json" \
     -d '{"login":"admin@admin.com","password":"secret","device_id":"test"}' \
     -w "\nHTTP Status: %{http_code}\n" \
     -s https://dokterkuklinik.com/api/v2/auth/login

echo ""
echo "📋 9. Test login with test user:"
echo "Testing test@test.com with password 'secret'..."
curl -X POST \
     -H "Accept: application/json" \
     -H "Content-Type: application/json" \
     -d '{"login":"test@test.com","password":"secret","device_id":"test"}' \
     -w "\nHTTP Status: %{http_code}\n" \
     -s https://dokterkuklinik.com/api/v2/auth/login

echo ""
echo "🎉 Manual database setup complete!"
echo ""
echo "📋 Login credentials:"
echo "Email: admin@admin.com or test@test.com"
echo "Password: secret"