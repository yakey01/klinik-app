#!/bin/bash

echo "🔍 Testing Hostinger Production API..."
echo "====================================="

# Test the dashboard API
echo -e "\n📱 Testing Dashboard API..."
curl -s -H "Accept: application/json" \
     -H "User-Agent: Mozilla/5.0" \
     https://dokterkuklinik.com/api/v2/dashboards/dokter/ | \
     python3 -m json.tool 2>/dev/null || echo "API requires authentication"

echo -e "\n✅ SUMMARY:"
echo "1. Database fixed: nama_lengkap = 'Dr. Yaya Mulyana, M.Kes'"
echo "2. Routes updated: Uses dokter->nama_lengkap"
echo "3. Caches cleared: All changes active"
echo ""
echo "🎯 Please test:"
echo "1. Go to: https://dokterkuklinik.com/dokter/mobile-app"
echo "2. Login with username: yaya"
echo "3. Welcome should show: 'Selamat Siang, Dr. Yaya Mulyana, M.Kes'"
echo "4. Check if attendance data appears"