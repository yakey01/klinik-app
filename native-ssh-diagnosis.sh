#!/bin/bash

# Native SSH Diagnosis without sshpass dependency
echo "🔍 NATIVE SSH 500 ERROR DIAGNOSIS"
echo "=================================="

HOST="u196138154@srv556.hstgr.io"
PATH_APP="/home/u196138154/domains/dokterkuklinik.com/public_html"

echo "🚀 Connecting to production server..."
echo "💡 You'll be prompted for SSH password"

# Use native SSH with inline commands
ssh -o StrictHostKeyChecking=no "$HOST" << 'EOF'
cd /home/u196138154/domains/dokterkuklinik.com/public_html

echo "🔥 === CRITICAL 500 ERROR ANALYSIS ==="
echo "======================================"

echo
echo "📜 Step 1: Recent Laravel Errors"
echo "-------------------------------"
if [ -f "storage/logs/laravel.log" ]; then
    echo "Last 10 critical errors:"
    tail -50 storage/logs/laravel.log | grep -E "(ERROR|CRITICAL|Fatal|Exception)" | tail -10
    echo
    echo "Most recent log entries:"
    tail -15 storage/logs/laravel.log
else
    echo "❌ Laravel log not found"
fi

echo
echo "🗃️  Step 2: Database & User Check"
echo "--------------------------------"
php artisan tinker --execute="
try {
    echo '🔌 Testing database connection...\n';
    \$pdo = \DB::connection()->getPdo();
    echo '✅ Database: CONNECTED\n';
    
    echo '\n👤 Checking naning user...\n';
    \$naning = \App\Models\Pegawai::where('username', 'naning')
        ->orWhere('nama_lengkap', 'LIKE', '%naning%')
        ->orWhere('nama_lengkap', 'LIKE', '%Naning%')
        ->first();
    
    if (\$naning) {
        echo '✅ Naning found: ' . \$naning->nama_lengkap . '\n';
        echo '   Username: ' . \$naning->username . '\n';
        echo '   NIK: ' . \$naning->nik . '\n';
        echo '   Type: ' . \$naning->jenis_pegawai . '\n';
        echo '   Active: ' . (\$naning->aktif ? 'YES' : 'NO') . '\n';
        echo '   Email: ' . (\$naning->email ?? 'NULL') . '\n';
        echo '   Password set: ' . (!empty(\$naning->password) ? 'YES' : 'NO') . '\n';
    } else {
        echo '❌ Naning user NOT FOUND\n';
        echo 'Available paramedis users:\n';
        \$paramedis = \App\Models\Pegawai::where('jenis_pegawai', 'Paramedis')->take(3)->get();
        foreach (\$paramedis as \$p) {
            echo '   - ' . \$p->nama_lengkap . ' (' . \$p->username . ')\n';
        }
    }
    
    echo '\n🎭 Checking roles...\n';
    \$paramedisRole = \Spatie\Permission\Models\Role::where('name', 'paramedis')->first();
    echo 'Paramedis role: ' . (\$paramedisRole ? 'EXISTS (ID: ' . \$paramedisRole->id . ')' : 'MISSING') . '\n';
    
    echo '\n📊 Database stats:\n';
    echo 'Total pegawai: ' . \App\Models\Pegawai::count() . '\n';
    echo 'Total users: ' . \App\Models\User::count() . '\n';
    echo 'Total roles: ' . \Spatie\Permission\Models\Role::count() . '\n';
    
} catch (Exception \$e) {
    echo '❌ DATABASE ERROR: ' . \$e->getMessage() . '\n';
    echo 'File: ' . \$e->getFile() . ':' . \$e->getLine() . '\n';
    echo 'This might be the cause of 500 errors!\n';
}"

