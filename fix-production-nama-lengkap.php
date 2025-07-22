<?php

/**
 * FIX PRODUCTION DATABASE - nama_lengkap Issue
 * Ensures username stays the same for login while updating nama_lengkap
 */

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;
use Carbon\Carbon;

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🔧 FIX PRODUCTION DATABASE - nama_lengkap Issue" . PHP_EOL;
echo "=" . str_repeat("=", 60) . PHP_EOL;
echo "Date: " . Carbon::now()->format('Y-m-d H:i:s') . PHP_EOL;
echo "Environment: " . config('app.env') . PHP_EOL . PHP_EOL;

echo "📋 IMPORTANT PRINCIPLES:" . PHP_EOL;
echo "✅ Username MUST stay unchanged (for login)" . PHP_EOL;
echo "✅ Only nama_lengkap should be updated" . PHP_EOL;
echo "✅ User table 'name' should also be updated" . PHP_EOL;
echo "✅ Login must still work after changes" . PHP_EOL . PHP_EOL;

// 1. CHECK CURRENT STATE
echo "🔍 1. CHECKING CURRENT DATABASE STATE" . PHP_EOL;
echo str_repeat("-", 40) . PHP_EOL;

// Find all dokters to understand the pattern
$allDokters = \App\Models\Dokter::select('id', 'username', 'nama_lengkap', 'user_id')
    ->orderBy('id')
    ->get();

echo "Current Dokters in database:" . PHP_EOL;
foreach ($allDokters as $dokter) {
    $user = $dokter->user;
    echo "ID: {$dokter->id} | Username: " . ($dokter->username ?? 'NULL') . 
         " | Nama: {$dokter->nama_lengkap}" . 
         " | User.name: " . ($user ? $user->name : 'NO USER') . PHP_EOL;
}

echo PHP_EOL;

// 2. FIND DR. YAYA SPECIFICALLY
echo "🎯 2. ANALYZING DR. YAYA DATA" . PHP_EOL;
echo str_repeat("-", 40) . PHP_EOL;

$dokterYaya = \App\Models\Dokter::where('username', 'yaya')->first();

if (!$dokterYaya) {
    echo "❌ ERROR: Dokter with username 'yaya' not found!" . PHP_EOL;
    
    // Try to find by name pattern
    $possibleYaya = \App\Models\Dokter::where('nama_lengkap', 'LIKE', '%yaya%')
        ->orWhere('nama_lengkap', 'LIKE', '%Yaya%')
        ->first();
    
    if ($possibleYaya) {
        echo "Found possible match: " . $possibleYaya->nama_lengkap . PHP_EOL;
        $dokterYaya = $possibleYaya;
    } else {
        exit(1);
    }
}

echo "✅ Found Dr. Yaya:" . PHP_EOL;
echo "   Dokter ID: " . $dokterYaya->id . PHP_EOL;
echo "   Username: " . ($dokterYaya->username ?? 'NULL') . PHP_EOL;
echo "   Current nama_lengkap: " . $dokterYaya->nama_lengkap . PHP_EOL;
echo "   User ID: " . $dokterYaya->user_id . PHP_EOL;

$userYaya = $dokterYaya->user;
if ($userYaya) {
    echo "   User.name: " . $userYaya->name . PHP_EOL;
    echo "   User.email: " . $userYaya->email . PHP_EOL;
} else {
    echo "   ❌ NO ASSOCIATED USER!" . PHP_EOL;
}

echo PHP_EOL;

// 3. DETECT ISSUES
echo "🚨 3. ISSUE DETECTION" . PHP_EOL;
echo str_repeat("-", 40) . PHP_EOL;

$expectedNamaLengkap = "Dr. Yaya Mulyana, M.Kes";
$issues = [];

// Check dokter nama_lengkap
if ($dokterYaya->nama_lengkap !== $expectedNamaLengkap) {
    $issues[] = "Dokter nama_lengkap is '{$dokterYaya->nama_lengkap}' instead of '{$expectedNamaLengkap}'";
}

// Check user name
if ($userYaya && $userYaya->name !== $expectedNamaLengkap) {
    $issues[] = "User name is '{$userYaya->name}' instead of '{$expectedNamaLengkap}'";
}

// Check if username is problematic
if ($dokterYaya->username === $dokterYaya->nama_lengkap) {
    $issues[] = "Username incorrectly set to nama_lengkap (should be 'yaya')";
}

