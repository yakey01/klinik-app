<?php

/**
 * HOSTINGER DEPLOYMENT SCRIPT
 * Deploy all fixes and run diagnostic on production
 */

require_once __DIR__ . '/vendor/autoload.php';

use Carbon\Carbon;

echo "🚀 HOSTINGER DEPLOYMENT SCRIPT" . PHP_EOL;
echo "=" . str_repeat("=", 50) . PHP_EOL;
echo "Timestamp: " . Carbon::now()->format('Y-m-d H:i:s') . PHP_EOL . PHP_EOL;

echo "📋 PRE-DEPLOYMENT CHECKLIST:" . PHP_EOL;
echo "✅ Fixed DokterDashboardController attendance table usage" . PHP_EOL;
echo "✅ Updated mobile app route to use dokter.nama_lengkap" . PHP_EOL;
echo "✅ Fixed AttendanceRecap to use correct name field" . PHP_EOL;
echo "✅ Created comprehensive diagnostic tools" . PHP_EOL . PHP_EOL;

echo "🔧 FILES TO DEPLOY TO HOSTINGER:" . PHP_EOL;
echo "1. app/Http/Controllers/Api/V2/Dashboards/DokterDashboardController.php" . PHP_EOL;
echo "2. routes/web.php" . PHP_EOL;
echo "3. app/Models/AttendanceRecap.php" . PHP_EOL;
echo "4. hostinger-production-diagnostic.php (for analysis)" . PHP_EOL;
echo "5. public/hostinger-debug.js (for frontend debugging)" . PHP_EOL . PHP_EOL;

echo "📤 DEPLOYMENT COMMANDS FOR HOSTINGER:" . PHP_EOL;
echo str_repeat("-", 30) . PHP_EOL;

$deploymentCommands = [
    "# 1. Upload files via FTP/SSH/File Manager",
    "# 2. Clear all caches",
    "php artisan cache:clear",
    "php artisan config:clear", 
    "php artisan route:clear",
    "php artisan view:clear",
    
    "# 3. Run diagnostic",
    "php hostinger-production-diagnostic.php",
    
    "# 4. Check database data",
    "php artisan tinker --execute=\"echo 'Dr. Yaya data: '; \$d = App\\Models\\Dokter::where('username', 'yaya')->first(); echo \$d ? \$d->nama_lengkap : 'Not found';\"",
    
    "# 5. Test API endpoint",
    "curl -H 'Accept: application/json' https://dokterkuklinik.com/api/v2/dashboards/dokter/",
    
    "# 6. Optimize for production",
    "php artisan optimize",
    "composer dump-autoload --optimize"
];

foreach ($deploymentCommands as $command) {
    echo $command . PHP_EOL;
}

echo str_repeat("-", 30) . PHP_EOL . PHP_EOL;

echo "🔍 PRODUCTION TROUBLESHOOTING STEPS:" . PHP_EOL;
echo "1. Run hostinger-production-diagnostic.php on production" . PHP_EOL;
echo "2. Check if database contains correct Dr. Yaya data" . PHP_EOL;
echo "3. Verify API authentication is working" . PHP_EOL;
echo "4. Test frontend debug script in browser" . PHP_EOL;
echo "5. Compare API responses between localhost and production" . PHP_EOL;
echo "6. Check server error logs" . PHP_EOL . PHP_EOL;

echo "🎯 EXPECTED RESULTS AFTER DEPLOYMENT:" . PHP_EOL;
echo "- Welcome message should show 'Dr. Yaya Mulyana, M.Kes'" . PHP_EOL;
echo "- Attendance ranking should appear" . PHP_EOL;
echo "- Schedule data should load properly" . PHP_EOL;
echo "- Dashboard API should return complete data" . PHP_EOL . PHP_EOL;

echo "🆘 IF ISSUES PERSIST:" . PHP_EOL;
echo "1. Check if production database is different from localhost" . PHP_EOL;
echo "2. Verify all file uploads were successful" . PHP_EOL;
echo "3. Check for PHP/Laravel version differences" . PHP_EOL;
echo "4. Verify all dependencies are installed" . PHP_EOL;
echo "5. Check server permissions and error logs" . PHP_EOL . PHP_EOL;

// Generate a quick test script for production
$quickTestScript = <<<'PHP'
<?php
// Quick production test - save as test-production.php and run on Hostinger
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🔍 QUICK PRODUCTION TEST\n";
echo "Environment: " . config('app.env') . "\n";

$dokter = \App\Models\Dokter::where('username', 'yaya')->first();
if ($dokter) {
    echo "✅ Found Dr. Yaya:\n";
    echo "   Username: " . $dokter->username . "\n";
    echo "   Nama Lengkap: " . $dokter->nama_lengkap . "\n";
    echo "   User Name: " . $dokter->user->name . "\n";
} else {
    echo "❌ Dr. Yaya not found!\n";
}
PHP;

file_put_contents(__DIR__ . '/test-production.php', $quickTestScript);
echo "✅ Generated test-production.php for quick testing" . PHP_EOL;

echo "✅ DEPLOYMENT PREPARATION COMPLETE" . PHP_EOL;
echo "Ready to deploy to Hostinger!" . PHP_EOL;