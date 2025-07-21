#!/bin/bash

# Simple Automated 500 Error Diagnosis
echo "🔍 AUTOMATED 500 ERROR DIAGNOSIS"
echo "================================"

# Use environment variable or prompt for password
if [ -z "$SSH_PASS" ]; then
    echo "💡 Set SSH_PASS environment variable or enter password when prompted"
    echo "Example: SSH_PASS='yourpassword' ./simple-auto-diagnosis.sh"
    echo
    read -s -p "🔐 SSH Password: " SSH_PASS
    echo
fi

HOST="srv556.hstgr.io"
USER="u196138154"
PATH_APP="/home/u196138154/domains/dokterkuklinik.com/public_html"

echo "🚀 Connecting to production server..."

# Execute comprehensive diagnosis
sshpass -p "$SSH_PASS" ssh -o StrictHostKeyChecking=no "$USER@$HOST" << EOF
cd $PATH_APP

echo "📋 === PRODUCTION 500 ERROR ANALYSIS ==="
echo "========================================"

echo
echo "🔍 STEP 1: Recent Laravel Errors"
echo "--------------------------------"
if [ -f "storage/logs/laravel.log" ]; then
    echo "📜 Last 30 lines of Laravel log:"
    tail -30 storage/logs/laravel.log
    echo
    echo "🚨 Recent errors with 'ERROR' keyword:"
    grep -n "ERROR" storage/logs/laravel.log | tail -5
    echo
    echo "🔥 Recent exceptions:"
    grep -n -A 2 "Exception" storage/logs/laravel.log | tail -10
else
    echo "❌ Laravel log file not found"
fi

echo
echo "🔍 STEP 2: Database Connection Test"
echo "----------------------------------"
php artisan tinker --execute="
try {
    \$pdo = \DB::connection()->getPdo();
    echo '✅ Database connection: SUCCESS\n';
    
    \$pegawaiCount = \App\Models\Pegawai::count();
    echo '📊 Pegawai records: ' . \$pegawaiCount . '\n';
    
    \$userCount = \App\Models\User::count();
    echo '📊 User records: ' . \$userCount . '\n';
    
} catch (Exception \$e) {
    echo '❌ Database error: ' . \$e->getMessage() . '\n';
}"

echo
echo "🔍 STEP 3: Paramedis User Analysis"
echo "----------------------------------"
php artisan tinker --execute="
try {
    // Find naning or any paramedis user
    \$naning = \App\Models\Pegawai::where('username', 'naning')
        ->orWhere('nama_lengkap', 'LIKE', '%naning%')
        ->orWhere('nama_lengkap', 'LIKE', '%Naning%')
        ->first();
    
    if (\$naning) {
        echo '✅ Found naning user:\n';
        echo '   ID: ' . \$naning->id . '\n';
        echo '   Name: ' . \$naning->nama_lengkap . '\n';
        echo '   Username: ' . \$naning->username . '\n';
        echo '   NIK: ' . \$naning->nik . '\n';
        echo '   Type: ' . \$naning->jenis_pegawai . '\n';
        echo '   Active: ' . (\$naning->aktif ? 'YES' : 'NO') . '\n';
        echo '   Email: ' . (\$naning->email ?? 'NULL') . '\n';
    } else {
        echo '❌ Naning user not found\n';
        echo '📋 Available paramedis users:\n';
        \$paramedis = \App\Models\Pegawai::where('jenis_pegawai', 'Paramedis')->take(5)->get();
        foreach (\$paramedis as \$p) {
            echo '   - ' . \$p->nama_lengkap . ' (' . \$p->username . ')\n';
        }
    }
} catch (Exception \$e) {
    echo '❌ User analysis error: ' . \$e->getMessage() . '\n';
}"

