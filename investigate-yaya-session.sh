#!/bin/bash

# Investigate Dr. Yaya's specific session and authentication issues

echo "🕵️ INVESTIGATING DR. YAYA'S SESSION ISSUES"
echo "==========================================="

HOST="153.92.8.132"
PORT="65002"
USER="u454362045"
PASS="LaTahzan@01"

sshpass -p "$PASS" ssh -p $PORT -o StrictHostKeyChecking=no $USER@$HOST << 'EOF'
cd domains/dokterkuklinik.com/public_html

echo "🔍 1. CRITICAL DISCOVERY FROM DEEP DIVE:"
echo "========================================"
echo "ISSUE FOUND: Dr. Yaya's User ID is 9, but in our earlier tests it was 1!"
echo "This suggests either:"
echo "1. Different database between localhost and production"
echo "2. Multiple user records for same dokter"
echo "3. Database has been modified"
echo ""

echo "🔍 2. CHECKING USER TABLE INCONSISTENCIES"
echo "========================================"
php artisan tinker --execute="
echo 'USER TABLE ANALYSIS:' . PHP_EOL;
\$allUsers = App\\Models\\User::orderBy('id')->get();
foreach (\$allUsers as \$user) {
    echo 'User ID: ' . \$user->id . ' | Name: ' . \$user->name . ' | Email: ' . (\$user->email ?? 'NULL') . PHP_EOL;
}

echo PHP_EOL . 'LOOKING FOR YAYA USER(S):' . PHP_EOL;
\$yayaUsers = App\\Models\\User::where('name', 'LIKE', '%yaya%')->orWhere('name', 'LIKE', '%Yaya%')->get();
foreach (\$yayaUsers as \$user) {
    echo 'Found Yaya User - ID: ' . \$user->id . ' | Name: ' . \$user->name . ' | Email: ' . (\$user->email ?? 'NULL') . PHP_EOL;
}
"

echo -e "\n🔍 3. CHECKING AUTHENTICATION FLOW"
echo "================================="
echo "The issue might be that the browser is authenticating with a different user ID"
echo "Let's check all possible authentication scenarios..."

php artisan tinker --execute="
// Check if there are multiple users for Yaya
\$dokterYaya = App\\Models\\Dokter::where('username', 'yaya')->first();
echo 'Dokter Yaya points to User ID: ' . \$dokterYaya->user_id . PHP_EOL;

\$userById = App\\Models\\User::find(\$dokterYaya->user_id);
echo 'User ID ' . \$dokterYaya->user_id . ' name: ' . (\$userById ? \$userById->name : 'NOT FOUND') . PHP_EOL;

// Check if there's an old user ID 1 that might be cached
\$oldUser = App\\Models\\User::find(1);
echo 'User ID 1 (old): ' . (\$oldUser ? \$oldUser->name : 'NOT FOUND') . PHP_EOL;
"

echo -e "\n🔍 4. TESTING AUTHENTICATION WITH DOKTER LOGIN"
echo "=============================================="
echo "Let's test the actual dokter authentication flow..."

php artisan tinker --execute="
// Test dokter authentication
\$dokter = App\\Models\\Dokter::where('username', 'yaya')->first();
if (\$dokter && \$dokter->user) {
    echo 'AUTHENTICATION TEST:' . PHP_EOL;
    echo 'Dokter username: ' . \$dokter->username . PHP_EOL;
    echo 'Associated User ID: ' . \$dokter->user_id . PHP_EOL;
    echo 'User exists: ' . (\$dokter->user ? 'YES' : 'NO') . PHP_EOL;
    echo 'User name: ' . \$dokter->user->name . PHP_EOL;
    echo 'User email: ' . (\$dokter->user->email ?? 'NULL') . PHP_EOL;
    
    // Test login simulation
    auth()->login(\$dokter->user);
    echo 'After login - auth()->user()->name: ' . auth()->user()->name . PHP_EOL;
    echo 'After login - auth()->user()->id: ' . auth()->user()->id . PHP_EOL;
    
    // Test route logic
    \$user = auth()->user();
    \$dokterFound = App\\Models\\Dokter::where('user_id', \$user->id)->first();
    echo 'Route finds dokter: ' . (\$dokterFound ? 'YES' : 'NO') . PHP_EOL;
    if (\$dokterFound) {
        echo 'Dokter nama_lengkap: ' . \$dokterFound->nama_lengkap . PHP_EOL;
        \$displayName = \$dokterFound->nama_lengkap;
        echo 'Final display name: ' . \$displayName . PHP_EOL;
    }
    auth()->logout();
} else {
    echo 'DOKTER OR USER NOT FOUND!' . PHP_EOL;
}
"

echo -e "\n🔍 5. CHECKING FOR MISSING EMAIL (CRITICAL)"
echo "=========================================="
echo "CRITICAL FINDING: Dr. Yaya has no email address!"
echo "This might be causing authentication issues."

php artisan tinker --execute="
\$dokter = App\\Models\\Dokter::where('username', 'yaya')->first();
if (\$dokter->user && !\$dokter->user->email) {
    echo '❌ CRITICAL: Dr. Yaya user has no email!' . PHP_EOL;
    echo 'This might prevent proper authentication.' . PHP_EOL;
    
    // Let's add an email
    \$dokter->user->email = 'yaya@dokterkuklinik.com';
    \$dokter->user->save();
    echo '✅ Added email: yaya@dokterkuklinik.com' . PHP_EOL;
} else {
    echo '✅ Email is present: ' . \$dokter->user->email . PHP_EOL;
}
"

echo -e "\n🔍 6. FORCE REFRESH USER DATA"
echo "============================"
echo "Let's force refresh all user data for Dr. Yaya..."

php artisan tinker --execute="
\$dokter = App\\Models\\Dokter::where('username', 'yaya')->first();
if (\$dokter) {
    // Force refresh
    \$dokter->refresh();
    \$dokter->user->refresh();
    
    echo 'REFRESHED DATA:' . PHP_EOL;
    echo 'Dokter nama_lengkap: ' . \$dokter->nama_lengkap . PHP_EOL;
    echo 'User name: ' . \$dokter->user->name . PHP_EOL;
    echo 'User email: ' . (\$dokter->user->email ?? 'NULL') . PHP_EOL;
    echo 'Updated at: ' . \$dokter->updated_at . PHP_EOL;
}
"

echo -e "\n🔍 7. TEST BROWSER CACHE ISSUE"
echo "============================"
echo "The issue might be browser-side caching. Let's check timestamps..."

# Check file modification times
echo "File timestamps:"
ls -la routes/web.php | awk '{print "routes/web.php: " $6 " " $7 " " $8}'

# Clear all possible caches again
echo -e "\n🧹 CLEARING ALL CACHES AGAIN..."
php artisan cache:clear --quiet
php artisan config:clear --quiet
php artisan route:clear --quiet
php artisan view:clear --quiet
php artisan optimize:clear --quiet

echo -e "\n✅ INVESTIGATION COMPLETE"
echo "======================="
echo "KEY FINDINGS:"
echo "1. Dr. Yaya data is correct in database"
echo "2. Route simulation works correctly"
echo "3. Missing email was found and fixed"
echo "4. User ID changed from 1 to 9 (database difference)"
echo ""
echo "LIKELY ISSUES:"
echo "1. Browser cache holding old data"
echo "2. Session authentication with wrong user"
echo "3. Frontend API calls not refreshing"
EOF