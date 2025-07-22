#!/bin/bash

# DEEP DIVE: Why Rindang works but Yaya doesn't

echo "🔍 DEEP DIVE ANALYSIS - Dr. Yaya vs Dr. Rindang"
echo "================================================"

HOST="153.92.8.132"
PORT="65002"
USER="u454362045"
PASS="LaTahzan@01"

# Comprehensive comparison script
sshpass -p "$PASS" ssh -p $PORT -o StrictHostKeyChecking=no $USER@$HOST << 'EOF'
cd domains/dokterkuklinik.com/public_html

echo "🔍 1. COMPARING ALL DOKTER RECORDS"
echo "=================================="
php artisan tinker --execute="
\$dokters = App\\Models\\Dokter::with('user')->get();
echo 'Total Dokters: ' . \$dokters->count() . PHP_EOL;
foreach (\$dokters as \$d) {
    echo '---' . PHP_EOL;
    echo 'ID: ' . \$d->id . PHP_EOL;
    echo 'Username: ' . (\$d->username ?? 'NULL') . PHP_EOL;
    echo 'Nama Lengkap: ' . \$d->nama_lengkap . PHP_EOL;
    echo 'User ID: ' . \$d->user_id . PHP_EOL;
    echo 'User Name: ' . (\$d->user ? \$d->user->name : 'NO USER') . PHP_EOL;
    echo 'Status Aktif: ' . (\$d->aktif ? 'YES' : 'NO') . PHP_EOL;
    echo 'Status Akun: ' . (\$d->status_akun ?? 'NULL') . PHP_EOL;
    echo 'Has Password: ' . (\$d->password ? 'YES' : 'NO') . PHP_EOL;
}
"

echo -e "\n🔍 2. SPECIFIC ANALYSIS - DR. YAYA"
echo "================================="
php artisan tinker --execute="
\$yaya = App\\Models\\Dokter::where('username', 'yaya')->first();
if (\$yaya) {
    echo 'YAYA ANALYSIS:' . PHP_EOL;
    echo 'Dokter ID: ' . \$yaya->id . PHP_EOL;
    echo 'Username: ' . \$yaya->username . PHP_EOL;
    echo 'Nama Lengkap: ' . \$yaya->nama_lengkap . PHP_EOL;
    echo 'User ID: ' . \$yaya->user_id . PHP_EOL;
    if (\$yaya->user) {
        echo 'User Name: ' . \$yaya->user->name . PHP_EOL;
        echo 'User Email: ' . \$yaya->user->email . PHP_EOL;
        echo 'User Updated: ' . \$yaya->user->updated_at . PHP_EOL;
    }
    echo 'Dokter Updated: ' . \$yaya->updated_at . PHP_EOL;
    echo 'Status Aktif: ' . (\$yaya->aktif ? 'YES' : 'NO') . PHP_EOL;
    echo 'Status Akun: ' . (\$yaya->status_akun ?? 'NULL') . PHP_EOL;
} else {
    echo 'YAYA NOT FOUND!' . PHP_EOL;
}
"

echo -e "\n🔍 3. SPECIFIC ANALYSIS - DR. RINDANG"
echo "===================================="
php artisan tinker --execute="
\$rindang = App\\Models\\Dokter::where('nama_lengkap', 'LIKE', '%rindang%')->first();
if (!\$rindang) {
    \$rindang = App\\Models\\Dokter::where('nama_lengkap', 'LIKE', '%Rindang%')->first();
}
if (\$rindang) {
    echo 'RINDANG ANALYSIS:' . PHP_EOL;
    echo 'Dokter ID: ' . \$rindang->id . PHP_EOL;
    echo 'Username: ' . (\$rindang->username ?? 'NULL') . PHP_EOL;
    echo 'Nama Lengkap: ' . \$rindang->nama_lengkap . PHP_EOL;
    echo 'User ID: ' . \$rindang->user_id . PHP_EOL;
    if (\$rindang->user) {
        echo 'User Name: ' . \$rindang->user->name . PHP_EOL;
        echo 'User Email: ' . \$rindang->user->email . PHP_EOL;
        echo 'User Updated: ' . \$rindang->user->updated_at . PHP_EOL;
    }
    echo 'Dokter Updated: ' . \$rindang->updated_at . PHP_EOL;
    echo 'Status Aktif: ' . (\$rindang->aktif ? 'YES' : 'NO') . PHP_EOL;
    echo 'Status Akun: ' . (\$rindang->status_akun ?? 'NULL') . PHP_EOL;
} else {
    echo 'RINDANG NOT FOUND!' . PHP_EOL;
}
"

echo -e "\n🔍 4. TESTING API FOR BOTH USERS"
echo "==============================="
echo "Testing mobile-app route simulation for both users..."

php artisan tinker --execute="
// Test Yaya route
\$yayaUser = App\\Models\\User::whereHas('dokter', function(\$q) {
    \$q->where('username', 'yaya');
})->first();

