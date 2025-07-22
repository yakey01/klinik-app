#!/bin/bash

# AUDIT LOCALHOST UNTUK PERBANDINGAN

echo "🔍 AUDIT LOCALHOST FITUR KEHADIRAN DOKTER"
echo "========================================"
echo "Untuk perbandingan dengan Hostinger"
echo "Timestamp: $(date '+%Y-%m-%d %H:%M:%S')"
echo ""

cd /Users/kym/Herd/Dokterku

echo "📊 1. AUDIT MODEL DAN STRUKTUR DATABASE (LOCALHOST)"
echo "=================================================="

echo "🔍 DokterPresensi Model Analysis:"
php artisan tinker --execute="
echo 'DOKTER PRESENSI MODEL (LOCALHOST):' . PHP_EOL;
if (class_exists('App\\Models\\DokterPresensi')) {
    \$model = new App\\Models\\DokterPresensi();
    echo 'Table: ' . \$model->getTable() . PHP_EOL;
    echo 'Fillable: ' . implode(', ', \$model->getFillable()) . PHP_EOL;
    echo 'Casts: ' . json_encode(\$model->getCasts()) . PHP_EOL;
    
    // Check actual data count
    \$count = App\\Models\\DokterPresensi::count();
    echo 'Total records: ' . \$count . PHP_EOL;
    
    if (\$count > 0) {
        \$sample = App\\Models\\DokterPresensi::latest()->first();
        echo 'Sample record:' . PHP_EOL;
        echo '  ID: ' . \$sample->id . PHP_EOL;
        echo '  Dokter ID: ' . \$sample->dokter_id . PHP_EOL;
        echo '  Tanggal: ' . \$sample->tanggal . PHP_EOL;
        echo '  Jam Masuk: ' . \$sample->jam_masuk . PHP_EOL;
        echo '  Jam Pulang: ' . \$sample->jam_pulang . PHP_EOL;
    }
} else {
    echo 'DokterPresensi model NOT FOUND!' . PHP_EOL;
}
"

echo -e "\n🔍 AttendanceRecap Model Analysis:"
php artisan tinker --execute="
echo 'ATTENDANCE RECAP MODEL (LOCALHOST):' . PHP_EOL;
if (class_exists('App\\Models\\AttendanceRecap')) {
    echo 'Model exists: YES' . PHP_EOL;
    
    // Test getRecapData method
    \$month = date('n');
    \$year = date('Y');
    echo 'Testing getRecapData for month: ' . \$month . ', year: ' . \$year . PHP_EOL;
    
    \$recapData = App\\Models\\AttendanceRecap::getRecapData(\$month, \$year, 'Dokter');
    echo 'Recap data count: ' . \$recapData->count() . PHP_EOL;
    
    foreach (\$recapData as \$data) {
        echo '  Staff: ' . \$data['staff_name'] . ' | Percentage: ' . \$data['attendance_percentage'] . '% | Rank: ' . \$data['rank'] . PHP_EOL;
    }
} else {
    echo 'AttendanceRecap model NOT FOUND!' . PHP_EOL;
}
"

echo -e "\n📊 2. AUDIT CONTROLLER DASHBOARD DOKTER (LOCALHOST)"
echo "================================================="

echo "🔍 DokterDashboardController Analysis:"

# Check if controller exists
if [ -f "app/Http/Controllers/Api/V2/Dashboards/DokterDashboardController.php" ]; then
    echo "✅ DokterDashboardController exists"
    
    # Check AttendanceRecap usage
    echo -e "\nAttendanceRecap usage check:"
    grep -n "AttendanceRecap" app/Http/Controllers/Api/V2/Dashboards/DokterDashboardController.php
    
    # Check DokterPresensi usage
    echo -e "\nDokterPresensi usage check:"
    grep -n "DokterPresensi" app/Http/Controllers/Api/V2/Dashboards/DokterDashboardController.php
    
    # Check getPerformanceStats method
    echo -e "\nPerformance stats method content:"
    sed -n '/private function getPerformanceStats/,/^    }/p' app/Http/Controllers/Api/V2/Dashboards/DokterDashboardController.php | head -20
else
    echo "❌ DokterDashboardController NOT FOUND!"
fi

echo -e "\n📊 3. AUDIT API DASHBOARD RESPONSE (LOCALHOST)"
echo "============================================="

