<?php
/**
 * Fix "This page has expired" error
 * Run this script to clear caches and fix CSRF/session issues
 */

echo "🔧 Fixing 'This page has expired' error...\n";
echo "📍 Target: https://dokterkuklinik.com/admin/dokters/2/edit\n\n";

// Check if we're in Laravel project
if (!file_exists('artisan')) {
    echo "❌ Error: Not in Laravel project root\n";
    exit(1);
}

echo "✅ Laravel project detected\n\n";

// Clear all caches
echo "🔄 Clearing caches...\n";

$commands = [
    'php artisan config:clear',
    'php artisan cache:clear',
    'php artisan view:clear', 
    'php artisan route:clear'
];

foreach ($commands as $command) {
    echo "Running: $command\n";
    $output = shell_exec($command . ' 2>&1');
    echo $output . "\n";
}

// Clear session files manually
echo "🗂️ Cleaning session files...\n";
$sessionPath = 'storage/framework/sessions/';
if (is_dir($sessionPath)) {
    $sessionFiles = glob($sessionPath . 'laravel_session*');
    $oldSessions = array_filter($sessionFiles, function($file) {
        return filemtime($file) < (time() - 3600); // Older than 1 hour
    });
    
    foreach ($oldSessions as $file) {
        unlink($file);
    }
    
    echo "Removed " . count($oldSessions) . " old session files\n";
    echo "Remaining sessions: " . count(glob($sessionPath . 'laravel_session*')) . "\n\n";
}

// Check and set proper permissions
echo "🔧 Setting permissions...\n";
if (is_dir('storage')) {
    chmod('storage', 0755);
    chmod('storage/framework', 0755);
    if (is_dir('storage/framework/sessions')) {
        chmod('storage/framework/sessions', 0755);
    }
    echo "✅ Storage permissions updated\n\n";
}

// Check .env configuration
echo "🔍 Checking .env configuration...\n";
if (file_exists('.env')) {
    $env = file_get_contents('.env');
    
    // Check APP_KEY
    if (strpos($env, 'APP_KEY=') === false || strpos($env, 'APP_KEY=base64:') === false) {
        echo "⚠️  APP_KEY not found or invalid, generating...\n";
        shell_exec('php artisan key:generate --force');
    } else {
        echo "✅ APP_KEY is set\n";
    }
    
    // Check session configuration
    $sessionSettings = [
        'SESSION_DRIVER=file',
        'SESSION_LIFETIME=120',
        'SESSION_SECURE_COOKIE=true',
        'SESSION_SAME_SITE=strict'
    ];
    
    foreach ($sessionSettings as $setting) {
        $key = explode('=', $setting)[0];
        if (strpos($env, $key) === false) {
            echo "Adding $setting to .env\n";
            file_put_contents('.env', "\n$setting", FILE_APPEND);
        }
    }
    
    echo "✅ .env configuration checked\n\n";
}

// Rebuild config cache
echo "🔄 Rebuilding config cache...\n";
$output = shell_exec('php artisan config:cache 2>&1');
echo $output . "\n";

// Test artisan
echo "🌐 Testing artisan...\n";
$version = shell_exec('php artisan --version 2>&1');
echo "Laravel version: " . trim($version) . "\n\n";

// Final optimization
echo "🚀 Running optimization...\n";
$output = shell_exec('php artisan optimize 2>&1');
echo $output . "\n";

echo "✅ Fix completed successfully!\n";
echo "📍 Please test: https://dokterkuklinik.com/admin/dokters/2/edit\n";
echo "💡 If issue persists, clear browser cache and try again.\n";
?>