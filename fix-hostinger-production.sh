#!/bin/bash

# HOSTINGER PRODUCTION FIX SCRIPT
# Fix Dr. Yaya nama_lengkap issue

echo "🚀 Connecting to Hostinger Production Server..."
echo "================================================"

# Connection details
HOST="153.92.8.132"
PORT="65002"
USER="u454362045"
PASS="LaTahzan@01"
WEBROOT="domains/dokterkuklinik.com/public_html"

# Create a script to run on the server
cat > /tmp/fix_nama_lengkap.sh << 'EOF'
#!/bin/bash
cd domains/dokterkuklinik.com/public_html

echo "🔍 Checking current Dr. Yaya data..."
php artisan tinker --execute="
\$d = App\\Models\\Dokter::where('username', 'yaya')->first();
if (\$d) {
    echo 'Current data:' . PHP_EOL;
    echo 'Username: ' . \$d->username . PHP_EOL;
    echo 'Nama Lengkap: ' . \$d->nama_lengkap . PHP_EOL;
    echo 'User Name: ' . (\$d->user ? \$d->user->name : 'No user') . PHP_EOL;
} else {
    echo 'Dokter not found!';
}
"

echo -e "\n🔧 Fixing nama_lengkap..."
php artisan tinker --execute="
\$d = App\\Models\\Dokter::where('username', 'yaya')->first();
if (\$d) {
    // Update dokter nama_lengkap
    \$d->nama_lengkap = 'Dr. Yaya Mulyana, M.Kes';
    \$d->save();
    
    // Update user name
    if (\$d->user) {
        \$d->user->name = 'Dr. Yaya Mulyana, M.Kes';
        \$d->user->save();
    }
    
    echo '✅ Fixed! Username: ' . \$d->username . ', Nama: ' . \$d->nama_lengkap . PHP_EOL;
} else {
    echo '❌ Dokter not found!';
}
"

echo -e "\n🧹 Clearing all caches..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan optimize:clear

echo -e "\n✅ Verifying fix..."
php artisan tinker --execute="
\$d = App\\Models\\Dokter::where('username', 'yaya')->first();
if (\$d) {
    echo '✅ VERIFICATION:' . PHP_EOL;
    echo 'Username (for login): ' . \$d->username . PHP_EOL;
    echo 'Nama Lengkap (display): ' . \$d->nama_lengkap . PHP_EOL;
    echo 'User Name: ' . (\$d->user ? \$d->user->name : 'No user') . PHP_EOL;
}
"

echo -e "\n✅ Production fix complete!"
EOF

# Execute the fix on the server
sshpass -p "$PASS" ssh -p $PORT -o StrictHostKeyChecking=no $USER@$HOST 'bash -s' < /tmp/fix_nama_lengkap.sh

# Clean up
rm /tmp/fix_nama_lengkap.sh

echo -e "\n🎯 Fix deployed to production!"
echo "Please test: https://dokterkuklinik.com/dokter/mobile-app"
echo "Login with username: yaya"
echo "Should show: Selamat Siang, Dr. Yaya Mulyana, M.Kes"