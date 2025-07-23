<?php

/**
 * World Class Audit: Fix Dokter Dashboard 500 Error
 * Comprehensive diagnostic and repair script for dokter mobile app dashboard
 */

echo "🏥 WORLD CLASS AUDIT: Dokter Dashboard 500 Error Fix\n";
echo "====================================================\n\n";

require_once 'vendor/autoload.php';

// Load environment
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

try {
    // Database connection
    $host = $_ENV['DB_HOST'] ?? 'localhost';
    $dbname = $_ENV['DB_DATABASE'] ?? '';
    $username = $_ENV['DB_USERNAME'] ?? '';
    $password = $_ENV['DB_PASSWORD'] ?? '';
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    echo "✅ Database connection established\n\n";
    
    // ===================================
    // 1. DOKTER DATA ANALYSIS
    // ===================================
    echo "🔍 STEP 1: Dokter Data Analysis\n";
    echo "-------------------------------\n";
    
    $dokterStats = $pdo->query("
        SELECT 
            COUNT(*) as total_dokters,
            COUNT(CASE WHEN status_akun = 'Aktif' THEN 1 END) as active_dokters,
            COUNT(CASE WHEN password IS NOT NULL THEN 1 END) as with_password,
            COUNT(CASE WHEN user_id IS NOT NULL THEN 1 END) as linked_users
        FROM dokters
    ")->fetch();
    
    echo "📊 Dokter Statistics:\n";
    foreach ($dokterStats as $key => $value) {
        echo "   $key: $value\n";
    }
    
    // Check specific dokter accounts
    $dokters = $pdo->query("
        SELECT d.id, d.nama_lengkap, d.username, d.status_akun, 
               u.email as user_email, r.name as role_name
        FROM dokters d
        LEFT JOIN users u ON d.user_id = u.id
        LEFT JOIN roles r ON u.role_id = r.id
        LIMIT 5
    ")->fetchAll();
    
    echo "\n📋 Dokter Accounts:\n";
    foreach ($dokters as $dokter) {
        echo "   ID: {$dokter['id']} | {$dokter['nama_lengkap']} | Status: {$dokter['status_akun']}\n";
        echo "      Username: {$dokter['username']} | Role: {$dokter['role_name']}\n";
    }
    
    // ===================================
    // 2. TINDAKAN DATA ANALYSIS
    // ===================================
    echo "\n🔍 STEP 2: Tindakan Data Analysis\n";
    echo "---------------------------------\n";
    
    $tindakanStats = $pdo->query("
        SELECT 
            COUNT(*) as total_tindakan,
            COUNT(CASE WHEN dokter_id IS NOT NULL THEN 1 END) as with_dokter,
            COUNT(CASE WHEN DATE(tanggal_tindakan) = CURDATE() THEN 1 END) as today_tindakan,
            COUNT(CASE WHEN DATE(tanggal_tindakan) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 1 END) as week_tindakan
        FROM tindakan
    ")->fetch();
    
    echo "📊 Tindakan Statistics:\n";
    foreach ($tindakanStats as $key => $value) {
        echo "   $key: $value\n";
    }
    
    // ===================================
    // 3. KEHADIRAN DATA ANALYSIS
    // ===================================
    echo "\n🔍 STEP 3: Kehadiran Data Analysis\n";
    echo "----------------------------------\n";
    
    // Check if kehadiran table exists
    $kehadiranExists = $pdo->query("SHOW TABLES LIKE 'kehadiran'")->rowCount() > 0;
    
    if ($kehadiranExists) {
        $kehadiranStats = $pdo->query("
            SELECT 
                COUNT(*) as total_kehadiran,
                COUNT(CASE WHEN user_id IN (SELECT user_id FROM dokters WHERE user_id IS NOT NULL) THEN 1 END) as dokter_kehadiran,
                COUNT(CASE WHEN DATE(tanggal) = CURDATE() THEN 1 END) as today_kehadiran
            FROM kehadiran
        ")->fetch();
        
        echo "📊 Kehadiran Statistics:\n";
        foreach ($kehadiranStats as $key => $value) {
            echo "   $key: $value\n";
        }
    } else {
        echo "❌ Kehadiran table not found\n";
    }
    
    // ===================================
    // 4. GENERATE SAMPLE STATS DATA
    // ===================================
    echo "\n🔍 STEP 4: Generate Sample Stats Data\n";
    echo "------------------------------------\n";
    
    // Calculate actual stats for dokter dashboard
    $dashboardStats = [];
    
    // Attendance stats
    if ($kehadiranExists) {
        $attendanceToday = $pdo->query("
            SELECT COUNT(*) as count 
            FROM kehadiran k
            JOIN dokters d ON k.user_id = d.user_id
            WHERE DATE(k.tanggal) = CURDATE()
        ")->fetch()['count'] ?? 0;
        
        $totalDokters = $pdo->query("SELECT COUNT(*) as count FROM dokters WHERE status_akun = 'Aktif'")->fetch()['count'] ?? 1;
        
        $dashboardStats['attendance_current'] = $attendanceToday;
        $dashboardStats['attendance_rate'] = round(($attendanceToday / max($totalDokters, 1)) * 100, 1);
    } else {
        $dashboardStats['attendance_current'] = 0;
        $dashboardStats['attendance_rate'] = 0;
    }
    
    // Patient stats
    $patientStats = $pdo->query("
        SELECT 
            COUNT(CASE WHEN DATE(tanggal_tindakan) = CURDATE() THEN 1 END) as today_patients,
            COUNT(CASE WHEN DATE(tanggal_tindakan) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 1 END) as week_patients,
            COUNT(CASE WHEN DATE(tanggal_tindakan) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN 1 END) as month_patients
        FROM tindakan 
        WHERE dokter_id IS NOT NULL
    ")->fetch();
    
    $dashboardStats['patients_today'] = $patientStats['today_patients'] ?? 0;
    $dashboardStats['patients_week'] = $patientStats['week_patients'] ?? 0;
    $dashboardStats['patients_month'] = $patientStats['month_patients'] ?? 0;
    
    // Revenue stats
    $revenueStats = $pdo->query("
        SELECT 
            COALESCE(SUM(CASE WHEN DATE(tanggal_tindakan) = CURDATE() THEN jasa_dokter END), 0) as today_revenue,
            COALESCE(SUM(CASE WHEN DATE(tanggal_tindakan) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN jasa_dokter END), 0) as week_revenue,
            COALESCE(SUM(CASE WHEN DATE(tanggal_tindakan) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN jasa_dokter END), 0) as month_revenue
        FROM tindakan 
        WHERE dokter_id IS NOT NULL
    ")->fetch();
    
    $dashboardStats['revenue_today'] = $revenueStats['today_revenue'] ?? 0;
    $dashboardStats['revenue_week'] = $revenueStats['week_revenue'] ?? 0;
    $dashboardStats['revenue_month'] = $revenueStats['month_revenue'] ?? 0;
    
    echo "📊 Generated Dashboard Stats:\n";
    foreach ($dashboardStats as $key => $value) {
        echo "   $key: $value\n";
    }
    
    // ===================================
    // 5. CREATE SAMPLE API RESPONSE
    // ===================================
    echo "\n🔍 STEP 5: Sample API Response Structure\n";
    echo "---------------------------------------\n";
    
    $apiResponse = [
        'success' => true,
        'data' => [
            'stats' => $dashboardStats,
            'performance_data' => [
                'attendance_trend' => [
                    ['date' => date('Y-m-d', strtotime('-6 days')), 'value' => rand(80, 100)],
                    ['date' => date('Y-m-d', strtotime('-5 days')), 'value' => rand(80, 100)],
                    ['date' => date('Y-m-d', strtotime('-4 days')), 'value' => rand(80, 100)],
                    ['date' => date('Y-m-d', strtotime('-3 days')), 'value' => rand(80, 100)],
                    ['date' => date('Y-m-d', strtotime('-2 days')), 'value' => rand(80, 100)],
                    ['date' => date('Y-m-d', strtotime('-1 days')), 'value' => rand(80, 100)],
                    ['date' => date('Y-m-d'), 'value' => $dashboardStats['attendance_rate']],
                ],
                'patient_trend' => [
                    ['date' => date('Y-m-d', strtotime('-6 days')), 'value' => rand(5, 20)],
                    ['date' => date('Y-m-d', strtotime('-5 days')), 'value' => rand(5, 20)],
                    ['date' => date('Y-m-d', strtotime('-4 days')), 'value' => rand(5, 20)],
                    ['date' => date('Y-m-d', strtotime('-3 days')), 'value' => rand(5, 20)],
                    ['date' => date('Y-m-d', strtotime('-2 days')), 'value' => rand(5, 20)],
                    ['date' => date('Y-m-d', strtotime('-1 days')), 'value' => rand(5, 20)],
                    ['date' => date('Y-m-d'), 'value' => $dashboardStats['patients_today']],
                ]
            ],
            'recent_activities' => [
                [
                    'type' => 'tindakan',
                    'description' => 'Pemeriksaan rutin pasien',
                    'time' => date('H:i'),
                    'status' => 'completed'
                ],
                [
                    'type' => 'consultation',
                    'description' => 'Konsultasi dengan pasien',
                    'time' => date('H:i', strtotime('-1 hour')),
                    'status' => 'in_progress'
                ]
            ]
        ],
        'meta' => [
            'generated_at' => date('Y-m-d H:i:s'),
            'version' => '1.0.0'
        ]
    ];
    
    echo "✅ Sample API Response Generated\n";
    echo "📋 Response structure: " . json_encode($apiResponse, JSON_PRETTY_PRINT) . "\n";
    
    // ===================================
    // 6. RECOMMENDATIONS
    // ===================================
    echo "\n🔍 STEP 6: Recommendations & Fixes\n";
    echo "----------------------------------\n";
    
    echo "📋 CRITICAL RECOMMENDATIONS:\n\n";
    
    echo "1. 🔥 CREATE MISSING API ENDPOINT:\n";
    echo "   - Create DokterStatsController with stats() method\n";
    echo "   - Route: GET /api/dokter/stats or GET /dokter/api/stats\n";
    echo "   - Return structure shown above\n\n";
    
    echo "2. 🛠️ FIX JAVASCRIPT ERROR HANDLING:\n";
    echo "   - Add try-catch blocks around API calls\n";
    echo "   - Implement fallback data for failed requests\n";
    echo "   - Add loading states and error messages\n\n";
    
    echo "3. 📊 DATABASE OPTIMIZATION:\n";
    echo "   - Ensure dokter-user relationships are properly linked\n";
    echo "   - Add indexes for performance on stats queries\n";
    echo "   - Validate all dokter accounts have proper status\n\n";
    
    echo "4. 🔧 IMMEDIATE FIXES:\n";
    echo "   - Enable Laravel debug mode to see detailed 500 errors\n";
    echo "   - Clear all Laravel caches\n";
    echo "   - Check Laravel error logs for specific error details\n\n";
    
    echo "✅ WORLD CLASS AUDIT COMPLETED\n";
    echo "📅 Analysis completed at: " . date('Y-m-d H:i:s') . "\n";
    echo "🎯 Focus: Fix 500 error on dokter dashboard stats API\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}