if (empty($issues)) {
    echo "✅ No issues found! Data appears correct." . PHP_EOL;
} else {
    echo "❌ Issues found:" . PHP_EOL;
    foreach ($issues as $issue) {
        echo "   - " . $issue . PHP_EOL;
    }
}

echo PHP_EOL;

// 4. GENERATE FIX COMMANDS
echo "🔧 4. FIX COMMANDS FOR PRODUCTION" . PHP_EOL;
echo str_repeat("-", 40) . PHP_EOL;

echo "📝 SQL Commands to fix the issue:" . PHP_EOL . PHP_EOL;

// Fix dokter table
echo "-- Fix dokter nama_lengkap (preserving username for login)" . PHP_EOL;
echo "UPDATE dokters " . PHP_EOL;
echo "SET nama_lengkap = '{$expectedNamaLengkap}' " . PHP_EOL;
echo "WHERE id = {$dokterYaya->id};" . PHP_EOL . PHP_EOL;

// Fix user table
if ($userYaya) {
    echo "-- Fix associated user name" . PHP_EOL;
    echo "UPDATE users " . PHP_EOL;
    echo "SET name = '{$expectedNamaLengkap}' " . PHP_EOL;
    echo "WHERE id = {$userYaya->id};" . PHP_EOL . PHP_EOL;
}

// Ensure username is correct (if needed)
if ($dokterYaya->username !== 'yaya') {
    echo "-- Fix username (ONLY if it was incorrectly changed)" . PHP_EOL;
    echo "UPDATE dokters " . PHP_EOL;
    echo "SET username = 'yaya' " . PHP_EOL;
    echo "WHERE id = {$dokterYaya->id};" . PHP_EOL . PHP_EOL;
}

echo "📋 Laravel Artisan Commands:" . PHP_EOL . PHP_EOL;

$artisanCommands = <<<'PHP'
php artisan tinker
// Update dokter nama_lengkap
$dokter = App\Models\Dokter::where('username', 'yaya')->first();
$dokter->nama_lengkap = 'Dr. Yaya Mulyana, M.Kes';
$dokter->save();

// Update user name
$user = $dokter->user;
$user->name = 'Dr. Yaya Mulyana, M.Kes';
$user->save();

// Verify changes
echo "Dokter nama: " . $dokter->nama_lengkap;
echo "User name: " . $user->name;
echo "Username (for login): " . $dokter->username;
exit
PHP;

echo $artisanCommands . PHP_EOL . PHP_EOL;

// 5. VERIFICATION STEPS
echo "✅ 5. VERIFICATION STEPS AFTER FIX" . PHP_EOL;
echo str_repeat("-", 40) . PHP_EOL;

$verificationSteps = [
    "1. Run the SQL or Artisan commands above",
    "2. Clear all caches:",
    "   - php artisan cache:clear",
    "   - php artisan config:clear",
    "   - php artisan route:clear",
    "3. Test login with username 'yaya'",
    "4. Check dashboard welcome message",
    "5. Verify API returns correct nama_lengkap"
];

foreach ($verificationSteps as $step) {
    echo $step . PHP_EOL;
}

echo PHP_EOL;

// 6. LOGIN TEST
echo "🔐 6. LOGIN VERIFICATION" . PHP_EOL;
echo str_repeat("-", 40) . PHP_EOL;

echo "After fixing, login should work with:" . PHP_EOL;
echo "   Username: yaya" . PHP_EOL;
echo "   Password: [unchanged]" . PHP_EOL . PHP_EOL;

echo "Dashboard should show:" . PHP_EOL;
echo "   Welcome: Selamat Siang, Dr. Yaya Mulyana, M.Kes" . PHP_EOL;
echo "   NOT: Selamat Siang, yaya" . PHP_EOL . PHP_EOL;

// 7. IMPORTANT NOTES
echo "⚠️ 7. IMPORTANT NOTES" . PHP_EOL;
echo str_repeat("-", 40) . PHP_EOL;

echo "1. NEVER change username - it's used for login" . PHP_EOL;
echo "2. nama_lengkap is for display purposes only" . PHP_EOL;
echo "3. Always update both dokters.nama_lengkap AND users.name" . PHP_EOL;
echo "4. Clear caches after database changes" . PHP_EOL;
echo "5. Test login after any changes" . PHP_EOL . PHP_EOL;

echo "✅ FIX SCRIPT COMPLETE" . PHP_EOL;
echo "Ready to fix production database!" . PHP_EOL;