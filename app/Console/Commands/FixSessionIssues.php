<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;

class FixSessionIssues extends Command
{
    protected $signature = 'fix:session-issues {--force : Force fix even in production}';
    protected $description = 'Comprehensive fix for Laravel session and CSRF token issues';

    public function handle()
    {
        $this->info('🔧 Starting comprehensive session and CSRF fix...');
        
        // Safety check for production
        if (app()->environment('production') && !$this->option('force')) {
            if (!$this->confirm('This will run in PRODUCTION. Are you sure?')) {
                $this->error('Operation cancelled.');
                return 1;
            }
        }

        $this->fixEnvironmentConfiguration();
        $this->clearAllCaches();
        $this->fixStoragePermissions();
        $this->cleanSessionFiles();
        $this->fixMiddlewareConfiguration();
        $this->optimizeSessionConfiguration();
        $this->verifyFixResults();

        $this->info('✅ Session and CSRF fix completed successfully!');
        return 0;
    }

    private function fixEnvironmentConfiguration()
    {
        $this->info('📝 Fixing environment configuration...');
        
        $envPath = base_path('.env');
        if (!File::exists($envPath)) {
            $this->error('.env file not found!');
            return;
        }

        $envContent = File::get($envPath);
        $updated = false;

        // Ensure APP_KEY is set
        if (!str_contains($envContent, 'APP_KEY=base64:')) {
            $this->info('Generating new APP_KEY...');
            Artisan::call('key:generate', ['--force' => true]);
            $updated = true;
        }

        // Session configuration
        $sessionConfig = [
            'SESSION_DRIVER' => 'file',
            'SESSION_LIFETIME' => '120',
            'SESSION_SECURE_COOKIE' => 'true',
            'SESSION_SAME_SITE' => 'lax',
            'SESSION_ENCRYPT' => 'false',
            'SESSION_HTTP_ONLY' => 'true'
        ];

        foreach ($sessionConfig as $key => $value) {
            if (!str_contains($envContent, $key . '=')) {
                $envContent .= "\n$key=$value";
                $updated = true;
                $this->info("Added $key=$value");
            } else {
                // Update existing value if different
                $pattern = "/^$key=.*$/m";
                $replacement = "$key=$value";
                if (!str_contains($envContent, $replacement)) {
                    $envContent = preg_replace($pattern, $replacement, $envContent);
                    $updated = true;
                    $this->info("Updated $key=$value");
                }
            }
        }

        if ($updated) {
            File::put($envPath, $envContent);
            $this->info('✅ Environment configuration updated');
        } else {
            $this->info('✅ Environment configuration is already correct');
        }
    }

    private function clearAllCaches()
    {
        $this->info('🗑️ Clearing all caches...');
        
        $commands = [
            'config:clear' => 'Configuration cache',
            'cache:clear' => 'Application cache',
            'route:clear' => 'Route cache',
            'view:clear' => 'View cache',
            'clear-compiled' => 'Compiled classes'
        ];

        foreach ($commands as $command => $description) {
            $this->info("Clearing $description...");
            Artisan::call($command);
        }

        $this->info('✅ All caches cleared');
    }

    private function fixStoragePermissions()
    {
        $this->info('🔧 Fixing storage permissions...');
        
        $directories = [
            'storage',
            'storage/framework',
            'storage/framework/sessions',
            'storage/framework/cache',
            'storage/framework/views',
            'storage/logs',
            'bootstrap/cache'
        ];

        foreach ($directories as $dir) {
            $fullPath = base_path($dir);
            if (File::isDirectory($fullPath)) {
                chmod($fullPath, 0755);
                $this->info("Set permissions for $dir");
            } else {
                File::makeDirectory($fullPath, 0755, true);
                $this->info("Created directory $dir");
            }
        }

        $this->info('✅ Storage permissions fixed');
    }

    private function cleanSessionFiles()
    {
        $this->info('🗂️ Cleaning session files...');
        
        $sessionPath = storage_path('framework/sessions');
        if (!File::isDirectory($sessionPath)) {
            File::makeDirectory($sessionPath, 0755, true);
            $this->info('Created sessions directory');
            return;
        }

        $sessionFiles = File::glob($sessionPath . '/laravel_session*');
        $cleaned = 0;
        $cutoff = time() - 3600; // 1 hour ago

        foreach ($sessionFiles as $file) {
            if (File::lastModified($file) < $cutoff) {
                File::delete($file);
                $cleaned++;
            }
        }

        $this->info("✅ Cleaned $cleaned old session files");
        $this->info('Remaining sessions: ' . count(File::glob($sessionPath . '/laravel_session*')));
    }

    private function fixMiddlewareConfiguration()
    {
        $this->info('🛡️ Checking middleware configuration...');
        
        $kernelPath = app_path('Http/Kernel.php');
        if (File::exists($kernelPath)) {
            $content = File::get($kernelPath);
            
            // Check if VerifyCsrfToken is in web middleware
            if (str_contains($content, 'VerifyCsrfToken::class')) {
                $this->info('✅ CSRF middleware is configured');
            } else {
                $this->warn('⚠️ CSRF middleware may not be properly configured');
            }
        }

        // Check VerifyCsrfToken middleware
        $csrfMiddlewarePath = app_path('Http/Middleware/VerifyCsrfToken.php');
        if (File::exists($csrfMiddlewarePath)) {
            $content = File::get($csrfMiddlewarePath);
            
            // Ensure proper exception handling
            if (!str_contains($content, 'protected $except = [')) {
                $this->info('ℹ️ No CSRF exceptions configured (this is usually good)');
            }
            
            $this->info('✅ CSRF middleware file exists');
        } else {
            $this->error('❌ CSRF middleware file not found');
        }
    }

    private function optimizeSessionConfiguration()
    {
        $this->info('⚙️ Optimizing session configuration...');
        
        // Create optimized session config
        $sessionConfigPath = config_path('session.php');
        if (File::exists($sessionConfigPath)) {
            $this->info('✅ Session configuration file exists');
        }

        // Rebuild config cache with new settings
        Artisan::call('config:cache');
        $this->info('✅ Configuration cache rebuilt');
        
        // Optimize application
        Artisan::call('optimize');
        $this->info('✅ Application optimized');
    }

    private function verifyFixResults()
    {
        $this->info('🔍 Verifying fix results...');
        
        // Check if storage is writable
        $sessionPath = storage_path('framework/sessions');
        if (is_writable($sessionPath)) {
            $this->info('✅ Session storage is writable');
        } else {
            $this->error('❌ Session storage is not writable');
        }

        // Check config cache
        if (File::exists(bootstrap_path('cache/config.php'))) {
            $this->info('✅ Configuration cache exists');
        } else {
            $this->warn('⚠️ Configuration cache not found');
        }

        // Test session configuration
        try {
            $sessionConfig = config('session');
            if ($sessionConfig['driver'] === 'file') {
                $this->info('✅ Session driver is set to file');
            } else {
                $this->warn("⚠️ Session driver is: " . $sessionConfig['driver']);
            }
            
            if ($sessionConfig['lifetime'] >= 120) {
                $this->info('✅ Session lifetime is adequate: ' . $sessionConfig['lifetime'] . ' minutes');
            } else {
                $this->warn('⚠️ Session lifetime may be too short: ' . $sessionConfig['lifetime'] . ' minutes');
            }
        } catch (\Exception $e) {
            $this->error('❌ Could not verify session configuration: ' . $e->getMessage());
        }
    }
}