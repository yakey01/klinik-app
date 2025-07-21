#!/bin/bash

# Ultra-Fast 500 Error Diagnosis
echo "⚡ INSTANT 500 ERROR DIAGNOSIS"

# Set password via environment or prompt
export SSHPASS=${SSH_PASS:-$(read -s -p "Password: " && echo $REPLY)}

echo -e "\n🚀 Analyzing production server..."

sshpass -e ssh -o StrictHostKeyChecking=no u196138154@srv556.hstgr.io << 'DIAGNOSIS'
cd /home/u196138154/domains/dokterkuklinik.com/public_html

echo "🔥 CRITICAL ERRORS:"
echo "=================="

# Check Laravel log for recent critical errors
echo "📜 Latest Laravel errors:"
tail -20 storage/logs/laravel.log 2>/dev/null | grep -E "(ERROR|CRITICAL|Exception)" | tail -5

# Quick database test
echo -e "\n🗃️  Database quick test:"
php -r "
try {
    require 'vendor/autoload.php';
    \$app = require 'bootstrap/app.php';
    \$kernel = \$app->make(Illuminate\Contracts\Console\Kernel::class);
    \$kernel->bootstrap();
    
    \$pdo = \DB::connection()->getPdo();
    echo '✅ DB Connection: OK\n';
    
    \$pegawai = \App\Models\Pegawai::where('username', 'naning')->first();
    echo 'Naning user: ' . (\$pegawai ? 'FOUND' : 'NOT FOUND') . '\n';
    
    if (\$pegawai) {
        echo 'Status: ' . (\$pegawai->aktif ? 'ACTIVE' : 'INACTIVE') . '\n';
        echo 'Type: ' . \$pegawai->jenis_pegawai . '\n';
    }
    
    \$role = \Spatie\Permission\Models\Role::where('name', 'paramedis')->first();
    echo 'Paramedis role: ' . (\$role ? 'EXISTS' : 'MISSING') . '\n';
    
} catch (Exception \$e) {
    echo '❌ ERROR: ' . \$e->getMessage() . '\n';
    echo 'File: ' . \$e->getFile() . ':' . \$e->getLine() . '\n';
}
"

echo -e "\n🌐 HTTP Status Test:"
curl -s -o /dev/null -w "Login page status: %{http_code}\n" "https://dokterkuklinik.com/login"

echo -e "\n🔧 Quick Fix Attempt:"
echo "Running emergency fixes..."
php artisan cache:clear >/dev/null 2>&1 && echo "✅ Cache cleared"
php artisan config:clear >/dev/null 2>&1 && echo "✅ Config cleared"
php artisan migrate --force >/dev/null 2>&1 && echo "✅ Migrations run"

echo -e "\n📊 System Status:"
php -r "echo 'PHP: ' . PHP_VERSION . ' | Memory: ' . ini_get('memory_limit') . '\n';"

echo -e "\n🎯 DIAGNOSIS COMPLETE"
DIAGNOSIS

echo -e "\n🏁 Analysis finished!"