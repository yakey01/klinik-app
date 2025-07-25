<?php

/**
 * Hostinger Production Authentication Debug Script
 * 
 * This script performs comprehensive authentication debugging
 * for the persistent login issue on Hostinger production environment.
 */

require_once __DIR__ . '/vendor/autoload.php';

// Load Laravel application
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;

echo "=== HOSTINGER PRODUCTION AUTHENTICATION DEBUG ===\n\n";

// Test 1: Database Connection
echo "1. Testing Database Connection...\n";
try {
    $connection = DB::connection()->getPdo();
    echo "✓ Database connection successful\n";
    echo "   Driver: " . DB::connection()->getDriverName() . "\n";
    echo "   Database: " . DB::connection()->getDatabaseName() . "\n\n";
} catch (Exception $e) {
    echo "✗ Database connection failed: " . $e->getMessage() . "\n\n";
    exit(1);
}

// Test 2: Check Admin User in Database
echo "2. Checking Admin User in Database...\n";
try {
    $adminUser = User::where('email', 'admin@dokterkuklinik.com')->first();
    
    if ($adminUser) {
        echo "✓ Admin user found in database\n";
        echo "   ID: " . $adminUser->id . "\n";
        echo "   Email: " . $adminUser->email . "\n";
        echo "   Username: " . ($adminUser->username ?? 'NULL') . "\n";
        echo "   Role ID: " . ($adminUser->role_id ?? 'NULL') . "\n";
        echo "   Is Active: " . ($adminUser->is_active ? 'YES' : 'NO') . "\n";
        echo "   Email Verified: " . ($adminUser->email_verified_at ? 'YES' : 'NO') . "\n";
        echo "   Password Hash Length: " . strlen($adminUser->password) . "\n";
        echo "   Password Hash: " . substr($adminUser->password, 0, 20) . "...\n";
        
        // Check if password is hashed properly
        if (Hash::needsRehash($adminUser->password)) {
            echo "   ⚠️  Password needs rehash (not using current hash method)\n";
        } else {
            echo "   ✓ Password hash format is correct\n";
        }
        
        // Test password verification
        $testPassword = 'admin123';
        if (Hash::check($testPassword, $adminUser->password)) {
            echo "   ✓ Password 'admin123' verification successful\n";
        } else {
            echo "   ✗ Password 'admin123' verification failed\n";
        }
        
    } else {
        echo "✗ Admin user NOT found in database\n";
    }
    echo "\n";
} catch (Exception $e) {
    echo "✗ Error checking admin user: " . $e->getMessage() . "\n\n";
}

// Test 3: Check Role and Permissions
echo "3. Checking Role and Permissions...\n";
try {
    if (isset($adminUser) && $adminUser) {
        // Check role relationship
        $role = $adminUser->role;
        if ($role) {
            echo "   ✓ Role found: " . $role->name . " (ID: " . $role->id . ")\n";
        } else {
            echo "   ✗ No role assigned to admin user\n";
        }
        
        // Check Spatie roles
        $spatieRoles = $adminUser->roles()->get();
        if ($spatieRoles->count() > 0) {
            echo "   ✓ Spatie roles: " . $spatieRoles->pluck('name')->implode(', ') . "\n";
        } else {
            echo "   ⚠️  No Spatie roles assigned\n";
        }
        
        // Test hasRole method
        if ($adminUser->hasRole('admin')) {
            echo "   ✓ hasRole('admin') returns true\n";
        } else {
            echo "   ✗ hasRole('admin') returns false\n";
        }
    }
    echo "\n";
} catch (Exception $e) {
    echo "   ✗ Error checking roles: " . $e->getMessage() . "\n\n";
}

// Test 4: Authentication Configuration
echo "4. Checking Authentication Configuration...\n";
echo "   Default Guard: " . Config::get('auth.defaults.guard') . "\n";
echo "   User Provider: " . Config::get('auth.guards.web.provider') . "\n";
echo "   Provider Driver: " . Config::get('auth.providers.users.driver') . "\n";
echo "   User Model: " . Config::get('auth.providers.users.model') . "\n";
echo "   Hash Driver: " . Config::get('hashing.driver') . "\n\n";

// Test 5: Session Configuration
echo "5. Checking Session Configuration...\n";
echo "   Session Driver: " . Config::get('session.driver') . "\n";
echo "   Session Lifetime: " . Config::get('session.lifetime') . " minutes\n";
echo "   Session Encrypt: " . (Config::get('session.encrypt') ? 'YES' : 'NO') . "\n";
echo "   Session Cookie: " . Config::get('session.cookie') . "\n";
echo "   Session Domain: " . (Config::get('session.domain') ?? 'NULL') . "\n";
echo "   Session Secure: " . (Config::get('session.secure') ? 'YES' : 'NO') . "\n";
echo "   Session Same Site: " . Config::get('session.same_site') . "\n\n";

