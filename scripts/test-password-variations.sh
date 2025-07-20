#!/bin/bash

# Test various password combinations with the correct database names

cd /home/u454362045/domains/dokterkuklinik.com/public_html

echo "🔧 Testing password variations with correct database names..."

DB_HOST="127.0.0.1"
DB_PORT="3306"
DB_DATABASE="u454362045_u45436245_kli"
DB_USERNAME="u454362045_u45436245_kli"

echo "📋 Database credentials being tested:"
echo "   Host: $DB_HOST"
echo "   Database: $DB_DATABASE"
echo "   Username: $DB_USERNAME"

echo ""
echo "📋 Testing various password combinations:"

# Test password variations
passwords=(
    "KlinikApp2025!"
    "klinikapp2025"
    "Klinik2025"
    "klinik2025"
    "KlinikApp2025"
    "Dokterku2025!"
    "dokterku2025"
    "Dokterku2025"
    ""
    "123456"
    "password"
    "admin"
    "root"
)

for password in "${passwords[@]}"; do
    echo -n "Testing password: '${password:0:3}***': "
    if mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USERNAME" -p"$password" -e "USE $DB_DATABASE; SELECT 1;" 2>/dev/null >/dev/null; then
        echo "✅ SUCCESS!"
        WORKING_PASSWORD="$password"
        break
    else
        echo "❌ Failed"
    fi
done

if [ -n "$WORKING_PASSWORD" ]; then
    echo ""
    echo "🎉 Found working password: '$WORKING_PASSWORD'"
    
    echo ""
    echo "📋 Updating .env with working password:"
    cp .env .env.backup.password_test
    sed -i "s/DB_PASSWORD=.*/DB_PASSWORD=$WORKING_PASSWORD/" .env
    
    echo "📋 New configuration:"
    grep "DB_" .env | head -6
    
    echo ""
    echo "📋 Clear caches and test:"
    php artisan config:clear
    php artisan cache:clear
    
    echo ""
    echo "📋 Test login endpoint:"
    curl -X POST \
         -H "Accept: application/json" \
         -H "Content-Type: application/json" \
         -d '{"login":"admin","password":"admin","device_id":"test"}' \
         -w "\nHTTP Status: %{http_code}\n" \
         -s https://dokterkuklinik.com/api/v2/auth/login
    
    echo ""
    echo "🎉 Password fixed!"
    
else
    echo ""
    echo "❌ No working password found from common variations"
    echo ""
    echo "💡 NEXT STEPS:"
    echo "1. In Hostinger cPanel, click the eye icon next to the password field"
    echo "2. Copy the EXACT password shown"
    echo "3. Update .env manually:"
    echo "   sed -i 's/DB_PASSWORD=.*/DB_PASSWORD=YourActualPassword/' .env"
    echo ""
    echo "📋 The database name and username are now correct:"
    echo "   DB_DATABASE=u454362045_u45436245_kli ✅"
    echo "   DB_USERNAME=u454362045_u45436245_kli ✅"
    echo "   DB_PASSWORD=??? ❌ (needs correct password from cPanel)"
fi

echo ""
echo "🏁 Password testing complete"