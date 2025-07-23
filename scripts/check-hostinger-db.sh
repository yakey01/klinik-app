#!/bin/bash

echo "🔍 Hostinger Database Configuration Checker"
echo "==========================================="

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}This script helps you gather the correct database information from Hostinger.${NC}"
echo ""

echo -e "${YELLOW}📋 Steps to get your Hostinger database information:${NC}"
echo ""
echo "1. 🌐 Login to your Hostinger control panel (hPanel)"
echo "2. 🗄️ Go to 'Databases' > 'MySQL Databases'"  
echo "3. 📝 Find your database and note down:"
echo "   - Database name"
echo "   - Database username" 
echo "   - Database host/server"
echo "4. 🔑 If you forgot the password, you can reset it there"
echo ""

echo -e "${YELLOW}❓ Common Hostinger database configurations:${NC}"
echo ""
echo "📊 Typical patterns:"
echo "   Database name: u[user_id]_[db_name]"
echo "   Username: u[user_id]_[user_name]" 
echo "   Host: localhost OR mysql.hostinger.com"
echo "   Port: 3306"
echo ""

echo -e "${YELLOW}🔧 Current configuration check:${NC}"

# Check if we're in the right directory
if [ ! -f ".env" ]; then
    echo -e "${RED}❌ .env file not found in current directory${NC}"
    echo "Please run this script from your Laravel project root"
    exit 1
fi

echo ""
echo -e "${GREEN}📋 Current .env database settings:${NC}"
if grep -q "^DB_" .env; then
    grep "^DB_" .env | while read line; do
        key=$(echo $line | cut -d'=' -f1)
        value=$(echo $line | cut -d'=' -f2)
        
        if [ "$key" = "DB_PASSWORD" ]; then
            echo "  $key=${value:0:2}$(echo $value | sed 's/./*/g' | cut -c3-)"
        else
            echo "  $key=$value"
        fi
    done
else
    echo -e "${RED}❌ No database configuration found in .env${NC}"
fi

echo ""
echo -e "${YELLOW}🧪 Interactive configuration helper:${NC}"
echo ""

read -p "Do you want to update your database configuration? (y/n): " -n 1 -r
echo ""

if [[ $REPLY =~ ^[Yy]$ ]]; then
    echo ""
    echo -e "${GREEN}Please enter your Hostinger database information:${NC}"
    
    read -p "Database Host (default: localhost): " db_host
    db_host=${db_host:-localhost}
    
    read -p "Database Name: " db_database
    
    read -p "Database Username: " db_username
    
    read -s -p "Database Password: " db_password
    echo ""
    
    read -p "Database Port (default: 3306): " db_port
    db_port=${db_port:-3306}
    
    echo ""
    echo -e "${YELLOW}🔄 Updating .env file...${NC}"
    
    # Backup current .env
    cp .env .env.backup.$(date +%Y%m%d_%H%M%S)
    echo "💾 Backed up current .env"
    
    # Update .env
    sed -i.tmp "s/^DB_HOST=.*/DB_HOST=$db_host/" .env
    sed -i.tmp "s/^DB_PORT=.*/DB_PORT=$db_port/" .env
    sed -i.tmp "s/^DB_DATABASE=.*/DB_DATABASE=$db_database/" .env
    sed -i.tmp "s/^DB_USERNAME=.*/DB_USERNAME=$db_username/" .env
    sed -i.tmp "s/^DB_PASSWORD=.*/DB_PASSWORD=$db_password/" .env
    
    # Clean up temp file
    rm -f .env.tmp
    
    echo -e "${GREEN}✅ .env file updated${NC}"
    
    echo ""
    echo -e "${YELLOW}🧪 Testing connection...${NC}"
    
    # Test connection with PHP
    php -r "
    try {
        \$pdo = new PDO('mysql:host=$db_host;port=$db_port;dbname=$db_database', '$db_username', '$db_password');
        echo '✅ Connection successful!' . PHP_EOL;
        \$stmt = \$pdo->query('SELECT VERSION() as version');
        \$result = \$stmt->fetch(PDO::FETCH_ASSOC);
        echo '📊 MySQL Version: ' . \$result['version'] . PHP_EOL;
    } catch (PDOException \$e) {
        echo '❌ Connection failed: ' . \$e->getMessage() . PHP_EOL;
        echo '💡 Double-check your credentials in Hostinger control panel' . PHP_EOL;
    }
    "
    
    echo ""
    read -p "Do you want to test with Laravel? (y/n): " -n 1 -r
    echo ""
    
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        echo -e "${YELLOW}🔄 Testing Laravel connection...${NC}"
        
        php artisan config:clear
        
        if php artisan tinker --execute="
            try {
                \$pdo = DB::connection()->getPdo();
                echo 'Laravel connection: SUCCESS' . PHP_EOL;
                echo 'Database: ' . DB::connection()->getDatabaseName() . PHP_EOL;
            } catch (Exception \$e) {
                echo 'Laravel connection failed: ' . \$e->getMessage() . PHP_EOL;
            }
        "; then
            echo -e "${GREEN}🎉 Laravel can connect to your database!${NC}"
            
            echo ""
            read -p "Do you want to run migrations? (y/n): " -n 1 -r
            echo ""
            
            if [[ $REPLY =~ ^[Yy]$ ]]; then
                echo -e "${YELLOW}🗄️ Running migrations...${NC}"
                php artisan migrate
            fi
            
        else
            echo -e "${RED}❌ Laravel still cannot connect${NC}"
        fi
    fi
fi

echo ""
echo -e "${GREEN}🚀 Next steps:${NC}"
echo ""
echo "1. If connection works locally, commit and push your changes:"
echo "   git add .env"
echo "   git commit -m 'Fix database configuration'"
echo "   git push origin main"
echo ""
echo "2. Run the database fix workflow:"
echo "   gh workflow run fix-database-connection.yml"
echo ""
echo "3. Or trigger it via GitHub Actions UI:"
echo "   https://github.com/$(git remote get-url origin | sed 's/.*github.com[:/]//' | sed 's/.git$//')/actions"
echo ""

echo -e "${BLUE}📞 If you still have issues:${NC}"
echo "1. Contact Hostinger support for correct database connection details"
echo "2. Verify database user permissions in phpMyAdmin"
echo "3. Check if your hosting plan supports remote database connections"
echo ""

echo -e "${GREEN}✅ Database configuration check completed!${NC}"