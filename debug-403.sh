#!/bin/bash

echo "🔍 DEBUG 403 FORBIDDEN ERROR"
echo "============================"
echo "Current directory: $(pwd)"
echo "Current user: $(whoami)"
echo ""

echo "📁 DIRECTORY STRUCTURE:"
echo "ls -la /"
ls -la /
echo ""

echo "📁 DOMAIN DIRECTORY:"
echo "ls -la /domains/"
ls -la /domains/ 2>/dev/null || echo "❌ /domains/ not found"
echo ""

echo "📁 PUBLIC_HTML DIRECTORY:"
echo "ls -la /domains/dokterkuklinik.com/"
ls -la /domains/dokterkuklinik.com/ 2>/dev/null || echo "❌ Domain directory not found"
echo ""

echo "📁 WEBSITE FILES:"
echo "ls -la /domains/dokterkuklinik.com/public_html/"
ls -la /domains/dokterkuklinik.com/public_html/ 2>/dev/null || echo "❌ public_html not found"
echo ""

echo "🔍 CHECKING FOR INDEX FILES:"
echo "Does index.php exist in public_html?"
if [ -f "/domains/dokterkuklinik.com/public_html/index.php" ]; then
    echo "✅ index.php found"
    echo "First 10 lines of index.php:"
    head -n 10 /domains/dokterkuklinik.com/public_html/index.php
else
    echo "❌ index.php NOT found"
fi
echo ""

echo "🔍 CHECKING FOR LARAVEL FILES:"
echo "Laravel files check:"
ls -la /domains/dokterkuklinik.com/public_html/vendor 2>/dev/null || echo "❌ vendor directory not found"
ls -la /domains/dokterkuklinik.com/public_html/bootstrap 2>/dev/null || echo "❌ bootstrap directory not found"
ls -la /domains/dokterkuklinik.com/public_html/artisan 2>/dev/null || echo "❌ artisan file not found"
echo ""

echo "🔍 CHECKING PERMISSIONS:"
echo "public_html permissions:"
ls -ld /domains/dokterkuklinik.com/public_html/ 2>/dev/null || echo "❌ Cannot check permissions"
echo ""

echo "🔍 CHECKING .HTACCESS:"
if [ -f "/domains/dokterkuklinik.com/public_html/.htaccess" ]; then
    echo "✅ .htaccess found"
    echo "Content:"
    cat /domains/dokterkuklinik.com/public_html/.htaccess
else
    echo "❌ .htaccess NOT found"
fi
echo ""

echo "🔍 CHECKING ALTERNATIVE PATHS:"
echo "Checking if files are in wrong location..."
find /domains/dokterkuklinik.com/ -name "artisan" -type f 2>/dev/null || echo "❌ artisan not found anywhere"
find /domains/dokterkuklinik.com/ -name "index.php" -type f 2>/dev/null || echo "❌ index.php not found anywhere"
echo ""

echo "🔍 CHECKING WEB SERVER LOGS:"
echo "Looking for web server error logs..."
find /domains/dokterkuklinik.com/ -name "*error*log*" -type f 2>/dev/null || echo "❌ No error logs found"
find /var/log/ -name "*error*log*" -type f 2>/dev/null | head -5 || echo "❌ No system error logs accessible"
echo ""

echo "🔍 CHECKING HOSTINGER SPECIFIC PATHS:"
echo "Checking common Hostinger paths..."
ls -la ~/public_html/ 2>/dev/null || echo "❌ ~/public_html/ not found"
ls -la ~/domains/ 2>/dev/null || echo "❌ ~/domains/ not found"
ls -la ~/htdocs/ 2>/dev/null || echo "❌ ~/htdocs/ not found"
echo ""

echo "🔍 DISK SPACE CHECK:"
df -h
echo ""

echo "🔍 PROCESS CHECK:"
echo "PHP processes:"
ps aux | grep php || echo "❌ No PHP processes"
echo ""

echo "🎯 RECOMMENDATIONS:"
echo "1. Check Hostinger control panel document root setting"
echo "2. Verify domain is pointing to correct directory"
echo "3. Check if there's a separate public_html for the domain"
echo "4. Contact Hostinger support if structure is different"
echo ""

echo "✅ DEBUG COMPLETE!"