echo "🔍 Testing Dashboard API Response:"
php artisan tinker --execute="
// Simulate dashboard API call
\$yayaUser = App\\Models\\User::whereHas('dokter', function(\$q) {
    \$q->where('username', 'yaya');
})->first();

if (\$yayaUser) {
    auth()->login(\$yayaUser);
    
    echo 'DASHBOARD API SIMULATION (LOCALHOST):' . PHP_EOL;
    
    // Test if controller exists and can be instantiated
    if (class_exists('App\\Http\\Controllers\\Api\\V2\\Dashboards\\DokterDashboardController')) {
        try {
            \$controller = new App\\Http\\Controllers\\Api\\V2\\Dashboards\\DokterDashboardController();
            \$request = new Illuminate\\Http\\Request();
            
            \$response = \$controller->index(\$request);
            \$data = \$response->getData(true);
            
            echo 'API Response Status: ' . (\$data['success'] ? 'SUCCESS' : 'FAILED') . PHP_EOL;
            
            if (\$data['success']) {
                echo 'Performance data:' . PHP_EOL;
                if (isset(\$data['data']['performance'])) {
                    \$perf = \$data['data']['performance'];
                    echo '  Attendance Rank: ' . (\$perf['attendance_rank'] ?? 'NULL') . PHP_EOL;
                    echo '  Total Staff: ' . (\$perf['total_staff'] ?? 'NULL') . PHP_EOL;
                    echo '  Attendance %: ' . (\$perf['attendance_percentage'] ?? 'NULL') . PHP_EOL;
                    echo '  Attendance Rate: ' . (\$perf['attendance_rate'] ?? 'NULL') . PHP_EOL;
                } else {
                    echo '  Performance data MISSING!' . PHP_EOL;
                }
                
                echo 'Stats data:' . PHP_EOL;
                if (isset(\$data['data']['stats'])) {
                    \$stats = \$data['data']['stats'];
                    echo '  Attendance Today: ' . json_encode(\$stats['attendance_today'] ?? 'NULL') . PHP_EOL;
                    echo '  Shifts Week: ' . (\$stats['shifts_week'] ?? 'NULL') . PHP_EOL;
                }
            } else {
                echo 'API Error: ' . (\$data['message'] ?? 'Unknown error') . PHP_EOL;
            }
            
        } catch (Exception \$e) {
            echo 'Controller test failed: ' . \$e->getMessage() . PHP_EOL;
        }
    } else {
        echo 'DokterDashboardController class NOT FOUND!' . PHP_EOL;
    }
    
    auth()->logout();
} else {
    echo 'Dr. Yaya user not found for API test!' . PHP_EOL;
}
"

echo -e "\n📊 4. AUDIT FRONTEND COMPONENTS (LOCALHOST)"
echo "========================================="

echo "🔍 Dashboard Frontend Files:"

# Check Dashboard.tsx
if [ -f "resources/js/components/dokter/Dashboard.tsx" ]; then
    echo "✅ Dokter Dashboard.tsx exists"
    
    # Check attendance ranking implementation
    echo "Attendance ranking implementation:"
    grep -n -A 5 -B 5 "attendance.*rank\|attendance_rank" resources/js/components/dokter/Dashboard.tsx | head -15
    
else
    echo "❌ Dokter Dashboard.tsx NOT FOUND!"
fi

echo -e "\n📊 5. PERBANDINGAN FILE CRITICAL"
echo "=============================="

echo "🔍 Comparing file checksums:"

# Check AttendanceRecap.php
if [ -f "app/Models/AttendanceRecap.php" ]; then
    echo "AttendanceRecap.php checksum:"
    md5 app/Models/AttendanceRecap.php
else
    echo "❌ AttendanceRecap.php NOT FOUND!"
fi

# Check DokterDashboardController.php
if [ -f "app/Http/Controllers/Api/V2/Dashboards/DokterDashboardController.php" ]; then
    echo "DokterDashboardController.php checksum:"
    md5 app/Http/Controllers/Api/V2/Dashboards/DokterDashboardController.php
else
    echo "❌ DokterDashboardController.php NOT FOUND!"
fi

# Check Dashboard.tsx
if [ -f "resources/js/components/dokter/Dashboard.tsx" ]; then
    echo "Dashboard.tsx checksum:"
    md5 resources/js/components/dokter/Dashboard.tsx
else
    echo "❌ Dashboard.tsx NOT FOUND!"
fi

echo -e "\n✅ AUDIT LOCALHOST SELESAI"