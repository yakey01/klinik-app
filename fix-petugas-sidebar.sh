#!/bin/bash

# Fix Petugas Sidebar Navigation Issues
echo "🔧 Fixing Petugas Sidebar Navigation..."

# Server details
SERVER="u454362045@153.92.8.132"
PORT="65002"
PASSWORD="LaTahzan@01"
REMOTE_PATH="domains/dokterkuklinik.com/public_html"

# Create backup first
echo "📦 Creating backup..."
sshpass -p "$PASSWORD" ssh -p $PORT $SERVER "cd $REMOTE_PATH && cp resources/views/petugas/dashboard.blade.php resources/views/petugas/dashboard.blade.php.backup.$(date +%Y%m%d_%H%M%S)"

# Upload the fixed file
echo "📤 Uploading fixed dashboard file..."
sshpass -p "$PASSWORD" scp -P $PORT resources/views/petugas/dashboard.blade.php $SERVER:$REMOTE_PATH/resources/views/petugas/dashboard.blade.php

# Clear Laravel caches
echo "🧹 Clearing Laravel caches..."
sshpass -p "$PASSWORD" ssh -p $PORT $SERVER "cd $REMOTE_PATH && php artisan view:clear && php artisan cache:clear && php artisan config:clear"

# Verify the upload
echo "✅ Verifying upload..."
sshpass -p "$PASSWORD" ssh -p $PORT $SERVER "cd $REMOTE_PATH && ls -la resources/views/petugas/dashboard.blade.php*"

echo "🎉 Petugas sidebar navigation fix completed!"
echo "📋 Changes made:"
echo "   - Removed 'collapsed' class from Transaksi group"
echo "   - Enhanced toggleNavGroup() function with visual feedback"
echo "   - Added initializeSidebarGroups() for proper initialization"
echo "   - Improved CSS transitions and hover effects"
echo ""
echo "🌐 Test the sidebar at: https://dokterkuklinik.com/petugas" 