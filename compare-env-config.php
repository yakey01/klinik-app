<?php
/**
 * Environment Configuration Comparison
 * Compare localhost vs production configurations
 */

echo "=== ENVIRONMENT CONFIGURATION COMPARISON ===\n\n";

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
    
    try {
        $app = require_once __DIR__ . '/bootstrap/app.php';
        $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
        $kernel->bootstrap();
        
        echo "Current Environment: " . config('app.env') . "\n\n";
        
        // Critical configuration items that affect CSRF
        $criticalConfigs = [
            'app.env' => 'Application Environment',
            'app.debug' => 'Debug Mode',
            'app.url' => 'Application URL',
            'app.key' => 'Application Key (length)',
            'session.driver' => 'Session Driver',
            'session.lifetime' => 'Session Lifetime (minutes)',
            'session.domain' => 'Session Domain',
            'session.path' => 'Session Path',
            'session.secure' => 'Secure Cookies',
            'session.http_only' => 'HTTP Only Cookies',
            'session.same_site' => 'SameSite Policy',
            'database.default' => 'Database Connection',
        ];
        
        echo "CURRENT CONFIGURATION:\n";
        echo str_repeat("=", 50) . "\n";
        
        foreach ($criticalConfigs as $key => $description) {
            $value = config($key);
            
            // Special handling for sensitive values
            if ($key === 'app.key') {
                $value = $value ? 'SET (' . strlen($value) . ' chars)' : 'NOT SET';
            } elseif (is_bool($value)) {
                $value = $value ? 'true' : 'false';
            } elseif (is_null($value)) {
                $value = 'null';
            }
            
            printf("%-30s: %s\n", $description, $value);
        }
        
        // Expected production values
        echo "\n\nEXPECTED PRODUCTION VALUES:\n";
        echo str_repeat("=", 50) . "\n";
        $expectedProd = [
            'Application Environment' => 'production',
            'Debug Mode' => 'false',
            'Application URL' => 'https://dokterkuklinik.com',
            'Session Driver' => 'database',
            'Session Lifetime (minutes)' => '1440',
            'Session Domain' => 'null (auto-detect)',
            'Session Path' => '/',
            'Secure Cookies' => 'true (for HTTPS)',
            'HTTP Only Cookies' => 'true',
            'SameSite Policy' => 'lax'
        ];
        
        foreach ($expectedProd as $key => $value) {
            printf("%-30s: %s\n", $key, $value);
        }
        
        // Database connectivity test
        echo "\n\nDATABASE CONNECTION TEST:\n";
        echo str_repeat("=", 50) . "\n";
        try {
            $pdo = DB::connection()->getPdo();
            echo "✅ Database connection: SUCCESS\n";
            echo "   Driver: " . $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) . "\n";
            echo "   Server version: " . $pdo->getAttribute(PDO::ATTR_SERVER_VERSION) . "\n";
            
            // Test tables that are critical for auth
            $criticalTables = ['users', 'roles', 'sessions'];
            foreach ($criticalTables as $table) {
                try {
                    $count = DB::table($table)->count();
                    echo "✅ Table '$table': $count records\n";
                } catch (Exception $e) {
                    echo "❌ Table '$table': ERROR - " . $e->getMessage() . "\n";
                }
            }
            
        } catch (Exception $e) {
            echo "❌ Database connection: FAILED\n";
            echo "   Error: " . $e->getMessage() . "\n";
        }
        
        // Web server detection
        echo "\n\nWEB SERVER ENVIRONMENT:\n";
        echo str_repeat("=", 50) . "\n";
        echo "Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "\n";
        echo "PHP Version: " . PHP_VERSION . "\n";
        echo "PHP SAPI: " . php_sapi_name() . "\n";
        echo "Document Root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Unknown') . "\n";
        echo "Script Filename: " . (__FILE__) . "\n";
        
        // Check for common hosting-specific issues
        echo "\n\nCOMMON HOSTING ISSUES CHECK:\n";
        echo str_repeat("=", 50) . "\n";
        
        // Check if storage is writable
        $storageWritable = is_writable(storage_path());
        echo "Storage writable: " . ($storageWritable ? 'YES' : 'NO') . "\n";
        
        // Check if sessions directory exists and is writable
        if (config('session.driver') === 'file') {
            $sessionPath = storage_path('framework/sessions');
            $sessionWritable = is_writable($sessionPath);
            echo "Session directory writable: " . ($sessionWritable ? 'YES' : 'NO') . "\n";
        }
        
        // Check cache directory
        $cacheWritable = is_writable(storage_path('framework/cache'));
        echo "Cache directory writable: " . ($cacheWritable ? 'YES' : 'NO') . "\n";
        
        // Check if .env file exists
        $envExists = file_exists(base_path('.env'));
        echo ".env file exists: " . ($envExists ? 'YES' : 'NO') . "\n";
        
        // Check important PHP extensions
        $requiredExtensions = ['pdo', 'openssl', 'mbstring', 'tokenizer', 'xml', 'ctype', 'json'];
        echo "\nPHP Extensions:\n";
        foreach ($requiredExtensions as $ext) {
            $loaded = extension_loaded($ext);
            echo "   $ext: " . ($loaded ? 'LOADED' : 'MISSING') . "\n";
        }
        
        echo "\n=== COMPARISON COMPLETE ===\n";
        
        // Generate recommendations
        echo "\nRECOMMENDATIONS:\n";
        echo str_repeat("=", 50) . "\n";
        
        if (config('app.env') !== 'production') {
            echo "⚠️  Set APP_ENV=production in .env\n";
        }
        
        if (config('app.debug') === true) {
            echo "⚠️  Set APP_DEBUG=false in production\n";
        }
        
        if (config('session.secure') !== true && str_contains(config('app.url'), 'https')) {
            echo "⚠️  Set SESSION_SECURE_COOKIE=true for HTTPS\n";
        }
        
        if (!config('app.key')) {
            echo "❌ Run: php artisan key:generate\n";
        }
        
        echo "\n";
        
    } catch (Exception $e) {
        echo "❌ Configuration check failed: " . $e->getMessage() . "\n";
    }
} else {
    echo "❌ Run from Laravel root directory\n";
}

echo "=== END COMPARISON ===\n";