<?php
// DEBUG BITA LOGIN ISSUE
echo "🔍 DEEP ANALYSIS: BITA LOGIN FAILURE\n";
echo "====================================\n\n";

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

try {
    echo "📊 DATABASE CONNECTION TEST:\n";
    $connection = DB::connection()->getPdo();
    echo "  ✅ Database connected successfully\n\n";
    
    echo "👤 BITA USER ANALYSIS:\n";
    echo "====================\n";
    
    // Find Bita user
    $bita = User::where('username', 'sabita')->first();
    
    if (!$bita) {
        echo "  ❌ Bita user not found with username 'sabita'\n";
        
        // Search for similar usernames
        $users = User::where('name', 'like', '%bita%')
                    ->orWhere('username', 'like', '%bita%')
                    ->get(['id', 'name', 'username', 'email']);
        
        if ($users->count() > 0) {
            echo "  🔍 Found similar users:\n";
            foreach ($users as $user) {
                echo "    - ID: {$user->id}, Name: {$user->name}, Username: {$user->username}\n";
            }
        }
        exit(1);
    }
    
    echo "  ✅ User found: {$bita->name} (ID: {$bita->id})\n";
    echo "  📧 Email: {$bita->email}\n";
    echo "  👤 Username: {$bita->username}\n";
    echo "  🔑 Password Hash: " . substr($bita->password, 0, 20) . "...\n";
    echo "  ✅ Active: " . ($bita->is_active ? 'YES' : 'NO') . "\n";
    echo "  📅 Email Verified: " . ($bita->email_verified_at ? 'YES' : 'NO') . "\n";
    echo "  🕒 Last Login: " . ($bita->last_login_at ?? 'Never') . "\n";
    echo "  🕒 Created: {$bita->created_at}\n";
    echo "  🕒 Updated: {$bita->updated_at}\n\n";
    
    echo "🎭 ROLE ANALYSIS:\n";
    echo "================\n";
    
    // Check primary role
    if ($bita->role) {
        echo "  ✅ Primary Role: {$bita->role->name} (ID: {$bita->role->id})\n";
        echo "  📝 Role Description: {$bita->role->description}\n";
    } else {
        echo "  ❌ No primary role assigned\n";
    }
    
    // Check multi-roles (if using Spatie)
    if (method_exists($bita, 'roles')) {
        $roles = $bita->roles;
        echo "  📋 Multi-Roles: ";
        if ($roles->count() > 0) {
            foreach ($roles as $role) {
                echo $role->name . " ";
            }
            echo "\n";
        } else {
            echo "None\n";
        }
    }
    
    // Check role permissions
    if (method_exists($bita, 'hasRole')) {
        echo "  🔐 Has Paramedis Role: " . ($bita->hasRole('paramedis') ? 'YES' : 'NO') . "\n";
    }
    
    echo "\n🔒 PASSWORD VERIFICATION TEST:\n";
    echo "=============================\n";
    
    // Test common passwords
    $testPasswords = [
        'password',
        '123456',
        'sabita',
        'bita',
        'Sabita123',
        'paramedis123',
        'dokterku123'
    ];
    
    $passwordFound = false;
    foreach ($testPasswords as $testPassword) {
        if (Hash::check($testPassword, $bita->password)) {
            echo "  ✅ Password found: '{$testPassword}'\n";
            $passwordFound = true;
            break;
        }
    }
    
    if (!$passwordFound) {
        echo "  ❌ None of the common passwords match\n";
        echo "  🔍 Password hash: {$bita->password}\n";
    }
    
    echo "\n📱 FILAMENT PANEL ACCESS:\n";
    echo "========================\n";
    
    // Check if user can access paramedis panel
    if (class_exists('App\Filament\Paramedis\ParamedisPanelProvider')) {
        echo "  ✅ Paramedis panel exists\n";
    } else {
        echo "  ❌ Paramedis panel not found\n";
    }
    
    // Check authentication guard configuration
    echo "\n🛡️ AUTHENTICATION GUARDS:\n";
    echo "=========================\n";
    
    $guards = config('auth.guards');
    foreach ($guards as $guard => $config) {
        echo "  📋 Guard '{$guard}': driver={$config['driver']}, provider={$config['provider']}\n";
    }
    
    echo "\n🔧 SESSION CONFIGURATION:\n";
    echo "========================\n";
    echo "  📂 Driver: " . config('session.driver') . "\n";
    echo "  ⏰ Lifetime: " . config('session.lifetime') . " minutes\n";
    echo "  🍪 Cookie: " . config('session.cookie') . "\n";
    echo "  🌐 Domain: " . (config('session.domain') ?? 'null') . "\n";
    echo "  🔒 Secure: " . (config('session.secure') ? 'YES' : 'NO') . "\n";
    echo "  🛡️ HttpOnly: " . (config('session.http_only') ? 'YES' : 'NO') . "\n";
    
    echo "\n🔍 MIDDLEWARE ANALYSIS:\n";
    echo "======================\n";
    
    // Check for any middleware that might be blocking login
    $middleware = app('router')->getMiddleware();
    echo "  📋 Registered Middleware:\n";
    foreach ($middleware as $name => $class) {
        if (strpos($name, 'auth') !== false || strpos($name, 'csrf') !== false || strpos($name, 'session') !== false) {
            echo "    - {$name}: {$class}\n";
        }
    }
    
    echo "\n🧪 AUTHENTICATION TEST:\n";
    echo "======================\n";
    
    // Test authentication attempt
    try {
        // Clear any existing authentication
        Auth::logout();
        
        // Attempt to authenticate manually
        if ($passwordFound) {
            $credentials = ['username' => 'sabita', 'password' => $testPassword];
            $attemptResult = Auth::attempt($credentials);
            echo "  🔐 Auth attempt result: " . ($attemptResult ? 'SUCCESS' : 'FAILED') . "\n";
            
            if ($attemptResult) {
                $authenticatedUser = Auth::user();
                echo "  ✅ Authenticated as: {$authenticatedUser->name}\n";
                echo "  🎭 Role: " . ($authenticatedUser->role ? $authenticatedUser->role->name : 'No role') . "\n";
            } else {
                echo "  ❌ Authentication failed even with correct password\n";
            }
        } else {
            echo "  ⚠️ Cannot test authentication - password not identified\n";
        }
        
    } catch (Exception $e) {
        echo "  ❌ Authentication test error: " . $e->getMessage() . "\n";
    }
    
    echo "\n🔍 POTENTIAL ISSUES:\n";
    echo "===================\n";
    
    $issues = [];
    
    if (!$bita->is_active) {
        $issues[] = "❌ User account is inactive";
    }
    
    if (!$bita->email_verified_at) {
        $issues[] = "⚠️ Email not verified";
    }
    
    if (!$bita->role) {
        $issues[] = "❌ No primary role assigned";
    }
    
    if (!$passwordFound) {
        $issues[] = "❌ Password not identifiable with common patterns";
    }
    
    if (empty($issues)) {
        echo "  ✅ No obvious issues found\n";
        echo "  💡 Login should work if correct password is used\n";
    } else {
        foreach ($issues as $issue) {
            echo "  {$issue}\n";
        }
    }
    
    echo "\n💡 RECOMMENDATIONS:\n";
    echo "==================\n";
    
    if (!$passwordFound) {
        echo "  1. Reset Bita's password to a known value\n";
        echo "  2. Check if password was changed recently\n";
        echo "  3. Verify password policy requirements\n";
    }
    
    if (!$bita->email_verified_at) {
        echo "  4. Mark email as verified if not required\n";
    }
    
    echo "  5. Check browser console for JavaScript errors\n";
    echo "  6. Verify CSRF token handling\n";
    echo "  7. Check session middleware configuration\n";
    echo "  8. Review Filament panel authentication requirements\n";
    
    echo "\n🎯 NEXT STEPS:\n";
    echo "=============\n";
    echo "  1. Try resetting password: php artisan tinker --execute=\"App\\Models\\User::where('username', 'sabita')->first()->update(['password' => Hash::make('newpassword123')]);\"\n";
    echo "  2. Test login with new password\n";
    echo "  3. Check browser network tab during login attempt\n";
    echo "  4. Review Laravel logs for authentication errors\n";
    
} catch (Exception $e) {
    echo "❌ Error during analysis: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n✅ Analysis completed\n";