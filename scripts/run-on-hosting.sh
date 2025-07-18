#!/bin/bash

echo "🚀 Commands to run on your hosting server"
echo "========================================="
echo ""
echo "Copy and paste these commands one by one in your hosting terminal:"
echo ""

echo "1. Navigate to your Laravel project directory:"
echo "cd /path/to/your/laravel/project"
echo ""

echo "2. Check if you're in the right directory:"
echo "ls -la"
echo ""

echo "3. Check PHP and Composer versions:"
echo "php --version"
echo "composer --version"
echo ""

echo "4. Check if Laravel is working:"
echo "php artisan --version"
echo ""

echo "5. Check if Pail is installed:"
echo "composer show laravel/pail"
echo ""

echo "6. If Pail is not installed, install it:"
echo "composer require --dev laravel/pail"
echo ""

echo "7. Clear all Laravel caches:"
echo "php artisan config:clear"
echo "php artisan cache:clear"
echo "php artisan route:clear"
echo "php artisan view:clear"
echo ""

echo "8. Regenerate autoload files:"
echo "composer dump-autoload"
echo ""

echo "9. Publish Pail configuration (if needed):"
echo "php artisan vendor:publish --tag=pail-config --force"
echo ""

echo "10. Test Pail command:"
echo "php artisan pail --help"
echo ""

echo "11. If still having issues, try:"
echo "composer update"
echo "php artisan optimize:clear"
echo ""

echo "12. Check Laravel logs for errors:"
echo "tail -n 50 storage/logs/laravel.log"
echo ""

echo "13. Check your .env file settings:"
echo "grep -E 'APP_ENV|APP_DEBUG' .env"
echo ""

echo "14. Make sure production settings are correct:"
echo "APP_ENV=production"
echo "APP_DEBUG=false"
echo ""

echo "15. Check file permissions:"
echo "chmod -R 755 storage bootstrap/cache"
echo "chown -R www-data:www-data storage bootstrap/cache  # if using Apache"
echo ""

echo "16. Final test:"
echo "php artisan config:cache"
echo "php artisan route:cache"
echo ""

echo "✅ If all commands run successfully, your Pail issue should be resolved!"
echo ""
echo "📞 If you still have issues, check:"
echo "   - Your hosting provider's PHP version compatibility"
echo "   - Required PHP extensions (mbstring, xml, curl, etc.)"
echo "   - File permissions and ownership"
echo "   - Laravel logs for specific error messages" 