// Test 6: Check Sessions Table
echo "6. Checking Sessions Table...\n";
try {
    $sessionTable = Config::get('session.table', 'sessions');
    $sessionsExist = DB::getSchemaBuilder()->hasTable($sessionTable);
    
    if ($sessionsExist) {
        echo "   ✓ Sessions table '{$sessionTable}' exists\n";
        
        $sessionCount = DB::table($sessionTable)->count();
        echo "   Sessions count: " . $sessionCount . "\n";
        
        // Check recent sessions
        $recentSessions = DB::table($sessionTable)
            ->where('last_activity', '>', time() - 86400) // Last 24 hours
            ->count();
        echo "   Recent sessions (24h): " . $recentSessions . "\n";
        
    } else {
        echo "   ✗ Sessions table '{$sessionTable}' does NOT exist\n";
        echo "   💡 Run: php artisan session:table && php artisan migrate\n";
    }
    echo "\n";
} catch (Exception $e) {
    echo "   ✗ Error checking sessions table: " . $e->getMessage() . "\n\n";
}

// Test 7: Environment Variables
echo "7. Checking Critical Environment Variables...\n";
$criticalEnvVars = [
    'APP_ENV', 'APP_DEBUG', 'APP_KEY', 'APP_URL',
    'DB_CONNECTION', 'DB_HOST', 'DB_DATABASE', 'DB_USERNAME',
    'SESSION_DRIVER', 'SESSION_LIFETIME', 'SESSION_ENCRYPT',
    'AUTH_GUARD', 'AUTH_PASSWORD_BROKER'
];

foreach ($criticalEnvVars as $var) {
    $value = env($var);
    if ($value !== null) {
        // Hide sensitive values
        if (in_array($var, ['APP_KEY', 'DB_PASSWORD', 'DB_USERNAME'])) {
            echo "   {$var}: " . str_repeat('*', min(strlen($value), 8)) . "\n";
        } else {
            echo "   {$var}: {$value}\n";
        }
    } else {
        echo "   {$var}: NOT SET\n";
    }
}
echo "\n";

// Test 8: Test Custom Authentication Provider
echo "8. Testing Custom Authentication Provider...\n";
try {
    $authProvider = Auth::getProvider();
    echo "   Provider Class: " . get_class($authProvider) . "\n";
    
    // Test retrieveByCredentials
    $credentials = ['email' => 'admin@dokterkuklinik.com'];
    $retrievedUser = $authProvider->retrieveByCredentials($credentials);
    
    if ($retrievedUser) {
        echo "   ✓ Custom provider can retrieve user by email\n";
        echo "   Retrieved User ID: " . $retrievedUser->id . "\n";
    } else {
        echo "   ✗ Custom provider CANNOT retrieve user by email\n";
    }
    
    // Test validateCredentials
    if (isset($retrievedUser) && $retrievedUser) {
        $passwordCredentials = ['email' => 'admin@dokterkuklinik.com', 'password' => 'admin123'];
        $isValid = $authProvider->validateCredentials($retrievedUser, $passwordCredentials);
        
        if ($isValid) {
            echo "   ✓ Custom provider validates credentials successfully\n";
        } else {
            echo "   ✗ Custom provider FAILS to validate credentials\n";
        }
    }
    echo "\n";
} catch (Exception $e) {
    echo "   ✗ Error testing custom provider: " . $e->getMessage() . "\n\n";
}

// Test 9: Manual Authentication Attempt
echo "9. Manual Authentication Attempt...\n";
try {
    // Clear any existing auth
    Auth::logout();
    
    $credentials = [
        'email' => 'admin@dokterkuklinik.com',
        'password' => 'admin123'
    ];
    
    echo "   Attempting login with credentials...\n";
    
    $loginAttempt = Auth::attempt($credentials);
    
    if ($loginAttempt) {
        echo "   ✓ Auth::attempt() successful\n";
        
        $authUser = Auth::user();
        if ($authUser) {
            echo "   ✓ Auth::user() returns user: " . $authUser->email . "\n";
            echo "   User ID: " . $authUser->id . "\n";
            echo "   User Name: " . $authUser->name . "\n";
        } else {
            echo "   ✗ Auth::user() returns null after successful attempt\n";
        }
        
        if (Auth::check()) {
            echo "   ✓ Auth::check() returns true\n";
        } else {
            echo "   ✗ Auth::check() returns false after successful attempt\n";
        }
        
    } else {
        echo "   ✗ Auth::attempt() FAILED\n";
        
        // Additional debugging for failed attempt
        echo "   \n   Debugging failed attempt:\n";
        
        // Check if user exists
        $user = User::where('email', 'admin@dokterkuklinik.com')->first();
        if (!$user) {
            echo "   - User does not exist\n";
        } else {
            echo "   - User exists\n";
            
            // Check if password matches
            if (!Hash::check('admin123', $user->password)) {
                echo "   - Password does not match\n";
            } else {
                echo "   - Password matches\n";
            }
            
            // Check if user is active
            if (!$user->is_active) {
                echo "   - User is not active\n";
            } else {
                echo "   - User is active\n";
            }
        }
    }
    echo "\n";
} catch (Exception $e) {
    echo "   ✗ Error during manual authentication: " . $e->getMessage() . "\n\n";
}

