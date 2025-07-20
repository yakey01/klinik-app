#!/bin/bash

# Fix placeholder database credentials in .env file

cd /home/u454362045/domains/dokterkuklinik.com/public_html

echo "🚨 CRITICAL: Found placeholder credentials in .env file!"

echo "📋 1. Current INCORRECT .env configuration:"
grep "DB_" .env | head -6

echo ""
echo "🔧 2. You need to replace these placeholder values:"
echo "   DB_DATABASE=YourExactDatabaseName  ← REPLACE with actual database name"
echo "   DB_USERNAME=YourExactUsername      ← REPLACE with actual username"

echo ""
echo "📋 3. Check what databases exist in cPanel and update accordingly."
echo "   Look for databases starting with 'u454362045_' in your Hostinger cPanel"

echo ""
echo "🎯 4. Example of what the correct .env should look like:"
echo "   DB_DATABASE=u454362045_clinic_db"
echo "   DB_USERNAME=u454362045_clinic_usr"
echo "   DB_PASSWORD=KlinikApp2025!"

echo ""
echo "📋 5. Quick command template (fill in the ACTUAL names):"
echo ""
echo "# Replace ACTUAL_DB_NAME and ACTUAL_USERNAME with real values from cPanel:"
echo "sed -i 's/DB_DATABASE=.*/DB_DATABASE=ACTUAL_DB_NAME/' .env"
echo "sed -i 's/DB_USERNAME=.*/DB_USERNAME=ACTUAL_USERNAME/' .env"

echo ""
echo "📋 6. After updating, clear caches:"
echo "php artisan config:clear"
echo "php artisan cache:clear"

echo ""
echo "🎉 Once you replace the placeholders with REAL database names, the 500 error will be fixed!"