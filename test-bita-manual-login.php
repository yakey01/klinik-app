<?php
// MANUAL BITA LOGIN TEST
echo "🧪 MANUAL BITA LOGIN TEST\n";
echo "========================\n\n";

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

try {
    echo "🔐 TESTING BITA LOGIN PROCESS\n";
    echo "=============================\n\n";
    
    // 1. Find Bita user
    $bita = User::where('username', 'sabita')->first();
    
    if (!$bita) {
        echo "❌ Bita user not found\n";
        exit(1);
    }
    
    echo "✅ Found user: {$bita->name} (sabita)\n";
    echo "📧 Email: {$bita->email}\n";
    echo "🎭 Role: " . ($bita->role ? $bita->role->name : 'No role') . "\n\n";
    
    // 2. Test password verification
    $testPassword = 'bita123';
    $passwordVerification = Hash::check($testPassword, $bita->password);
    
    echo "🔑 PASSWORD VERIFICATION:\n";
    echo "  Testing password: '{$testPassword}'\n";
    echo "  Result: " . ($passwordVerification ? '✅ CORRECT' : '❌ INCORRECT') . "\n\n";
    
    if (!$passwordVerification) {
        echo "❌ Cannot proceed with login test - password verification failed\n";
        exit(1);
    }
    
    // 3. Manual authentication attempt
    echo "🚀 MANUAL AUTHENTICATION ATTEMPT:\n";
    
    // Clear any existing authentication
    Auth::logout();
    
    // Attempt login with credentials
    $credentials = [
        'username' => 'sabita',
        'password' => $testPassword
    ];
    
    $loginAttempt = Auth::attempt($credentials);
    
    echo "  Credentials: " . json_encode($credentials) . "\n";
    echo "  Auth::attempt() result: " . ($loginAttempt ? '✅ SUCCESS' : '❌ FAILED') . "\n";
    
    if ($loginAttempt) {
        $authenticatedUser = Auth::user();
        echo "  ✅ Authenticated as: {$authenticatedUser->name}\n";
        echo "  🎭 Role: " . ($authenticatedUser->role ? $authenticatedUser->role->name : 'No role') . "\n";
        echo "  🆔 User ID: {$authenticatedUser->id}\n";
        
        // 4. Test role-based access
        echo "\n🎭 ROLE-BASED ACCESS TEST:\n";
        
        if (method_exists($authenticatedUser, 'hasRole')) {
            $hasParamedisRole = $authenticatedUser->hasRole('paramedis');
            echo "  Has 'paramedis' role: " . ($hasParamedisRole ? '✅ YES' : '❌ NO') . "\n";
            
            if ($hasParamedisRole) {
                echo "  ✅ Should have access to paramedis panel\n";
            } else {
                echo "  ❌ Would be denied access to paramedis panel\n";
            }
        } else {
            echo "  ⚠️ hasRole method not available\n";
        }
        
        // 5. Test middleware simulation
        echo "\n🛡️ MIDDLEWARE SIMULATION:\n";
        
        // Simulate ParamedisMiddleware logic
        try {
            if ($authenticatedUser && $authenticatedUser->hasRole('paramedis')) {
                echo "  ✅ ParamedisMiddleware: PASS\n";
                echo "  ✅ User would be allowed to access paramedis panel\n";
            } else {
                echo "  ❌ ParamedisMiddleware: FAIL (no paramedis role)\n";
                echo "  🔄 Would redirect to: " . ($authenticatedUser->role ? "role-specific dashboard" : "home") . "\n";
            }
        } catch (Exception $e) {
            echo "  ❌ ParamedisMiddleware: ERROR - " . $e->getMessage() . "\n";
        }
        
        // 6. Test URL access paths
        echo "\n🌐 URL ACCESS TEST:\n";
        echo "  Login URL: http://localhost:8000/login\n";
        echo "  Paramedis Panel: http://localhost:8000/paramedis\n";
        echo "  After login should redirect to: http://localhost:8000/paramedis\n";
        
    } else {
        echo "  ❌ Authentication failed\n";
        
        // Debug why authentication failed
        echo "\n🔍 AUTHENTICATION FAILURE DEBUG:\n";
        
        // Check if user exists with these credentials
        $userByUsername = User::where('username', $credentials['username'])->first();
        $userByEmail = User::where('email', $credentials['username'])->first();
        
        echo "  User by username: " . ($userByUsername ? "✅ Found" : "❌ Not found") . "\n";
        echo "  User by email: " . ($userByEmail ? "✅ Found" : "❌ Not found") . "\n";
        
        if ($userByUsername) {
            $passwordMatch = Hash::check($credentials['password'], $userByUsername->password);
            echo "  Password match: " . ($passwordMatch ? "✅ YES" : "❌ NO") . "\n";
            echo "  User active: " . ($userByUsername->is_active ? "✅ YES" : "❌ NO") . "\n";
        }
    }
    
    // 7. Generate login instructions
    echo "\n📋 LOGIN INSTRUCTIONS FOR BITA:\n";
    echo "===============================\n";
    echo "1. Go to: http://localhost:8000/login\n";
    echo "2. Enter username: sabita\n";
    echo "3. Enter password: {$testPassword}\n";
    echo "4. Click 'Login'\n";
    echo "5. Should redirect to: http://localhost:8000/paramedis\n\n";
    
    // 8. Potential issues and solutions
    echo "🚨 POTENTIAL ISSUES & SOLUTIONS:\n";
    echo "================================\n";
    
    if (!$loginAttempt) {
        echo "❌ ISSUE: Authentication failed\n";
        echo "   💡 SOLUTION: Check auth configuration, user provider, password hashing\n\n";
    }
    
    if ($loginAttempt && !$authenticatedUser->hasRole('paramedis')) {
        echo "❌ ISSUE: User doesn't have paramedis role\n";
        echo "   💡 SOLUTION: Assign paramedis role to user\n\n";
    }
    
    echo "🔍 COMMON ISSUES:\n";
    echo "  1. CSRF token mismatch (check RefreshCsrfToken middleware)\n";
    echo "  2. Session middleware conflicts\n";
    echo "  3. Browser cache/cookies issues\n";
    echo "  4. JavaScript errors preventing form submission\n";
    echo "  5. Validation errors not displayed\n\n";
    
    echo "🛠️ DEBUGGING STEPS:\n";
    echo "  1. Check browser console for errors\n";
    echo "  2. Check browser network tab for failed requests\n";
    echo "  3. Check Laravel logs: storage/logs/laravel.log\n";
    echo "  4. Try incognito/private browsing mode\n";
    echo "  5. Clear browser cache and cookies\n";
    
    echo "\n✅ Test completed\n";
    
} catch (Exception $e) {
    echo "❌ Error during test: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}