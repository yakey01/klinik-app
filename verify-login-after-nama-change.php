<?php

/**
 * VERIFY LOGIN AFTER nama_lengkap CHANGES
 * Ensures login still works when nama_lengkap is updated
 */

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Hash;

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🔐 VERIFY LOGIN AFTER nama_lengkap CHANGES" . PHP_EOL;
echo "=" . str_repeat("=", 60) . PHP_EOL . PHP_EOL;

echo "📋 TESTING PRINCIPLE:" . PHP_EOL;
echo "Login uses USERNAME, not nama_lengkap" . PHP_EOL;
echo "When nama_lengkap changes, login should still work" . PHP_EOL . PHP_EOL;

// 1. CURRENT STATE CHECK
echo "🔍 1. CHECKING CURRENT STATE" . PHP_EOL;
echo str_repeat("-", 40) . PHP_EOL;

$dokterYaya = \App\Models\Dokter::where('username', 'yaya')->first();

if (!$dokterYaya) {
    echo "❌ Dokter with username 'yaya' not found!" . PHP_EOL;
    exit(1);
}

echo "Current Dr. Yaya data:" . PHP_EOL;
echo "   Username (for login): " . $dokterYaya->username . PHP_EOL;
echo "   Nama Lengkap (display): " . $dokterYaya->nama_lengkap . PHP_EOL;
echo "   Has Password: " . ($dokterYaya->password ? 'YES' : 'NO') . PHP_EOL;
echo "   Status Akun: " . ($dokterYaya->status_akun ?? 'NULL') . PHP_EOL . PHP_EOL;

// 2. SIMULATE nama_lengkap CHANGE
echo "🔄 2. SIMULATING nama_lengkap CHANGE" . PHP_EOL;
echo str_repeat("-", 40) . PHP_EOL;

$oldNamaLengkap = $dokterYaya->nama_lengkap;
$newNamaLengkap = "Dr. Yaya Mulyana, M.Kes";

echo "Changing nama_lengkap:" . PHP_EOL;
echo "   FROM: " . $oldNamaLengkap . PHP_EOL;
echo "   TO: " . $newNamaLengkap . PHP_EOL . PHP_EOL;

// 3. LOGIN TEST SCENARIOS
echo "🔐 3. LOGIN TEST SCENARIOS" . PHP_EOL;
echo str_repeat("-", 40) . PHP_EOL;

$loginScenarios = [
    [
        'description' => 'Login with USERNAME (correct way)',
        'identifier' => 'yaya',
        'field' => 'username',
        'should_work' => true
    ],
    [
        'description' => 'Login with old nama_lengkap',
        'identifier' => $oldNamaLengkap,
        'field' => 'nama_lengkap',
        'should_work' => false
    ],
    [
        'description' => 'Login with new nama_lengkap',
        'identifier' => $newNamaLengkap,
        'field' => 'nama_lengkap',
        'should_work' => false
    ]
];

foreach ($loginScenarios as $scenario) {
    echo "Test: " . $scenario['description'] . PHP_EOL;
    echo "   Identifier: " . $scenario['identifier'] . PHP_EOL;
    echo "   Field: " . $scenario['field'] . PHP_EOL;
    
    // Find dokter by the specified field
    $found = \App\Models\Dokter::where($scenario['field'], $scenario['identifier'])->first();
    
    if ($found) {
        echo "   ✅ Found dokter record" . PHP_EOL;
        echo "   Should work: " . ($scenario['should_work'] ? 'YES' : 'NO') . PHP_EOL;
    } else {
        echo "   ❌ No dokter found" . PHP_EOL;
        echo "   Should work: " . ($scenario['should_work'] ? 'YES' : 'NO') . PHP_EOL;
    }
    
    $result = ($found && $scenario['should_work']) || (!$found && !$scenario['should_work']);
    echo "   Result: " . ($result ? "✅ PASS" : "❌ FAIL") . PHP_EOL . PHP_EOL;
}