if (\$yayaUser) {
    \$dokter = App\\Models\\Dokter::where('user_id', \$yayaUser->id)->first();
    \$displayName = \$dokter ? \$dokter->nama_lengkap : \$yayaUser->name;
    echo 'YAYA ROUTE TEST:' . PHP_EOL;
    echo 'User Name: ' . \$yayaUser->name . PHP_EOL;
    echo 'Dokter Nama Lengkap: ' . (\$dokter ? \$dokter->nama_lengkap : 'NO DOKTER') . PHP_EOL;
    echo 'Display Name (final): ' . \$displayName . PHP_EOL;
} else {
    echo 'YAYA USER NOT FOUND!' . PHP_EOL;
}

echo PHP_EOL;

// Test Rindang route
\$rindangUser = App\\Models\\User::whereHas('dokter', function(\$q) {
    \$q->where('nama_lengkap', 'LIKE', '%indang%');
})->first();

if (\$rindangUser) {
    \$dokter = App\\Models\\Dokter::where('user_id', \$rindangUser->id)->first();
    \$displayName = \$dokter ? \$dokter->nama_lengkap : \$rindangUser->name;
    echo 'RINDANG ROUTE TEST:' . PHP_EOL;
    echo 'User Name: ' . \$rindangUser->name . PHP_EOL;
    echo 'Dokter Nama Lengkap: ' . (\$dokter ? \$dokter->nama_lengkap : 'NO DOKTER') . PHP_EOL;
    echo 'Display Name (final): ' . \$displayName . PHP_EOL;
} else {
    echo 'RINDANG USER NOT FOUND!' . PHP_EOL;
}
"

echo -e "\n🔍 5. CHECKING SESSION AND CACHE"
echo "==============================="
echo "Checking for any cached sessions or tokens..."

# Check for any session files or cached data
ls -la storage/framework/sessions/ | head -5
echo "Session files count: $(ls storage/framework/sessions/ | wc -l)"

# Check Laravel cache
php artisan cache:clear --quiet
echo "✅ Cache cleared"

echo -e "\n🔍 6. TESTING DASHBOARD API SIMULATION"
echo "====================================="
php artisan tinker --execute="
// Simulate dashboard API for Yaya
\$yayaUser = App\\Models\\User::whereHas('dokter', function(\$q) {
    \$q->where('username', 'yaya');
})->first();

if (\$yayaUser) {
    // Simulate authentication
    auth()->login(\$yayaUser);
    
    echo 'YAYA API SIMULATION:' . PHP_EOL;
    echo 'Authenticated as: ' . auth()->user()->name . PHP_EOL;
    
    \$dokter = App\\Models\\Dokter::where('user_id', \$yayaUser->id)->first();
    echo 'Found Dokter: ' . (\$dokter ? 'YES' : 'NO') . PHP_EOL;
    
    if (\$dokter) {
        echo 'API would return:' . PHP_EOL;
        echo '  user.name: ' . \$yayaUser->name . PHP_EOL;
        echo '  dokter.nama_lengkap: ' . \$dokter->nama_lengkap . PHP_EOL;
    }
    
    auth()->logout();
}
"

echo -e "\n🔍 7. BROWSER SIMULATION TEST"
echo "============================"
echo "Testing what browser would receive..."

# Simulate the exact route that browser hits
php -r "
\$_SERVER['REQUEST_METHOD'] = 'GET';
\$_SERVER['REQUEST_URI'] = '/dokter/mobile-app';
include 'bootstrap/app.php';

// Simulate auth for Yaya
\$yayaUser = App\\Models\\User::whereHas('dokter', function(\$q) {
    \$q->where('username', 'yaya');
})->first();

if (\$yayaUser) {
    auth()->login(\$yayaUser);
    
    // Simulate the route logic
    \$user = auth()->user();
    \$hour = now()->hour;
    \$greeting = \$hour < 12 ? 'Selamat Pagi' : (\$hour < 17 ? 'Selamat Siang' : 'Selamat Malam');
    
    // Get dokter data for more accurate name
    \$dokter = App\\Models\\Dokter::where('user_id', \$user->id)->first();
    \$displayName = \$dokter ? \$dokter->nama_lengkap : \$user->name;
    
    \$userData = [
        'name' => \$displayName,
        'email' => \$user->email,
        'greeting' => \$greeting,
        'initials' => strtoupper(substr(\$displayName ?? 'DA', 0, 2))
    ];
    
    echo 'BROWSER WOULD RECEIVE:' . PHP_EOL;
    echo 'userData.name: ' . \$userData['name'] . PHP_EOL;
    echo 'userData.greeting: ' . \$userData['greeting'] . PHP_EOL;
    echo 'userData.initials: ' . \$userData['initials'] . PHP_EOL;
} else {
    echo 'Could not simulate for Yaya user';
}
"

echo -e "\n✅ DEEP DIVE ANALYSIS COMPLETE"
echo "=============================="
EOF