<?php
// Quick production test script
require_once __DIR__ . "/vendor/autoload.php";
$app = require_once __DIR__ . "/bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

echo "=== PRODUCTION LOGIN TEST ===\n";

$admin = User::where("email", "admin@dokterkuklinik.com")->first();
if ($admin && Hash::check("admin123", $admin->password)) {
    echo "✅ Admin credentials valid\n";
    
    if (Auth::attempt(['email' => 'admin@dokterkuklinik.com', 'password' => 'admin123'])) {
        echo "✅ Authentication successful\n";
        echo "✅ Ready for production login\n";
    } else {
        echo "❌ Authentication failed\n";
    }
} else {
    echo "❌ Invalid admin credentials\n";
}
