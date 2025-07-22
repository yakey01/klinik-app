<?php
// Quick production test - save as test-production.php and run on Hostinger
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🔍 QUICK PRODUCTION TEST\n";
echo "Environment: " . config('app.env') . "\n";

$dokter = \App\Models\Dokter::where('username', 'yaya')->first();
if ($dokter) {
    echo "✅ Found Dr. Yaya:\n";
    echo "   Username: " . $dokter->username . "\n";
    echo "   Nama Lengkap: " . $dokter->nama_lengkap . "\n";
    echo "   User Name: " . $dokter->user->name . "\n";
} else {
    echo "❌ Dr. Yaya not found!\n";
}