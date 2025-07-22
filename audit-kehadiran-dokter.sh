#!/bin/bash

# AUDIT MENDALAM FITUR KEHADIRAN DOKTER
# Bandingkan implementasi Hostinger vs Localhost

echo "🔍 AUDIT MENDALAM FITUR KEHADIRAN DOKTER"
echo "========================================"
echo "Membandingkan implementasi Hostinger vs Localhost"
echo "Timestamp: $(date '+%Y-%m-%d %H:%M:%S')"
echo ""

HOST="153.92.8.132"
PORT="65002"
USER="u454362045"
PASS="LaTahzan@01"

# Create comprehensive audit script
cat > /tmp/audit_kehadiran.sh << 'AUDIT_EOF'
#!/bin/bash
cd domains/dokterkuklinik.com/public_html

echo "📊 1. AUDIT MODEL DAN STRUKTUR DATABASE"
echo "====================================="

echo "🔍 DokterPresensi Model Analysis:"
php artisan tinker --execute="
echo 'DOKTER PRESENSI MODEL:' . PHP_EOL;
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
echo 'ATTENDANCE RECAP MODEL:' . PHP_EOL;
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

echo -e "\n🔍 Dokter Model Analysis:"
php artisan tinker --execute="
\$dokters = App\\Models\\Dokter::with('user')->get();
echo 'DOKTER MODELS:' . PHP_EOL;
echo 'Total dokters: ' . \$dokters->count() . PHP_EOL;
foreach (\$dokters as \$d) {
    echo '  ID: ' . \$d->id . ' | Username: ' . (\$d->username ?? 'NULL') . ' | Nama: ' . \$d->nama_lengkap . PHP_EOL;
}
"

echo -e "\n📊 2. AUDIT CONTROLLER DASHBOARD DOKTER"
echo "======================================"

echo "🔍 DokterDashboardController Analysis:"

# Check if controller exists
if [ -f "app/Http/Controllers/Api/V2/Dashboards/DokterDashboardController.php" ]; then
    echo "✅ DokterDashboardController exists"
    
    # Check key methods
    echo "Methods check:"
    grep -n "public function" app/Http/Controllers/Api/V2/Dashboards/DokterDashboardController.php | head -10
    
    echo -e "\nPerformance stats method check:"
    grep -A 5 -B 5 "getPerformanceStats" app/Http/Controllers/Api/V2/Dashboards/DokterDashboardController.php | head -15
    
    echo -e "\nAttendanceRecap usage check:"
    grep -n "AttendanceRecap" app/Http/Controllers/Api/V2/Dashboards/DokterDashboardController.php
    
    echo -e "\nDokterPresensi usage check:"
    grep -n "DokterPresensi" app/Http/Controllers/Api/V2/Dashboards/DokterDashboardController.php
    
else
    echo "❌ DokterDashboardController NOT FOUND!"
fi

echo -e "\n📊 3. AUDIT PERHITUNGAN KEHADIRAN"
echo "==============================="

php artisan tinker --execute="
echo 'TESTING ATTENDANCE CALCULATION:' . PHP_EOL;

// Test for specific dokter
\$dokterYaya = App\\Models\\Dokter::where('username', 'yaya')->first();
if (\$dokterYaya) {
    echo 'Testing for Dr. Yaya (ID: ' . \$dokterYaya->id . ')' . PHP_EOL;
    
    // Count dokter_presensis records
    \$presensiCount = App\\Models\\DokterPresensi::where('dokter_id', \$dokterYaya->id)->count();
    echo 'DokterPresensi records: ' . \$presensiCount . PHP_EOL;
    
    // Get this month's attendance
    \$thisMonth = App\\Models\\DokterPresensi::where('dokter_id', \$dokterYaya->id)
        ->whereMonth('tanggal', date('n'))
        ->whereYear('tanggal', date('Y'))
        ->count();
    echo 'This month attendance: ' . \$thisMonth . PHP_EOL;
    
    // Calculate working days in current month
    \$startDate = new DateTime(date('Y-m-01'));
    \$endDate = new DateTime(date('Y-m-t'));
    \$workingDays = 0;
    
    while (\$startDate <= \$endDate) {
        if (\$startDate->format('N') != 7) { // Not Sunday
            \$workingDays++;
        }
        \$startDate->modify('+1 day');
    }
    
    echo 'Working days this month: ' . \$workingDays . PHP_EOL;
    \$percentage = \$workingDays > 0 ? round((\$thisMonth / \$workingDays) * 100, 2) : 0;
    echo 'Calculated percentage: ' . \$percentage . '%' . PHP_EOL;
}
"

echo -e "\n📊 4. AUDIT API DASHBOARD RESPONSE"
echo "==============================="

echo "🔍 Testing Dashboard API Response:"
php artisan tinker --execute="
// Simulate dashboard API call
\$yayaUser = App\\Models\\User::whereHas('dokter', function(\$q) {
    \$q->where('username', 'yaya');
})->first();

if (\$yayaUser) {
    auth()->login(\$yayaUser);
    
    echo 'DASHBOARD API SIMULATION:' . PHP_EOL;
    
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

echo -e "\n📊 5. AUDIT FRONTEND COMPONENTS"
echo "=============================="

echo "🔍 Dashboard Frontend Files:"

# Check if mobile app exists
if [ -f "resources/js/components/dokter/Dashboard.tsx" ]; then
    echo "✅ Dokter Dashboard.tsx exists"
    
    # Check attendance ranking implementation
    echo "Attendance ranking check:"
    grep -n -A 3 -B 3 "attendance.*rank\|ranking" resources/js/components/dokter/Dashboard.tsx || echo "No ranking references found"
    
    echo -e "\nPerformance stats usage:"
    grep -n -A 3 -B 3 "performance" resources/js/components/dokter/Dashboard.tsx | head -10
    
else
    echo "❌ Dokter Dashboard.tsx NOT FOUND!"
fi

# Check mobile app entry point
if [ -f "resources/js/dokter-mobile-app.tsx" ]; then
    echo "✅ dokter-mobile-app.tsx exists"
else
    echo "❌ dokter-mobile-app.tsx NOT FOUND!"
fi

# Check blade template
if [ -f "resources/views/mobile/dokter/app.blade.php" ]; then
    echo "✅ mobile dokter app.blade.php exists"
    
    echo "Meta tags check:"
    grep -n "meta.*user-data\|api-token" resources/views/mobile/dokter/app.blade.php
else
    echo "❌ mobile dokter app.blade.php NOT FOUND!"
fi

echo -e "\n📊 6. AUDIT ROUTES DAN MIDDLEWARE"
echo "=============================="

echo "🔍 Routes Analysis:"
grep -n -A 10 -B 5 "dokter.*mobile-app\|dashboards.*dokter" routes/web.php routes/api.php 2>/dev/null || echo "No dokter routes found"

echo -e "\n📊 7. SUMMARY DAN REKOMENDASI"
echo "==========================="
echo "Audit selesai. Lihat hasil di atas untuk perbedaan implementasi."

AUDIT_EOF

# Execute audit on Hostinger
echo "🚀 Menjalankan audit pada HOSTINGER..."
sshpass -p "$PASS" ssh -p $PORT -o StrictHostKeyChecking=no $USER@$HOST 'bash -s' < /tmp/audit_kehadiran.sh

# Clean up
rm /tmp/audit_kehadiran.sh

echo -e "\n🔍 Sekarang audit localhost untuk perbandingan..."