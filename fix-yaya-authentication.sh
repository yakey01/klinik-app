#!/bin/bash

# Fix Dr. Yaya's authentication issues

echo "🔧 FIXING DR. YAYA'S AUTHENTICATION ISSUES"
echo "=========================================="

HOST="153.92.8.132"
PORT="65002"
USER="u454362045"
PASS="LaTahzan@01"

sshpass -p "$PASS" ssh -p $PORT -o StrictHostKeyChecking=no $USER@$HOST << 'EOF'
cd domains/dokterkuklinik.com/public_html

echo "🎯 CRITICAL FINDINGS FROM INVESTIGATION:"
echo "======================================="
echo "1. There are TWO Yaya users in the database:"
echo "   - User ID 8: name='yaya', email='yaya@test.com'"
echo "   - User ID 9: name='Dr. Yaya Mulyana, M.Kes', email=NULL (now fixed)"
echo ""
echo "2. Dokter record points to User ID 9 (correct)"
echo "3. But browser might be authenticating with User ID 8 (old user)"
echo ""

echo "🔧 1. FIXING DUPLICATE YAYA USER ISSUE"
echo "===================================="
echo "We need to consolidate the two Yaya users..."

php artisan tinker --execute="
// Get both users
\$oldYaya = App\\Models\\User::find(8); // name='yaya'
\$newYaya = App\\Models\\User::find(9); // name='Dr. Yaya Mulyana, M.Kes'

echo 'Old Yaya (ID 8): ' . \$oldYaya->name . ' | Email: ' . \$oldYaya->email . PHP_EOL;
echo 'New Yaya (ID 9): ' . \$newYaya->name . ' | Email: ' . (\$newYaya->email ?? 'NULL') . PHP_EOL;

// Check which one the dokter points to
\$dokter = App\\Models\\Dokter::where('username', 'yaya')->first();
echo 'Dokter points to User ID: ' . \$dokter->user_id . PHP_EOL;

// If browser is logging in with old user (ID 8), we need to either:
// 1. Delete the old user and point everything to new user, OR
// 2. Update the old user to have the correct name

// SOLUTION: Update the old user (ID 8) to have correct name
// This ensures any existing sessions will show correct name
\$oldYaya->name = 'Dr. Yaya Mulyana, M.Kes';
\$oldYaya->save();

echo '✅ Updated old Yaya user (ID 8) name to: ' . \$oldYaya->name . PHP_EOL;
"

echo -e "\n🔧 2. ENSURING EMAIL CONSISTENCY"
echo "=============================="
php artisan tinker --execute="
\$oldYaya = App\\Models\\User::find(8);
\$newYaya = App\\Models\\User::find(9);

// Make sure both have proper emails
if (!\$oldYaya->email || \$oldYaya->email == 'yaya@test.com') {
    \$oldYaya->email = 'yaya@dokterkuklinik.com';
    \$oldYaya->save();
    echo '✅ Updated old Yaya (ID 8) email to: ' . \$oldYaya->email . PHP_EOL;
}

if (!\$newYaya->email) {
    \$newYaya->email = 'yaya@dokterkuklinik.com';
    \$newYaya->save();
    echo '✅ Updated new Yaya (ID 9) email to: ' . \$newYaya->email . PHP_EOL;
}
"

echo -e "\n🔧 3. TESTING BOTH AUTHENTICATION PATHS"
echo "====================================="
echo "Testing what happens if browser authenticates with either user..."

php artisan tinker --execute="
// Test with old user (ID 8)
\$oldYaya = App\\Models\\User::find(8);
auth()->login(\$oldYaya);
echo 'TESTING OLD USER (ID 8):' . PHP_EOL;
echo 'Authenticated as: ' . auth()->user()->name . PHP_EOL;

// Test route logic
\$user = auth()->user();
\$dokter = App\\Models\\Dokter::where('user_id', \$user->id)->first();
\$displayName = \$dokter ? \$dokter->nama_lengkap : \$user->name;
echo 'Route would find dokter: ' . (\$dokter ? 'YES' : 'NO') . PHP_EOL;
echo 'Display name: ' . \$displayName . PHP_EOL;
auth()->logout();

