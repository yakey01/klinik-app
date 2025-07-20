#!/bin/bash

echo "🧹 Starting disk space cleanup..."

# Function to safely remove files with confirmation
safe_remove() {
    local path="$1"
    local description="$2"
    
    if [ -e "$path" ]; then
        echo "🗑️  Removing $description: $path"
        rm -rf "$path"
        echo "✅ Removed: $path"
    else
        echo "⚠️  Not found: $path"
    fi
}

# Function to truncate log files
truncate_logs() {
    local log_file="$1"
    if [ -f "$log_file" ] && [ -s "$log_file" ]; then
        echo "📝 Truncating log file: $log_file"
        echo "" > "$log_file"
        echo "✅ Truncated: $log_file"
    fi
}

echo "📊 Current disk usage before cleanup:"
du -sh * | sort -hr | head -10

echo ""
echo "🚀 Starting cleanup process..."

# 1. Clean up old backup files
echo ""
echo "📦 Cleaning up backup files..."
safe_remove "react-native-backup" "React Native backup folder (397M)"
safe_remove "*.tar.gz" "All tar.gz backup files"
safe_remove "old-files" "Old files directory"
safe_remove "archive" "Archive directory"
safe_remove "migration_backups" "Migration backups"

# 2. Clean up large log files
echo ""
echo "📝 Cleaning up log files..."
truncate_logs "storage/logs/laravel.log"

# 3. Clean up storage cache and temporary files
echo ""
echo "🗂️  Cleaning up storage cache..."
safe_remove "storage/framework/cache/*" "Framework cache files"
safe_remove "storage/framework/sessions/*" "Session files"
safe_remove "storage/framework/views/*" "View cache files"
safe_remove "storage/app/public/*" "Public storage files (if not needed)"

# 4. Clean up test database files
echo ""
echo "🗄️  Cleaning up test database files..."
safe_remove "database/testing*.sqlite" "Test database files"

# 5. Clean up node_modules if not needed for production
echo ""
echo "📦 Checking node_modules..."
if [ -d "node_modules" ]; then
    echo "⚠️  node_modules found (267M). Consider removing if not needed for production."
    echo "   Run: rm -rf node_modules (if you're sure it's not needed)"
fi

# 6. Clean up vendor if not needed (be careful!)
echo ""
echo "📚 Checking vendor directory..."
if [ -d "vendor" ]; then
    echo "⚠️  vendor found (152M). This is usually needed for Laravel."
    echo "   Only remove if you're sure it can be regenerated with composer install"
fi

echo ""
echo "📊 Disk usage after cleanup:"
du -sh * | sort -hr | head -10

echo ""
echo "🎉 Cleanup completed!"
echo ""
echo "💡 Additional tips for hosting:"
echo "   - Check hosting file manager for old backups"
echo "   - Remove old database dumps if any"
echo "   - Clean up email attachments if using hosting email"
echo "   - Remove old website backups from hosting panel" 