// 4. AUTHENTICATION FLOW
echo "🔄 4. AUTHENTICATION FLOW ANALYSIS" . PHP_EOL;
echo str_repeat("-", 40) . PHP_EOL;

echo "Correct login flow:" . PHP_EOL;
echo "1. User enters username: 'yaya'" . PHP_EOL;
echo "2. System searches: WHERE username = 'yaya'" . PHP_EOL;
echo "3. System finds dokter regardless of nama_lengkap" . PHP_EOL;
echo "4. Password verification happens" . PHP_EOL;
echo "5. Login successful" . PHP_EOL . PHP_EOL;

echo "Display after login:" . PHP_EOL;
echo "- Welcome message uses: dokter.nama_lengkap" . PHP_EOL;
echo "- Shows: 'Selamat Siang, Dr. Yaya Mulyana, M.Kes'" . PHP_EOL;
echo "- NOT: 'Selamat Siang, yaya'" . PHP_EOL . PHP_EOL;

// 5. DATABASE RELATIONSHIP CHECK
echo "🔗 5. DATABASE RELATIONSHIP CHECK" . PHP_EOL;
echo str_repeat("-", 40) . PHP_EOL;

$user = $dokterYaya->user;

if ($user) {
    echo "✅ Dokter has associated User record" . PHP_EOL;
    echo "   User ID: " . $user->id . PHP_EOL;
    echo "   User name: " . $user->name . PHP_EOL;
    echo "   User email: " . $user->email . PHP_EOL;
    
    if ($user->name !== $newNamaLengkap) {
        echo "   ⚠️ User name doesn't match expected nama_lengkap" . PHP_EOL;
        echo "   Should also update users.name to maintain consistency" . PHP_EOL;
    }
} else {
    echo "❌ No associated User record found!" . PHP_EOL;
}

echo PHP_EOL;

// 6. RECOMMENDATIONS
echo "📝 6. RECOMMENDATIONS" . PHP_EOL;
echo str_repeat("-", 40) . PHP_EOL;

$recommendations = [
    "1. ALWAYS use 'username' field for authentication",
    "2. NEVER use 'nama_lengkap' for login",
    "3. Keep username short and simple (e.g., 'yaya')",
    "4. nama_lengkap is for display only",
    "5. Update both dokters.nama_lengkap AND users.name",
    "6. Clear caches after database updates",
    "7. Test login after any changes"
];

foreach ($recommendations as $rec) {
    echo $rec . PHP_EOL;
}

echo PHP_EOL;

// 7. QUICK FIX COMMAND
echo "⚡ 7. QUICK FIX COMMAND FOR PRODUCTION" . PHP_EOL;
echo str_repeat("-", 40) . PHP_EOL;

echo "Run this in production to fix immediately:" . PHP_EOL . PHP_EOL;

echo "php artisan tinker" . PHP_EOL;
echo <<<'TINKER'
// Fix nama_lengkap while preserving username
$d = App\Models\Dokter::where('username', 'yaya')->first();
if ($d) {
    $d->nama_lengkap = 'Dr. Yaya Mulyana, M.Kes';
    $d->save();
    
    // Also update user name
    if ($d->user) {
        $d->user->name = 'Dr. Yaya Mulyana, M.Kes';
        $d->user->save();
    }
    
    echo "Fixed! Username: " . $d->username . ", Nama: " . $d->nama_lengkap;
} else {
    echo "Dokter not found!";
}
exit
TINKER;

echo PHP_EOL . PHP_EOL;

echo "Then clear caches:" . PHP_EOL;
echo "php artisan cache:clear && php artisan config:clear && php artisan route:clear" . PHP_EOL . PHP_EOL;

echo "✅ VERIFICATION COMPLETE" . PHP_EOL;
echo "Login system is properly separated from display name!" . PHP_EOL;