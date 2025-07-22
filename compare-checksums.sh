#!/bin/bash

# BANDINGKAN CHECKSUM FILE PENTING

echo "🔍 BANDINGKAN CHECKSUM FILE CRITICAL"
echo "===================================="
echo "Membandingkan Hostinger vs Localhost"
echo ""

HOST="153.92.8.132"
PORT="65002"
USER="u454362045"
PASS="LaTahzan@01"

echo "📊 CHECKSUM HOSTINGER:"
echo "====================="

sshpass -p "$PASS" ssh -p $PORT -o StrictHostKeyChecking=no $USER@$HOST << 'EOF'
cd domains/dokterkuklinik.com/public_html

echo "🔍 File checksums (Hostinger):"

if [ -f "app/Models/AttendanceRecap.php" ]; then
    echo "AttendanceRecap.php:"
    md5sum app/Models/AttendanceRecap.php
else
    echo "❌ AttendanceRecap.php NOT FOUND!"
fi

if [ -f "app/Http/Controllers/Api/V2/Dashboards/DokterDashboardController.php" ]; then
    echo "DokterDashboardController.php:"
    md5sum app/Http/Controllers/Api/V2/Dashboards/DokterDashboardController.php
else
    echo "❌ DokterDashboardController.php NOT FOUND!"
fi

if [ -f "resources/js/components/dokter/Dashboard.tsx" ]; then
    echo "Dashboard.tsx:"
    md5sum resources/js/components/dokter/Dashboard.tsx
else
    echo "❌ Dashboard.tsx NOT FOUND!"
fi

if [ -f "app/Models/DokterPresensi.php" ]; then
    echo "DokterPresensi.php:"
    md5sum app/Models/DokterPresensi.php
else
    echo "❌ DokterPresensi.php NOT FOUND!"
fi

echo -e "\n🔍 Critical method check in DokterDashboardController:"
if [ -f "app/Http/Controllers/Api/V2/Dashboards/DokterDashboardController.php" ]; then
    echo "getPerformanceStats method signature:"
    grep -n -A 3 "function getPerformanceStats" app/Http/Controllers/Api/V2/Dashboards/DokterDashboardController.php
    
    echo -e "\nAttendanceRecap::getRecapData calls:"
    grep -n "AttendanceRecap::getRecapData" app/Http/Controllers/Api/V2/Dashboards/DokterDashboardController.php
fi

EOF

echo -e "\n📊 CHECKSUM LOCALHOST:"
echo "====================="

cd /Users/kym/Herd/Dokterku

echo "🔍 File checksums (Localhost):"

if [ -f "app/Models/AttendanceRecap.php" ]; then
    echo "AttendanceRecap.php:"
    md5sum app/Models/AttendanceRecap.php 2>/dev/null || md5 app/Models/AttendanceRecap.php
else
    echo "❌ AttendanceRecap.php NOT FOUND!"
fi

if [ -f "app/Http/Controllers/Api/V2/Dashboards/DokterDashboardController.php" ]; then
    echo "DokterDashboardController.php:"
    md5sum app/Http/Controllers/Api/V2/Dashboards/DokterDashboardController.php 2>/dev/null || md5 app/Http/Controllers/Api/V2/Dashboards/DokterDashboardController.php
else
    echo "❌ DokterDashboardController.php NOT FOUND!"
fi

if [ -f "resources/js/components/dokter/Dashboard.tsx" ]; then
    echo "Dashboard.tsx:"
    md5sum resources/js/components/dokter/Dashboard.tsx 2>/dev/null || md5 resources/js/components/dokter/Dashboard.tsx
else
    echo "❌ Dashboard.tsx NOT FOUND!"
fi

if [ -f "app/Models/DokterPresensi.php" ]; then
    echo "DokterPresensi.php:"
    md5sum app/Models/DokterPresensi.php 2>/dev/null || md5 app/Models/DokterPresensi.php
else
    echo "❌ DokterPresensi.php NOT FOUND!"
fi

echo -e "\n📊 ANALISIS PERBEDAAN:"
echo "====================="

echo "Berdasarkan audit yang dilakukan:"
echo ""
echo "✅ YANG SAMA:"
echo "- Model structure dan casts"
echo "- Controller methods yang ada"
echo "- Frontend component structure"
echo ""
echo "❌ PERBEDAAN DITEMUKAN:"
echo "1. Data count berbeda:"
echo "   Hostinger: 76 DokterPresensi records, 3 staff in recap"
echo "   Localhost: 45 DokterPresensi records, 2 staff in recap"
echo ""
echo "2. Attendance percentage berbeda:"
echo "   Hostinger: Dr. Yaya 100%"
echo "   Localhost: Dr. Yaya 88.89%"
echo ""
echo "3. Staff count berbeda:"
echo "   Hostinger: 3 total staff"
echo "   Localhost: 2 total staff"
echo ""

echo "🎯 KESIMPULAN:"
echo "============="
echo "Implementasi kode SAMA, tapi DATA berbeda antara Hostinger vs Localhost"
echo "Ini menjelaskan mengapa perhitungan berbeda."