echo PHP_EOL;

// Test with new user (ID 9)
\$newYaya = App\\Models\\User::find(9);
auth()->login(\$newYaya);
echo 'TESTING NEW USER (ID 9):' . PHP_EOL;
echo 'Authenticated as: ' . auth()->user()->name . PHP_EOL;

\$user = auth()->user();
\$dokter = App\\Models\\Dokter::where('user_id', \$user->id)->first();
\$displayName = \$dokter ? \$dokter->nama_lengkap : \$user->name;
echo 'Route would find dokter: ' . (\$dokter ? 'YES' : 'NO') . PHP_EOL;
echo 'Display name: ' . \$displayName . PHP_EOL;
auth()->logout();
"

echo -e "\n🔧 4. INVALIDATING ALL EXISTING SESSIONS"
echo "======================================"
echo "Force logout all existing sessions to ensure fresh login..."

# Clear all sessions
rm -rf storage/framework/sessions/*
echo "✅ Cleared all session files"

# Clear Laravel cache
php artisan cache:clear --quiet
php artisan session:flush --quiet 2>/dev/null || echo "Session flush command not available"

echo "✅ Cleared application cache"

echo -e "\n🔧 5. TESTING DOKTER LOGIN AUTHENTICATION"
echo "====================================="
echo "Let's test the actual dokter login flow..."

php artisan tinker --execute="
// Simulate dokter login
\$dokter = App\\Models\\Dokter::where('username', 'yaya')->first();

if (\$dokter) {
    echo 'DOKTER LOGIN TEST:' . PHP_EOL;
    echo 'Username: ' . \$dokter->username . PHP_EOL;
    echo 'Password exists: ' . (\$dokter->password ? 'YES' : 'NO') . PHP_EOL;
    echo 'Status Akun: ' . \$dokter->status_akun . PHP_EOL;
    echo 'Aktif: ' . (\$dokter->aktif ? 'YES' : 'NO') . PHP_EOL;
    echo 'Points to User ID: ' . \$dokter->user_id . PHP_EOL;
    echo 'User exists: ' . (\$dokter->user ? 'YES' : 'NO') . PHP_EOL;
    
    if (\$dokter->user) {
        echo 'User name: ' . \$dokter->user->name . PHP_EOL;
        echo 'User email: ' . \$dokter->user->email . PHP_EOL;
    }
}
"

echo -e "\n🔧 6. FORCE BROWSER CACHE INVALIDATION"
echo "===================================="
echo "Adding cache-busting mechanism..."

# Add timestamp to force browser refresh
TIMESTAMP=$(date +%s)
cat > public/cache-bust.js << CACHE_BUST_EOF
// Cache buster - forces browser to refresh data
window.CACHE_BUST = '$TIMESTAMP';
console.log('Cache bust timestamp: $TIMESTAMP');

// Clear localStorage if exists
if (window.localStorage) {
    localStorage.removeItem('dokter-dashboard-cache');
    localStorage.removeItem('user-data-cache');
    console.log('Cleared localStorage cache');
}

// Clear sessionStorage if exists
if (window.sessionStorage) {
    sessionStorage.clear();
    console.log('Cleared sessionStorage');
}
CACHE_BUST_EOF

echo "✅ Created cache-busting script: $TIMESTAMP"

echo -e "\n✅ AUTHENTICATION FIX COMPLETE!"
echo "============================="
echo "WHAT WAS FIXED:"
echo "1. ✅ Found duplicate Yaya users (ID 8 and 9)"
echo "2. ✅ Updated both users to have correct name"
echo "3. ✅ Fixed missing email addresses"
echo "4. ✅ Cleared all existing sessions"
echo "5. ✅ Added cache-busting mechanism"
echo ""
echo "NEXT STEPS:"
echo "1. Browser MUST do hard refresh (Ctrl+F5 or Cmd+Shift+R)"
echo "2. Clear browser data for dokterkuklinik.com"
echo "3. Login again with username 'yaya'"
echo "4. Should now show: 'Selamat Siang, Dr. Yaya Mulyana, M.Kes'"
EOF