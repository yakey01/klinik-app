#!/bin/bash

# Test Login Endpoint Directly to Capture 500 Error
# This script will test the actual login POST request to see the exact error

echo "🔍 TESTING LOGIN ENDPOINT FOR 500 ERROR"
echo "========================================"

REMOTE_HOST="srv556.hstgr.io"
REMOTE_USER="u196138154"
REMOTE_PATH="/home/u196138154/domains/dokterkuklinik.com/public_html"

# Get password
echo -n "🔐 Enter SSH password for $REMOTE_USER@$REMOTE_HOST: "
read -s SSH_PASSWORD
echo

echo "📡 Testing login endpoint on production server..."

sshpass -p "$SSH_PASSWORD" ssh -o StrictHostKeyChecking=no "$REMOTE_USER@$REMOTE_HOST" << 'EOF'
cd /home/u196138154/domains/dokterkuklinik.com/public_html

echo "🧪 Testing login endpoint with curl..."

# First, get CSRF token
echo "Step 1: Getting CSRF token..."
CSRF_TOKEN=$(curl -s -c cookies.txt -b cookies.txt "https://dokterkuklinik.com/login" | grep -o 'name="_token" value="[^"]*"' | cut -d'"' -f4)

if [ -z "$CSRF_TOKEN" ]; then
    echo "❌ Failed to get CSRF token"
    # Try alternative method
    CSRF_TOKEN=$(php artisan tinker --execute="echo csrf_token();" 2>/dev/null)
    echo "Generated CSRF token: $CSRF_TOKEN"
fi

echo "CSRF Token: $CSRF_TOKEN"

echo
echo "Step 2: Testing paramedis login POST request..."

# Test with a known paramedis user
curl -v -X POST \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -H "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8" \
  -H "User-Agent: Mozilla/5.0 (compatible; LoginTest/1.0)" \
  -b cookies.txt -c cookies.txt \
  -d "_token=$CSRF_TOKEN" \
  -d "identifier=naning" \
  -d "password=naning" \
  -d "role=paramedis" \
  "https://dokterkuklinik.com/login" \
  2>&1 | head -50

echo
echo "Step 3: Testing with artisan route:list to see if login route exists..."
php artisan route:list | grep -i login

echo
echo "Step 4: Testing login route directly through artisan..."
php artisan tinker --execute="
try {
    // Test the login route
    \$request = new \Illuminate\Http\Request();
    \$request->merge([
        'identifier' => 'naning',
        'password' => 'naning', 
        'role' => 'paramedis'
    ]);
    
    echo 'Testing UnifiedAuthController login method...\n';
    
    // Get the controller
    \$controller = new \App\Http\Controllers\Auth\UnifiedAuthController();
    
    // This will help us see the exact error
    echo 'Controller instantiated successfully\n';
    
} catch (Exception \$e) {
    echo 'Controller test failed: ' . \$e->getMessage() . '\n';
    echo 'File: ' . \$e->getFile() . ':' . \$e->getLine() . '\n';
}"

echo
echo "Step 5: Checking web.php routes..."
grep -n -A 5 -B 5 "login" routes/web.php || echo "No login routes found in web.php"

# Clean up
rm -f cookies.txt
EOF

echo
echo "🔍 ENDPOINT TEST COMPLETE"
echo "========================="

# Clean up password
unset SSH_PASSWORD