#!/bin/bash

# Check if routes file has the updated code

echo "🔍 Checking routes/web.php on Hostinger..."
echo "=========================================="

HOST="153.92.8.132"
PORT="65002"
USER="u454362045"
PASS="LaTahzan@01"

# Check routes file
sshpass -p "$PASS" ssh -p $PORT -o StrictHostKeyChecking=no $USER@$HOST << 'EOF'
cd domains/dokterkuklinik.com/public_html
echo "Checking mobile-app route around line 177..."
grep -A 15 -B 5 "Route::get('/mobile-app'" routes/web.php | grep -E "(dokter|displayName|userData\['name'\])"
EOF