echo
echo "🔍 STEP 4: Authentication Flow Test"
echo "-----------------------------------"
php artisan tinker --execute="
try {
    echo '🧪 Testing authentication components...\n';
    
    // Test Role existence
    \$paramedisRole = \Spatie\Permission\Models\Role::where('name', 'paramedis')->first();
    echo 'Paramedis role: ' . (\$paramedisRole ? 'EXISTS' : 'MISSING') . '\n';
    
    // Test authentication controller
    \$controller = new \App\Http\Controllers\Auth\UnifiedAuthController();
    echo 'UnifiedAuthController: LOADABLE\n';
    
    // Test a complete auth flow
    \$testUser = \App\Models\Pegawai::where('jenis_pegawai', 'Paramedis')->first();
    if (\$testUser) {
        echo 'Test user: ' . \$testUser->nama_lengkap . '\n';
        
        \$roleName = match(\$testUser->jenis_pegawai) {
            'Paramedis' => 'paramedis',
            'Non-Paramedis' => 'non_paramedis',
            default => 'petugas'
        };
        echo 'Role mapping: ' . \$testUser->jenis_pegawai . ' -> ' . \$roleName . '\n';
        
        \$role = \Spatie\Permission\Models\Role::where('name', \$roleName)->first();
        echo 'Role found: ' . (\$role ? 'YES' : 'NO') . '\n';
        
        if (\$role) {
            echo 'Role ID: ' . \$role->id . '\n';
        }
    }
    
} catch (Exception \$e) {
    echo '❌ Auth flow error: ' . \$e->getMessage() . '\n';
    echo 'File: ' . \$e->getFile() . ':' . \$e->getLine() . '\n';
} catch (Error \$e) {
    echo '❌ Fatal error: ' . \$e->getMessage() . '\n';
    echo 'File: ' . \$e->getFile() . ':' . \$e->getLine() . '\n';
}"

echo
echo "🔍 STEP 5: Migration and Schema Check"
echo "-------------------------------------"
echo "📋 Migration status:"
php artisan migrate:status | tail -10

echo
echo "📊 Database schema check:"
php artisan tinker --execute="
try {
    \$columns = \DB::select('SHOW COLUMNS FROM pegawais WHERE Field = \"email\"');
    echo 'Email column in pegawais: ' . (count(\$columns) > 0 ? 'EXISTS' : 'MISSING') . '\n';
    
    if (count(\$columns) > 0) {
        \$col = \$columns[0];
        echo 'Email column type: ' . \$col->Type . '\n';
        echo 'Email column nullable: ' . \$col->Null . '\n';
        echo 'Email column key: ' . \$col->Key . '\n';
    }
    
    // Check for email duplicates
    \$duplicates = \DB::select('SELECT COUNT(*) as total FROM (SELECT email FROM pegawais WHERE email IS NOT NULL GROUP BY email HAVING COUNT(*) > 1) as dups');
    echo 'Duplicate emails: ' . \$duplicates[0]->total . '\n';
    
} catch (Exception \$e) {
    echo 'Schema check error: ' . \$e->getMessage() . '\n';
}"

echo
echo "🔍 STEP 6: Route and Endpoint Test"
echo "----------------------------------"
echo "📋 Login routes:"
php artisan route:list | grep -E "(login|auth)" | head -5

echo
echo "🌐 HTTP endpoint test:"
curl -s -I "https://dokterkuklinik.com/login" | head -5

echo
echo "🔍 STEP 7: PHP Configuration"
echo "----------------------------"
php -r "echo 'PHP Version: ' . PHP_VERSION . '\n';"
php -r "echo 'Memory Limit: ' . ini_get('memory_limit') . '\n';"
php -r "echo 'Max Execution Time: ' . ini_get('max_execution_time') . '\n';"
php -r "echo 'Error Reporting: ' . ini_get('error_reporting') . '\n';"
php -r "echo 'Display Errors: ' . ini_get('display_errors') . '\n';"

echo
echo "🔍 STEP 8: File Permissions"
echo "---------------------------"
echo "📁 Storage directory:"
ls -la storage/logs/ | head -3
echo "📁 Bootstrap cache:"
ls -la bootstrap/cache/ | head -3

echo
echo "🔍 STEP 9: Recent Errors in PHP Logs"
echo "------------------------------------"
find . -name "error_log" -o -name "php_errors.log" | head -2 | xargs tail -10 2>/dev/null || echo "No PHP error logs found"

echo
echo "🎯 === ANALYSIS COMPLETE ==="
echo "============================"
echo "Check the output above for specific error details."
echo "Look for:"
echo "- Database connection errors"
echo "- Missing roles or users"
echo "- PHP fatal errors"
echo "- Permission issues"
echo "- Migration problems"

EOF

echo
echo "🏁 Automated diagnosis completed!"
echo "================================="

# Clean up
unset SSH_PASS