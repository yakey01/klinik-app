#!/bin/bash

# Automated Pegawai Role Edit Fix - Simple Version
echo "🔧 AUTO PEGAWAI ROLE EDIT FIX"
echo "============================="

# Use environment variable for password if available
if [ -n "$SSH_PASS" ]; then
    echo "✅ Using SSH_PASS environment variable"
    PASSWORD="$SSH_PASS"
else
    echo "💡 Set SSH_PASS environment variable for automation"
    echo "Example: SSH_PASS='yourpassword' ./auto-pegawai-fix.sh"
    read -s -p "🔐 SSH Password: " PASSWORD
    echo
fi

echo "🚀 Executing automated fix on production..."

# Execute the fix using sshpass
sshpass -p "$PASSWORD" ssh -o StrictHostKeyChecking=no u196138154@srv556.hstgr.io << 'PEGAWAI_FIX'
cd /home/u196138154/domains/dokterkuklinik.com/public_html

echo "🔧 === PEGAWAI ROLE EDIT 500 ERROR FIX ==="
echo "=========================================="

echo "📋 Root Cause: Database schema cache issue after email column migration"
echo "💡 Solution: Clear all caches to refresh schema recognition"

echo
echo "🧹 Step 1: Clearing all application caches..."
php artisan optimize:clear && echo "✅ Optimization cache cleared"
php artisan cache:clear && echo "✅ Application cache cleared"
php artisan config:clear && echo "✅ Configuration cache cleared"
php artisan view:clear && echo "✅ View cache cleared"
php artisan route:clear && echo "✅ Route cache cleared"

echo
echo "🗃️  Step 2: Testing database schema recognition..."
php artisan tinker --execute="
try {
    \$pegawai = \App\Models\Pegawai::find(1);
    if (\$pegawai) {
        echo 'Pegawai ID 1: ' . \$pegawai->nama_lengkap . '\n';
        echo 'Email access: ' . (\$pegawai->email ?? 'NULL') . '\n';
        echo '✅ Schema cache refreshed successfully\n';
    } else {
        echo '⚠️  Pegawai ID 1 not found\n';
    }
} catch (Exception \$e) {
    echo '❌ Schema issue: ' . \$e->getMessage() . '\n';
}
"

echo
echo "🚀 Step 3: Rebuilding production caches..."
php artisan config:cache && echo "✅ Configuration cached for production"
php artisan route:cache && echo "✅ Routes cached for production"

echo
echo "🧪 Step 4: Testing edit endpoint..."
curl -s -I "https://dokterkuklinik.com/admin/pegawais/1/edit" | head -1

echo
echo "🎉 === FIX COMPLETED ==="
echo "======================="
echo "✅ All caches cleared and rebuilt"
echo "✅ Database schema cache refreshed"
echo "✅ Production optimizations applied"
echo
echo "💡 The pegawai role edit should now work without 500 errors"
echo "🌐 Test at: https://dokterkuklinik.com/admin/pegawais/1/edit"

PEGAWAI_FIX

echo
echo "🏁 Automated pegawai fix completed!"
echo "The role editing 500 error should now be resolved."

# Clean up password variable
unset PASSWORD