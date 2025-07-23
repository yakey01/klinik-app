#!/bin/bash

echo "🔍 Manual Database Debug on Hostinger"
echo "======================================"

# SSH to server and run debug commands
ssh -p 65002 u454362045@153.92.8.132 << 'DEBUG_SSH'

echo "📍 Current location:"
pwd
ls -la

echo -e "\n📁 Moving to project directory:"
cd domains/dokterkuklinik.com/public_html
pwd

echo -e "\n📋 Current .env database config:"
if [ -f .env ]; then
    grep "^DB_" .env || echo "No DB config found in .env"
else
    echo "❌ .env file not found!"
fi

echo -e "\n🔌 Testing MySQL service:"
mysqladmin ping 2>/dev/null && echo "✅ MySQL is running" || echo "❌ MySQL not responding"

echo -e "\n🏠 Testing possible database hosts:"
for host in localhost 127.0.0.1 mysql.dokterkuklinik.com; do
    echo "Testing: $host"
    nc -zv $host 3306 2>&1 | head -1
done

echo -e "\n🗄️ Testing database credentials:"
# Test 1: Most common Hostinger pattern
DB_NAME="u454362045_klinik"
DB_USER="u454362045_klinik" 
mysql -h localhost -u "$DB_USER" -pLaTahzan@01 -e "SELECT 'Connection OK' as status, DATABASE() as db_name;" 2>/dev/null || echo "❌ Test 1 failed: $DB_USER@$DB_NAME"

# Test 2: Your specified pattern
DB_NAME="u454362045_u45436245_kli"
DB_USER="u454362045_u45436245_kli"
mysql -h localhost -u "$DB_USER" -pLaTahzan@01 -e "SELECT 'Connection OK' as status, DATABASE() as db_name;" 2>/dev/null || echo "❌ Test 2 failed: $DB_USER@$DB_NAME"

# Test 3: Simple pattern
DB_NAME="u454362045_dokterkuklinik"
DB_USER="u454362045_dokterkuklinik"
mysql -h localhost -u "$DB_USER" -pLaTahzan@01 -e "SELECT 'Connection OK' as status, DATABASE() as db_name;" 2>/dev/null || echo "❌ Test 3 failed: $DB_USER@$DB_NAME"

echo -e "\n📊 Available databases:"
mysql -h localhost -u u454362045 -pLaTahzan@01 -e "SHOW DATABASES;" 2>/dev/null | grep u454362045 || echo "❌ Cannot list databases"

echo -e "\n📋 MySQL users:"
mysql -h localhost -u u454362045 -pLaTahzan@01 -e "SELECT User, Host FROM mysql.user WHERE User LIKE 'u454362045%';" 2>/dev/null || echo "❌ Cannot list users"

echo -e "\n🔍 Checking cPanel files:"
ls -la ~/public_html/.env* 2>/dev/null || echo "No .env files found"
ls -la ~/ | grep -E "(db|database|mysql)" || echo "No database-related files in home"

echo -e "\n📡 Network connectivity:"
ping -c 1 localhost >/dev/null 2>&1 && echo "✅ localhost reachable" || echo "❌ localhost unreachable"

echo -e "\n✅ Debug completed!"

DEBUG_SSH