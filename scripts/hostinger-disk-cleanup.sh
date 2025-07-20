#!/bin/bash

# Comprehensive disk cleanup for Hostinger server
# Run this directly from SSH: bash hostinger-disk-cleanup.sh

echo "🧹 Hostinger Disk Space Cleanup..."

cd /home/u454362045

echo "📋 1. Current disk usage:"
du -sh * 2>/dev/null | sort -hr | head -10

echo ""
echo "📋 2. Check domains directory usage:"
du -sh domains/* 2>/dev/null | sort -hr

echo ""
echo "📋 3. Laravel application cleanup:"
cd /home/u454362045/domains/dokterkuklinik.com/public_html

echo "Cleaning Laravel cache and logs..."
rm -rf storage/framework/cache/data/* 2>/dev/null && echo "✅ Cache data cleared"
rm -rf storage/framework/sessions/* 2>/dev/null && echo "✅ Sessions cleared"
rm -rf storage/framework/views/* 2>/dev/null && echo "✅ Compiled views cleared"
rm -rf storage/logs/*.log 2>/dev/null && echo "✅ Laravel logs cleared"

echo ""
echo "📋 4. Remove old backup files:"
find . -name "*.backup*" -type f -exec ls -lh {} \; 2>/dev/null | head -10
find . -name "*.backup*" -type f -delete 2>/dev/null && echo "✅ Backup files removed"

echo ""
echo "📋 5. Remove old .env backup files:"
ls -la .env.backup* 2>/dev/null
rm -f .env.backup.* 2>/dev/null && echo "✅ Old .env backups removed"

echo ""
echo "📋 6. Clean up vendor cache (if exists):"
rm -rf vendor/cache/* 2>/dev/null && echo "✅ Vendor cache cleared"

echo ""
echo "📋 7. Remove old migration files (keep only essential):"
cd database/migrations
ls -la | grep -E "\.(php|md|json|sh)$" | wc -l
echo "Migration files found. Removing unnecessary ones..."
rm -f backup_migrations.sh 2>/dev/null
rm -f test_migrations.sh 2>/dev/null
rm -rf merged_originals/ 2>/dev/null && echo "✅ Merged originals removed"
rm -rf old_migrations/ 2>/dev/null && echo "✅ Old migrations removed"
rm -rf examples/ 2>/dev/null && echo "✅ Example migrations removed"
rm -f *.md 2>/dev/null && echo "✅ Documentation files removed"
rm -f *.json 2>/dev/null && echo "✅ JSON files removed"

echo ""
echo "📋 8. Clean up node_modules (if exists):"
cd /home/u454362045/domains/dokterkuklinik.com/public_html
if [ -d "node_modules" ]; then
    du -sh node_modules/
    rm -rf node_modules/ && echo "✅ node_modules removed"
fi

echo ""
echo "📋 9. Remove archive and backup directories:"
rm -rf archive/ 2>/dev/null && echo "✅ Archive directory removed"
rm -rf react-native-backup/ 2>/dev/null && echo "✅ React Native backup removed"

echo ""
echo "📋 10. Clean up temporary files:"
find . -name "*.tmp" -type f -delete 2>/dev/null && echo "✅ .tmp files removed"
find . -name "*.temp" -type f -delete 2>/dev/null && echo "✅ .temp files removed"
find . -name "*.cache" -type f -delete 2>/dev/null && echo "✅ .cache files removed"

echo ""
echo "📋 11. Remove test and debug files:"
rm -f debug-login.php 2>/dev/null
rm -f test-*.php 2>/dev/null
rm -f test-*.js 2>/dev/null
rm -f test-*.mjs 2>/dev/null
echo "✅ Test and debug files removed"

echo ""
echo "📋 12. Clean up user home directory:"
cd /home/u454362045
rm -rf scripts/ 2>/dev/null && echo "✅ Scripts directory removed"
rm -f *.sh 2>/dev/null && echo "✅ Shell scripts removed"

echo ""
echo "📋 13. Check final disk usage:"
cd /home/u454362045
du -sh * 2>/dev/null | sort -hr | head -10

echo ""
echo "📋 14. Laravel application size after cleanup:"
cd /home/u454362045/domains/dokterkuklinik.com/public_html
du -sh . 2>/dev/null

echo ""
echo "📋 15. Overall disk usage:"
df -h | head -5

echo ""
echo "🎉 Disk cleanup complete!"
echo ""
echo "📋 Next steps:"
echo "1. Run: php artisan config:clear"
echo "2. Run: php artisan cache:clear"
echo "3. Test login: https://dokterkuklinik.com/api/v2/auth/login"