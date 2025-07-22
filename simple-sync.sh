#!/bin/bash

# SIMPLE FILE SYNC TO HOSTINGER
echo "🚀 SIMPLE FILE SYNC TO HOSTINGER"
echo "================================"

HOST="153.92.8.132"
PORT="65002"
USER="u454362045"
PASS="LaTahzan@01"

# Read the localhost files and get their checksums first
echo "📊 LOCALHOST CHECKSUMS:"
echo "======================"
echo "AttendanceRecap.php:"
md5 app/Models/AttendanceRecap.php
echo "DokterDashboardController.php:" 
md5 app/Http/Controllers/Api/V2/Dashboards/DokterDashboardController.php
echo "Dashboard.tsx:"
md5 resources/js/components/dokter/Dashboard.tsx

echo -e "\n🔄 SYNC FILE 1: AttendanceRecap.php"
echo "===================================="

# Create a temporary script with the file content
cat > /tmp/upload_recap.sh << 'RECAP_EOF'
cd domains/dokterkuklinik.com/public_html

# Backup existing file
cp app/Models/AttendanceRecap.php app/Models/AttendanceRecap.php.backup.$(date +%Y%m%d_%H%M%S)

# Create the new file
cat > app/Models/AttendanceRecap.php << 'PHP_EOF'
RECAP_EOF

# Append the actual file content
cat app/Models/AttendanceRecap.php >> /tmp/upload_recap.sh

# Finish the script
cat >> /tmp/upload_recap.sh << 'RECAP_EOF'
PHP_EOF

echo "✅ AttendanceRecap.php uploaded"
RECAP_EOF

# Execute on remote server
sshpass -p "$PASS" ssh -p $PORT -o StrictHostKeyChecking=no $USER@$HOST 'bash -s' < /tmp/upload_recap.sh

echo -e "\n🔄 SYNC FILE 2: Dashboard.tsx"
echo "============================="

# Create a temporary script for Dashboard.tsx
cat > /tmp/upload_dashboard.sh << 'DASH_EOF'
cd domains/dokterkuklinic.com/public_html

# Backup existing file
cp resources/js/components/dokter/Dashboard.tsx resources/js/components/dokter/Dashboard.tsx.backup.$(date +%Y%m%d_%H%M%S)

# Create the new file
cat > resources/js/components/dokter/Dashboard.tsx << 'TSX_EOF'
DASH_EOF

# Append the actual file content  
cat resources/js/components/dokter/Dashboard.tsx >> /tmp/upload_dashboard.sh

# Finish the script
cat >> /tmp/upload_dashboard.sh << 'DASH_EOF'
TSX_EOF

echo "✅ Dashboard.tsx uploaded"
DASH_EOF

# Execute on remote server
sshpass -p "$PASS" ssh -p $PORT -o StrictHostKeyChecking=no $USER@$HOST 'bash -s' < /tmp/upload_dashboard.sh

echo -e "\n🔄 SYNC FILE 3: DokterDashboardController.php"
echo "============================================="

# This file is larger, so we'll create it differently
# First part of the file
head -n 100 app/Http/Controllers/Api/V2/Dashboards/DokterDashboardController.php > /tmp/controller_part1.txt

cat > /tmp/upload_controller.sh << 'CTRL_EOF'
cd domains/dokterkuklinic.com/public_html

# Backup existing file
cp app/Http/Controllers/Api/V2/Dashboards/DokterDashboardController.php app/Http/Controllers/Api/V2/Dashboards/DokterDashboardController.php.backup.$(date +%Y%m%d_%H%M%S)

# Create the new file start
cat > app/Http/Controllers/Api/V2/Dashboards/DokterDashboardController.php << 'CTRL_CONTENT_EOF'
CTRL_EOF

# Append the actual file content
cat app/Http/Controllers/Api/V2/Dashboards/DokterDashboardController.php >> /tmp/upload_controller.sh

# Finish the script
cat >> /tmp/upload_controller.sh << 'CTRL_EOF'
CTRL_CONTENT_EOF

echo "✅ DokterDashboardController.php uploaded"
CTRL_EOF

# Execute on remote server
sshpass -p "$PASS" ssh -p $PORT -o StrictHostKeyChecking=no $USER@$HOST 'bash -s' < /tmp/upload_controller.sh

echo -e "\n🧹 CLEAR CACHE"
echo "=============="

sshpass -p "$PASS" ssh -p $PORT -o StrictHostKeyChecking=no $USER@$HOST << 'CACHE_EOF'
cd domains/dokterkuklinic.com/public_html

php artisan cache:clear --quiet
php artisan config:clear --quiet  
php artisan route:clear --quiet
php artisan view:clear --quiet

echo "✅ Cache cleared"
CACHE_EOF

echo -e "\n🔍 VERIFY UPLOAD"
echo "==============="

sshpass -p "$PASS" ssh -p $PORT -o StrictHostKeyChecking=no $USER@$HOST << 'VERIFY_EOF'
cd domains/dokterkuklinic.com/public_html

echo "📊 New checksums (Hostinger):"
echo "AttendanceRecap.php:"
md5sum app/Models/AttendanceRecap.php

echo "DokterDashboardController.php:"  
md5sum app/Http/Controllers/Api/V2/Dashboards/DokterDashboardController.php

echo "Dashboard.tsx:"
md5sum resources/js/components/dokter/Dashboard.tsx
VERIFY_EOF

# Clean up temp files
rm -f /tmp/upload_*.sh /tmp/controller_part1.txt

echo -e "\n✅ SYNC COMPLETED"
echo "================"