// Test 10: File Permissions and Storage
echo "10. Checking File Permissions and Storage...\n";
$storageDirectories = [
    'storage/framework/sessions',
    'storage/logs',
    'storage/app',
    'bootstrap/cache'
];

foreach ($storageDirectories as $dir) {
    $fullPath = base_path($dir);
    if (is_dir($fullPath)) {
        $perms = substr(sprintf('%o', fileperms($fullPath)), -4);
        $writable = is_writable($fullPath) ? 'YES' : 'NO';
        echo "   {$dir}: exists, permissions {$perms}, writable: {$writable}\n";
    } else {
        echo "   {$dir}: does NOT exist\n";
    }
}
echo "\n";

// Test 11: Check Laravel Log for Authentication Errors
echo "11. Checking Recent Authentication Logs...\n";
try {
    $logFile = storage_path('logs/laravel.log');
    if (file_exists($logFile)) {
        $logContent = file_get_contents($logFile);
        $lines = explode("\n", $logContent);
        $recentLines = array_slice($lines, -100); // Last 100 lines
        
        $authErrors = [];
        foreach ($recentLines as $line) {
            if (stripos($line, 'auth') !== false || 
                stripos($line, 'login') !== false || 
                stripos($line, 'CustomEloquentUserProvider') !== false) {
                $authErrors[] = $line;
            }
        }
        
        if (!empty($authErrors)) {
            echo "   Recent authentication-related log entries:\n";
            foreach (array_slice($authErrors, -5) as $error) { // Last 5 entries
                echo "   " . trim($error) . "\n";
            }
        } else {
            echo "   No recent authentication-related log entries found\n";
        }
    } else {
        echo "   Log file does not exist: {$logFile}\n";
    }
    echo "\n";
} catch (Exception $e) {
    echo "   ✗ Error checking logs: " . $e->getMessage() . "\n\n";
}

// Summary and Recommendations
echo "=== DEBUGGING SUMMARY AND RECOMMENDATIONS ===\n\n";

echo "Based on the analysis above, here are the most likely causes and solutions:\n\n";

echo "1. SESSION ISSUES:\n";
echo "   - Check if sessions table exists and is writable\n";
echo "   - Verify session driver configuration\n";
echo "   - Clear browser cookies and try again\n\n";

echo "2. CUSTOM AUTHENTICATION PROVIDER:\n";
echo "   - The custom provider might have bugs in credential validation\n";
echo "   - Check the CustomEloquentUserProvider logic\n";
echo "   - Verify the retrieveByCredentials and validateCredentials methods\n\n";

echo "3. ROLE AND PERMISSION ISSUES:\n";
echo "   - Ensure the admin user has proper role assignment\n";
echo "   - Check both legacy role_id and Spatie roles\n";
echo "   - Verify canAccessPanel() method in User model\n\n";

echo "4. ENVIRONMENT CONFIGURATION:\n";
echo "   - Verify all environment variables are set correctly\n";
echo "   - Check APP_KEY is set and consistent\n";
echo "   - Ensure database connection is stable\n\n";

echo "5. FILE PERMISSIONS:\n";
echo "   - Check storage directories are writable by web server\n";
echo "   - Verify session files can be created and read\n\n";

echo "IMMEDIATE STEPS TO TRY:\n";
echo "1. Run: php artisan config:clear\n";
echo "2. Run: php artisan cache:clear\n";
echo "3. Run: php artisan session:table && php artisan migrate (if sessions table missing)\n";
echo "4. Check browser developer tools for cookie/session issues\n";
echo "5. Try logging in with different browsers/incognito mode\n";
echo "6. Check web server error logs for additional clues\n\n";

echo "=== DEBUG COMPLETED ===\n";