echo
echo "🧪 Step 3: Authentication Flow Test"
echo "-----------------------------------"
php artisan tinker --execute="
try {
    echo '🔐 Testing authentication components...\n';
    
    // Test controller loading
    \$controller = new \App\Http\Controllers\Auth\UnifiedAuthController();
    echo '✅ UnifiedAuthController: LOADABLE\n';
    
    // Find a test paramedis user
    \$testUser = \App\Models\Pegawai::where('jenis_pegawai', 'Paramedis')->where('aktif', true)->first();
    
    if (\$testUser) {
        echo '✅ Test user found: ' . \$testUser->nama_lengkap . '\n';
        
        // Test role mapping logic
        \$roleName = match(\$testUser->jenis_pegawai) {
            'Paramedis' => 'paramedis',
            'Non-Paramedis' => 'non_paramedis',
            default => 'petugas'
        };
        echo '✅ Role mapping: ' . \$testUser->jenis_pegawai . ' -> ' . \$roleName . '\n';
        
        // Test role existence
        \$role = \Spatie\Permission\Models\Role::where('name', \$roleName)->first();
        echo 'Role check: ' . (\$role ? '✅ EXISTS' : '❌ MISSING') . '\n';
        
        // Test user creation logic
        \$userEmail = \$testUser->nik . '@pegawai.local';
        \$emailExists = \App\Models\User::where('email', \$userEmail)->exists();
        echo 'Email conflict: ' . (\$emailExists ? '⚠️  YES (conflict)' : '✅ NO (safe)') . '\n';
        
        // Test User model creation (without actually creating)
        echo 'User creation test: ';
        if (empty(\$testUser->nama_lengkap) || empty(\$testUser->username) || empty(\$userEmail)) {
            echo '❌ MISSING REQUIRED DATA\n';
        } else {
            echo '✅ DATA COMPLETE\n';
        }
    } else {
        echo '❌ No active paramedis users found\n';
    }
    
} catch (Exception \$e) {
    echo '❌ AUTH TEST ERROR: ' . \$e->getMessage() . '\n';
    echo 'File: ' . \$e->getFile() . ':' . \$e->getLine() . '\n';
    echo 'This could be causing the 500 error!\n';
}"

echo
echo "🔧 Step 4: Migration & Schema Status"
echo "------------------------------------"
echo "Migration status:"
php artisan migrate:status | tail -5

echo
echo "Email column check:"
php artisan tinker --execute="
try {
    \$columns = \DB::select('SHOW COLUMNS FROM pegawais WHERE Field = \"email\"');
    if (count(\$columns) > 0) {
        \$col = \$columns[0];
        echo '✅ Email column EXISTS\n';
        echo '   Type: ' . \$col->Type . '\n';
        echo '   Nullable: ' . \$col->Null . '\n';
        echo '   Key: ' . \$col->Key . '\n';
        
        // Check for duplicates
        \$duplicates = \DB::select('SELECT email, COUNT(*) as cnt FROM pegawais WHERE email IS NOT NULL GROUP BY email HAVING COUNT(*) > 1');
        echo '   Duplicates: ' . count(\$duplicates) . '\n';
        
    } else {
        echo '❌ Email column MISSING - this could cause 500 errors!\n';
    }
} catch (Exception \$e) {
    echo '❌ Schema check error: ' . \$e->getMessage() . '\n';
}"

echo
echo "🌐 Step 5: HTTP Endpoint Test"
echo "-----------------------------"
echo "Testing login page accessibility:"
curl -s -I "https://dokterkuklinik.com/login" | head -3

echo
echo "🔧 Step 6: Quick Emergency Fix"
echo "------------------------------"
echo "Applying emergency fixes..."
php artisan cache:clear >/dev/null 2>&1 && echo "✅ Cache cleared"
php artisan config:clear >/dev/null 2>&1 && echo "✅ Config cleared"
php artisan view:clear >/dev/null 2>&1 && echo "✅ Views cleared"

echo
echo "🎯 === DIAGNOSIS SUMMARY ==="
echo "============================"
echo "✅ Analysis completed"
echo "🔍 Check the output above for:"
echo "   - Database connection errors"
echo "   - Missing naning user"
echo "   - Role mapping issues"
echo "   - Email column problems"
echo "   - Authentication flow errors"
echo
echo "💡 Common 500 error causes found above will guide the fix"

EOF

echo
echo "🏁 Native SSH diagnosis completed!"
echo "Check the server output above for specific error details."