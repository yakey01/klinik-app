<?php

require_once 'vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Contracts\Console\Kernel;

$app = require_once 'bootstrap/app.php';

$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

echo "Testing doctor login fix...\n";

try {
    $dokter = \App\Models\Dokter::where('username', 'yaya')->first();
    if ($dokter) {
        echo "Username: " . $dokter->username . "\n";
        echo "Password exists: " . ($dokter->password ? 'Yes' : 'No') . "\n";
        echo "Status: " . $dokter->status_akun . "\n";
        echo "Aktif: " . ($dokter->aktif ? 'Yes' : 'No') . "\n";
    } else {
        echo "Doctor 'yaya' not found\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}