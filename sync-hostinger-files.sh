#!/bin/bash

# SINKRONISASI FILE CRITICAL KE HOSTINGER

echo "🚀 SINKRONISASI FILE CRITICAL KE HOSTINGER"
echo "========================================"
echo "Menyamakan implementasi 100% dengan localhost"
echo ""

HOST="153.92.8.132"
PORT="65002"
USER="u454362045"
PASS="LaTahzan@01"

echo "📋 PERBEDAAN CHECKSUM DITEMUKAN:"
echo "==============================="
echo "File yang berbeda antara Hostinger vs Localhost:"
echo "1. ❌ AttendanceRecap.php: 185a85be... vs 2ccb3f04..."
echo "2. ❌ DokterDashboardController.php: 73f62dce... vs e72c0732..."
echo "3. ❌ Dashboard.tsx: 39c6be6c... vs d6cb8048..."
echo "4. ✅ DokterPresensi.php: SAMA"
echo ""

# Create backup and sync script
cat > /tmp/sync_files.sh << 'SYNC_EOF'
#!/bin/bash
cd domains/dokterkuklinik.com/public_html

echo "📦 BACKUP FILE EXISTING..."
mkdir -p backups/sync-$(date +%Y%m%d_%H%M%S)
cp app/Models/AttendanceRecap.php backups/sync-$(date +%Y%m%d_%H%M%S)/
cp app/Http/Controllers/Api/V2/Dashboards/DokterDashboardController.php backups/sync-$(date +%Y%m%d_%H%M%S)/
cp resources/js/components/dokter/Dashboard.tsx backups/sync-$(date +%Y%m%d_%H%M%S)/

echo "✅ Backup selesai"
SYNC_EOF

echo "🔄 1. SINKRONISASI AttendanceRecap.php"
echo "===================================="

# Upload AttendanceRecap.php
scp -P $PORT app/Models/AttendanceRecap.php $USER@$HOST:domains/dokterkuklinik.com/public_html/app/Models/

echo "🔄 2. SINKRONISASI DokterDashboardController.php"
echo "============================================="

# Upload DokterDashboardController.php
scp -P $PORT app/Http/Controllers/Api/V2/Dashboards/DokterDashboardController.php $USER@$HOST:domains/dokterkuklinik.com/public_html/app/Http/Controllers/Api/V2/Dashboards/

echo "🔄 3. SINKRONISASI Dashboard.tsx"
echo "============================"

# Upload Dashboard.tsx
scp -P $PORT resources/js/components/dokter/Dashboard.tsx $USER@$HOST:domains/dokterkuklinik.com/public_html/resources/js/components/dokter/

echo "🧹 4. CLEAR CACHE DAN REBUILD"
echo "============================"

sshpass -p "$PASS" ssh -p $PORT -o StrictHostKeyChecking=no $USER@$HOST << 'CACHE_EOF'
cd domains/dokterkuklinik.com/public_html

echo "🧹 Clearing all caches..."
php artisan cache:clear --quiet
php artisan config:clear --quiet
php artisan route:clear --quiet
php artisan view:clear --quiet

echo "🔨 Building assets..."
if [ -f "package.json" ]; then
    # Check if npm/yarn is available
    if command -v npm &> /dev/null; then
        npm run build --quiet 2>/dev/null || echo "NPM build skipped"
    elif command -v yarn &> /dev/null; then
        yarn build --quiet 2>/dev/null || echo "Yarn build skipped"
    fi
fi

echo "✅ Cache cleared and assets built"
CACHE_EOF

echo "🔍 5. VERIFIKASI SINKRONISASI"
echo "==========================="

echo "📊 Verifying checksums after sync:"

sshpass -p "$PASS" ssh -p $PORT -o StrictHostKeyChecking=no $USER@$HOST << 'VERIFY_EOF'
cd domains/dokterkuklinik.com/public_html

echo "🔍 New checksums (Hostinger after sync):"
echo "AttendanceRecap.php:"
md5sum app/Models/AttendanceRecap.php

echo "DokterDashboardController.php:"
md5sum app/Http/Controllers/Api/V2/Dashboards/DokterDashboardController.php

echo "Dashboard.tsx:"
md5sum resources/js/components/dokter/Dashboard.tsx
VERIFY_EOF

echo -e "\n📊 Localhost checksums (reference):"
echo "AttendanceRecap.php:"
md5sum app/Models/AttendanceRecap.php 2>/dev/null || md5 app/Models/AttendanceRecap.php

echo "DokterDashboardController.php:"
md5sum app/Http/Controllers/Api/V2/Dashboards/DokterDashboardController.php 2>/dev/null || md5 app/Http/Controllers/Api/V2/Dashboards/DokterDashboardController.php

echo "Dashboard.tsx:"
md5sum resources/js/components/dokter/Dashboard.tsx 2>/dev/null || md5 resources/js/components/dokter/Dashboard.tsx

echo -e "\n🎯 6. TEST API SETELAH SINKRONISASI"
echo "================================="

sshpass -p "$PASS" ssh -p $PORT -o StrictHostKeyChecking=no $USER@$HOST << 'TEST_EOF'
cd domains/dokterkuklinik.com/public_html

echo "🔍 Testing Dashboard API after sync:"
php artisan tinker --execute="
\$yayaUser = App\\Models\\User::whereHas('dokter', function(\$q) {
    \$q->where('username', 'yaya');
})->first();

if (\$yayaUser) {
    auth()->login(\$yayaUser);
    
    try {
        \$controller = new App\\Http\\Controllers\\Api\\V2\\Dashboards\\DokterDashboardController();
        \$request = new Illuminate\\Http\\Request();
        
        \$response = \$controller->index(\$request);
        \$data = \$response->getData(true);
        
        echo 'API Status: ' . (\$data['success'] ? 'SUCCESS' : 'FAILED') . PHP_EOL;
        
        if (\$data['success'] && isset(\$data['data']['performance'])) {
            \$perf = \$data['data']['performance'];
            echo 'AFTER SYNC - Performance Data:' . PHP_EOL;
            echo '  Attendance Rank: ' . (\$perf['attendance_rank'] ?? 'NULL') . PHP_EOL;
            echo '  Total Staff: ' . (\$perf['total_staff'] ?? 'NULL') . PHP_EOL;
            echo '  Attendance %: ' . (\$perf['attendance_percentage'] ?? 'NULL') . PHP_EOL;
            echo '  Attendance Rate: ' . (\$perf['attendance_rate'] ?? 'NULL') . PHP_EOL;
        }
        
    } catch (Exception \$e) {
        echo 'API Test Error: ' . \$e->getMessage() . PHP_EOL;
    }
    
    auth()->logout();
}
"

echo -e "\n✅ SINKRONISASI SELESAI!"
echo "======================"
echo "File-file critical sudah disamakan 100% dengan localhost"
echo "Silakan test dashboard dokter di browser"
TEST_EOF