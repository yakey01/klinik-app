#!/bin/bash

# MANUAL SYNC FILES USING SSH
# Alternative method when SCP fails

echo "🚀 MANUAL SYNC FILES KE HOSTINGER"
echo "================================"
echo "Menggunakan method upload alternatif"
echo ""

HOST="153.92.8.132"
PORT="65002"
USER="u454362045"
PASS="LaTahzan@01"

echo "🔄 1. UPLOAD AttendanceRecap.php"
echo "=============================="

# Read and encode the file
ATTENDANCE_RECAP_CONTENT=$(base64 app/Models/AttendanceRecap.php)

sshpass -p "$PASS" ssh -p $PORT -o StrictHostKeyChecking=no $USER@$HOST << EOF
cd domains/dokterkuklinik.com/public_html

echo "Uploading AttendanceRecap.php..."
echo "$ATTENDANCE_RECAP_CONTENT" | base64 -d > app/Models/AttendanceRecap.php
echo "✅ AttendanceRecap.php uploaded"
EOF

echo "🔄 2. UPLOAD DokterDashboardController.php"
echo "======================================"

# Split file into chunks due to size limit
split -b 50000 app/Http/Controllers/Api/V2/Dashboards/DokterDashboardController.php /tmp/controller_chunk_

sshpass -p "$PASS" ssh -p $PORT -o StrictHostKeyChecking=no $USER@$HOST << 'CONTROLLER_EOF'
cd domains/dokterkuklinik.com/public_html

# Create backup
cp app/Http/Controllers/Api/V2/Dashboards/DokterDashboardController.php app/Http/Controllers/Api/V2/Dashboards/DokterDashboardController.php.backup.$(date +%Y%m%d_%H%M%S)

echo "Creating new DokterDashboardController.php..."
CONTROLLER_EOF

# Upload each chunk
for chunk in /tmp/controller_chunk_*; do
    if [ -f "$chunk" ]; then
        CHUNK_CONTENT=$(base64 "$chunk")
        CHUNK_NAME=$(basename "$chunk")
        
        sshpass -p "$PASS" ssh -p $PORT -o StrictHostKeyChecking=no $USER@$HOST << EOF
echo "$CHUNK_CONTENT" | base64 -d >> app/Http/Controllers/Api/V2/Dashboards/DokterDashboardController.php.new
EOF
    fi
done

sshpass -p "$PASS" ssh -p $PORT -o StrictHostKeyChecking=no $USER@$HOST << 'MOVE_EOF'
cd domains/dokterkuklinik.com/public_html

# Replace the file
if [ -f "app/Http/Controllers/Api/V2/Dashboards/DokterDashboardController.php.new" ]; then
    mv app/Http/Controllers/Api/V2/Dashboards/DokterDashboardController.php.new app/Http/Controllers/Api/V2/Dashboards/DokterDashboardController.php
    echo "✅ DokterDashboardController.php uploaded"
else
    echo "❌ Failed to upload DokterDashboardController.php"
fi
MOVE_EOF

# Clean up chunks
rm -f /tmp/controller_chunk_*

echo "🔄 3. UPLOAD Dashboard.tsx"
echo "========================"

DASHBOARD_CONTENT=$(base64 resources/js/components/dokter/Dashboard.tsx)

sshpass -p "$PASS" ssh -p $PORT -o StrictHostKeyChecking=no $USER@$HOST << EOF
cd domains/dokterkuklinik.com/public_html

echo "Uploading Dashboard.tsx..."
echo "$DASHBOARD_CONTENT" | base64 -d > resources/js/components/dokter/Dashboard.tsx
echo "✅ Dashboard.tsx uploaded"
EOF

echo "🔍 4. VERIFIKASI UPLOAD"
echo "====================="

sshpass -p "$PASS" ssh -p $PORT -o StrictHostKeyChecking=no $USER@$HOST << 'VERIFY_EOF'
cd domains/dokterkuklinik.com/public_html

echo "🔍 Verifying uploaded files:"

echo "AttendanceRecap.php checksum:"
md5sum app/Models/AttendanceRecap.php

echo "DokterDashboardController.php checksum:"
md5sum app/Http/Controllers/Api/V2/Dashboards/DokterDashboardController.php

echo "Dashboard.tsx checksum:"
md5sum resources/js/components/dokter/Dashboard.tsx

echo -e "\n🧹 Clearing caches after upload..."
php artisan cache:clear --quiet
php artisan config:clear --quiet
php artisan route:clear --quiet
php artisan view:clear --quiet

echo "✅ Cache cleared"
VERIFY_EOF

echo -e "\n📊 EXPECTED CHECKSUMS (from localhost):"
echo "======================================="
echo "AttendanceRecap.php:"
md5sum app/Models/AttendanceRecap.php 2>/dev/null || md5 app/Models/AttendanceRecap.php

echo "DokterDashboardController.php:"
md5sum app/Http/Controllers/Api/V2/Dashboards/DokterDashboardController.php 2>/dev/null || md5 app/Http/Controllers/Api/V2/Dashboards/DokterDashboardController.php

echo "Dashboard.tsx:"
md5sum resources/js/components/dokter/Dashboard.tsx 2>/dev/null || md5 resources/js/components/dokter/Dashboard.tsx

echo -e "\n✅ MANUAL SYNC SELESAI"
echo "===================="
echo "File-file sudah di-upload ke Hostinger"
echo "Perlu hard refresh browser untuk melihat perubahan"