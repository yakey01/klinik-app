#!/bin/bash

echo "🔧 Laravel Logs Troubleshooting Script"
echo "======================================"

# Check current directory
echo "📂 Current directory: $(pwd)"

# Check if we're in Laravel project
if [ ! -f "artisan" ]; then
    echo "❌ Not in Laravel project root. Please cd to your Laravel project directory."
    exit 1
fi

echo "✅ Laravel project detected"

# Check storage directory structure
echo ""
echo "📁 Checking storage directory structure..."
if [ ! -d "storage" ]; then
    echo "❌ storage/ directory not found. Creating..."
    mkdir -p storage
    chmod 755 storage
    echo "✅ Created storage/ directory"
else
    echo "✅ storage/ directory exists"
fi

# Check logs directory
if [ ! -d "storage/logs" ]; then
    echo "❌ storage/logs/ directory not found. Creating..."
    mkdir -p storage/logs
    chmod 755 storage/logs
    echo "✅ Created storage/logs/ directory"
else
    echo "✅ storage/logs/ directory exists"
fi

# Check laravel.log file
if [ ! -f "storage/logs/laravel.log" ]; then
    echo "❌ storage/logs/laravel.log not found. Creating..."
    touch storage/logs/laravel.log
    chmod 644 storage/logs/laravel.log
    echo "✅ Created storage/logs/laravel.log"
else
    echo "✅ storage/logs/laravel.log exists"
fi

# Check permissions
echo ""
echo "🔐 Checking permissions..."
ls -la storage/
ls -la storage/logs/

# Fix permissions if needed
echo ""
echo "🔧 Setting correct permissions..."
chmod -R 755 storage
chmod -R 644 storage/logs/*.log 2>/dev/null || true

# Check other required storage directories
echo ""
echo "📁 Ensuring all storage directories exist..."
mkdir -p storage/app/public
mkdir -p storage/framework/cache
mkdir -p storage/framework/sessions
mkdir -p storage/framework/testing
mkdir -p storage/framework/views

# Set proper permissions for all
chmod -R 755 storage
find storage -type f -name "*.log" -exec chmod 644 {} \; 2>/dev/null || true

# Test log writing
echo ""
echo "🧪 Testing log writing..."
php artisan tinker --execute="Log::info('Test log entry from troubleshooting script');"

# Check if log was written
if [ -f "storage/logs/laravel.log" ] && [ -s "storage/logs/laravel.log" ]; then
    echo "✅ Log writing test successful"
    echo "📄 Last few log entries:"
    tail -5 storage/logs/laravel.log
else
    echo "⚠️ Log file exists but might be empty or not writable"
fi

# Check .env logging configuration
echo ""
echo "⚙️ Checking .env configuration..."
if [ -f ".env" ]; then
    echo "LOG_CHANNEL: $(grep LOG_CHANNEL .env || echo 'not set')"
    echo "LOG_DEPRECATIONS_CHANNEL: $(grep LOG_DEPRECATIONS_CHANNEL .env || echo 'not set')"
    echo "LOG_LEVEL: $(grep LOG_LEVEL .env || echo 'not set')"
else
    echo "❌ .env file not found"
fi

# Check config/logging.php
echo ""
echo "📝 Checking logging configuration..."
if [ -f "config/logging.php" ]; then
    echo "✅ config/logging.php exists"
else
    echo "❌ config/logging.php not found"
fi

# Final status
echo ""
echo "📊 Final Status:"
echo "==============="
echo "Storage directory: $([ -d "storage" ] && echo "✅ OK" || echo "❌ Missing")"
echo "Logs directory: $([ -d "storage/logs" ] && echo "✅ OK" || echo "❌ Missing")"
echo "Laravel log file: $([ -f "storage/logs/laravel.log" ] && echo "✅ OK" || echo "❌ Missing")"

# Test tail command
echo ""
echo "🔍 Testing tail command..."
if tail -5 storage/logs/laravel.log > /dev/null 2>&1; then
    echo "✅ tail command works successfully"
    echo "📄 Current log content (last 10 lines):"
    tail -10 storage/logs/laravel.log
else
    echo "❌ tail command still failing"
    echo "File permissions:"
    ls -la storage/logs/laravel.log
fi

echo ""
echo "🎯 Quick fixes if still having issues:"
echo "1. Run: php artisan config:clear"
echo "2. Run: php artisan cache:clear" 
echo "3. Check web server user permissions"
echo "4. On shared hosting, contact support about file permissions"

echo ""
echo "✅ Troubleshooting completed!"