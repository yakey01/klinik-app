<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Models\User;
use App\Models\Dokter;
use Illuminate\Support\Facades\Hash;

// Bootstrap Laravel
$app = Application::configure(basePath: __DIR__)
    ->withRouting(
        web: __DIR__.'/routes/web.php',
        api: __DIR__.'/routes/api.php',
        commands: __DIR__.'/routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();

$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔍 DEBUGGING LOGIN ISSUE\n";
echo "========================\n\n";

$identifier = 'bita01';
$password = 'bita123';

echo "🔑 Testing credentials: {$identifier} / {$password}\n\n";

// Check User table first
echo "1. 👤 Checking User table:\n";
echo "==========================\n";

$user = User::where('username', $identifier)
    ->orWhere('email', $identifier)
    ->first();

if ($user) {
    echo "   ✅ User found in User table\n";
    echo "   ID: {$user->id}\n";
    echo "   Name: {$user->name}\n";
    echo "   Username: {$user->username}\n";
    echo "   Email: {$user->email}\n";
    echo "   Password hash: " . substr($user->password, 0, 20) . "...\n";
    
    $passwordCheck = Hash::check($password, $user->password);
    echo "   Password check: " . ($passwordCheck ? "✅ VALID" : "❌ INVALID") . "\n";
    
    if (!$passwordCheck) {
        echo "   🔍 Testing with different password variations:\n";
        $variations = ['bita123', 'Bita123', 'BITA123', 'password', '123456'];
        foreach ($variations as $testPass) {
            $check = Hash::check($testPass, $user->password);
            echo "      {$testPass}: " . ($check ? "✅" : "❌") . "\n";
        }
    }
} else {
    echo "   ❌ User not found in User table\n";
}

echo "\n2. 👨‍⚕️ Checking Dokter table:\n";
echo "=============================\n";

$dokter = Dokter::where('username', $identifier)->first();

if ($dokter) {
    echo "   ✅ Dokter found in Dokter table\n";
    echo "   ID: {$dokter->id}\n";
    echo "   Name: {$dokter->nama_lengkap}\n";
    echo "   Username: {$dokter->username}\n";
    echo "   Status: {$dokter->status_akun}\n";
    echo "   Password hash: " . substr($dokter->password ?? 'NULL', 0, 20) . "...\n";
    
    if ($dokter->password) {
        $passwordCheck = Hash::check($password, $dokter->password);
        echo "   Password check: " . ($passwordCheck ? "✅ VALID" : "❌ INVALID") . "\n";
        
        if (!$passwordCheck) {
            echo "   🔍 Testing with different password variations:\n";
            $variations = ['bita123', 'Bita123', 'BITA123', 'password', '123456'];
            foreach ($variations as $testPass) {
                $check = Hash::check($testPass, $dokter->password);
                echo "      {$testPass}: " . ($check ? "✅" : "❌") . "\n";
            }
        }
    } else {
        echo "   ⚠️  No password set for this dokter\n";
    }
    
    if ($dokter->user_id) {
        echo "   🔗 Associated User ID: {$dokter->user_id}\n";
        $associatedUser = User::find($dokter->user_id);
        if ($associatedUser) {
            echo "   ✅ Associated User found: {$associatedUser->name}\n";
        } else {
            echo "   ❌ Associated User not found\n";
        }
    }
} else {
    echo "   ❌ Dokter not found in Dokter table\n";
}

echo "\n3. 🔍 Checking Paramedis table:\n";
echo "===============================\n";

$paramedis = \App\Models\Pegawai::where('username', $identifier)
    ->where('jenis_pegawai', 'Paramedis')
    ->first();

if ($paramedis) {
    echo "   ✅ Paramedis found\n";
    echo "   ID: {$paramedis->id}\n";
    echo "   Name: {$paramedis->nama_lengkap}\n";
    echo "   Username: {$paramedis->username}\n";
    echo "   Password hash: " . substr($paramedis->password ?? 'NULL', 0, 20) . "...\n";
    
    if ($paramedis->password) {
        $passwordCheck = Hash::check($password, $paramedis->password);
        echo "   Password check: " . ($passwordCheck ? "✅ VALID" : "❌ INVALID") . "\n";
    } else {
        echo "   ⚠️  No password set for this paramedis\n";
    }
    
    if ($paramedis->user_id) {
        echo "   🔗 Associated User ID: {$paramedis->user_id}\n";
        $associatedUser = User::find($paramedis->user_id);
        if ($associatedUser) {
            echo "   ✅ Associated User found: {$associatedUser->name}\n";
        } else {
            echo "   ❌ Associated User not found\n";
        }
    }
} else {
    echo "   ❌ Paramedis not found\n";
}

echo "\n4. 🧪 Testing User::findForAuth method:\n";
echo "======================================\n";

try {
    $authUser = User::findForAuth($identifier);
    if ($authUser) {
        echo "   ✅ findForAuth found user: {$authUser->name} (ID: {$authUser->id})\n";
        $passwordCheck = Hash::check($password, $authUser->password);
        echo "   Password check: " . ($passwordCheck ? "✅ VALID" : "❌ INVALID") . "\n";
    } else {
        echo "   ❌ findForAuth returned null\n";
    }
} catch (Exception $e) {
    echo "   ❌ Error in findForAuth: " . $e->getMessage() . "\n";
}

echo "\n📋 SUMMARY:\n";
echo "===========\n";
if ($user && Hash::check($password, $user->password)) {
    echo "✅ Login should work with User table credentials\n";
} elseif ($dokter && $dokter->password && Hash::check($password, $dokter->password)) {
    echo "✅ Login should work with Dokter table credentials\n";
} elseif ($paramedis && $paramedis->password && Hash::check($password, $paramedis->password)) {
    echo "✅ Login should work with Paramedis table credentials\n";
} else {
    echo "❌ Login will fail - no valid credentials found\n";
    echo "💡 Possible issues:\n";
    echo "   - Wrong password\n";
    echo "   - User not found in any table\n";
    echo "   - Password not properly hashed\n";
    echo "   - Account disabled/inactive\n";
}

echo "\n✅ Debug complete\n";