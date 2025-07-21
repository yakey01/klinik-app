<?php
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make('Illuminate\\Contracts\\Console\\Kernel');
$kernel->bootstrap();

echo "=== FILAMENT AUTHENTICATION FIX ===" . PHP_EOL;

try {
    // Check current Filament configuration
    echo "Checking Filament configuration..." . PHP_EOL;
    
    // Check if user model has proper methods
    $user = DB::table('users')->where('email', 'tina@paramedis.com')->first();
    if ($user) {
        echo "✅ User found in database" . PHP_EOL;
        
        // Create User model instance to test methods
        $userModel = new App\Models\User();
        $userInstance = $userModel->where('email', 'tina@paramedis.com')->first();
        
        if ($userInstance) {
            echo "✅ User model instance created" . PHP_EOL;
            
            // Test hasRole method if it exists
            if (method_exists($userInstance, 'hasRole')) {
                $hasParamedisRole = $userInstance->hasRole('paramedis');
                echo "Role check (hasRole): " . ($hasParamedisRole ? 'PASS ✅' : 'FAIL ❌') . PHP_EOL;
            } else {
                echo "⚠️  hasRole method not found, using role_id check" . PHP_EOL;
            }
            
            // Check role relationship
            if ($userInstance->role) {
                echo "✅ Role relationship works: {$userInstance->role->name}" . PHP_EOL;
            } else {
                echo "❌ Role relationship broken" . PHP_EOL;
            }
        }
    }
    
    // Test Filament Auth specifically
    echo PHP_EOL . "Testing Filament-specific authentication..." . PHP_EOL;
    
    // Clear any existing auth
    Auth::logout();
    
    // Test with web guard specifically
    $webAuth = Auth::guard('web')->attempt(['email' => 'tina@paramedis.com', 'password' => 'password123']);
    echo "Web guard auth: " . ($webAuth ? 'SUCCESS ✅' : 'FAILED ❌') . PHP_EOL;
    
    if ($webAuth) {
        $authUser = Auth::guard('web')->user();
        echo "Authenticated user: {$authUser->email}" . PHP_EOL;
        
        // Test canAccessPanel method if it exists
        $paramedisPanel = app('App\\Providers\\Filament\\ParamedisPanelProvider');
        if (method_exists($paramedisPanel, 'canAccessPanel')) {
            $canAccess = $paramedisPanel->canAccessPanel();
            echo "Can access paramedis panel: " . ($canAccess ? 'YES ✅' : 'NO ❌') . PHP_EOL;
        }
        
        Auth::logout();
    }
    
    // Check password hash format
    echo PHP_EOL . "Checking password hash format..." . PHP_EOL;
    $passwordHash = $user->password;
    echo "Hash algorithm: " . (str_starts_with($passwordHash, '$2y$') ? 'bcrypt ✅' : 'unknown ❌') . PHP_EOL;
    
    // Test password verification again with fresh hash
    echo "Creating fresh password hash..." . PHP_EOL;
    $freshHash = Hash::make('password123');
    $freshVerify = Hash::check('password123', $freshHash);
    echo "Fresh hash verification: " . ($freshVerify ? 'PASS ✅' : 'FAIL ❌') . PHP_EOL;
    
    // Update user with fresh hash if needed
    if ($freshVerify) {
        DB::table('users')
            ->where('email', 'tina@paramedis.com')
            ->update(['password' => $freshHash, 'updated_at' => now()]);
        echo "✅ Updated user with fresh password hash" . PHP_EOL;
        
        // Test auth again with fresh hash
        $finalAuth = Auth::attempt(['email' => 'tina@paramedis.com', 'password' => 'password123']);
        echo "Final auth test: " . ($finalAuth ? 'SUCCESS ✅' : 'FAILED ❌') . PHP_EOL;
        if ($finalAuth) Auth::logout();
    }
    
    // Clear all caches aggressively
    echo PHP_EOL . "Clearing all caches..." . PHP_EOL;
    Artisan::call('cache:clear');
    Artisan::call('config:clear');
    Artisan::call('route:clear');
    Artisan::call('view:clear');
    
    // Try to clear session cache if exists
    try {
        Artisan::call('session:clear');
        echo "✅ Session cache cleared" . PHP_EOL;
    } catch (Exception $e) {
        echo "ℹ️  Session clear not available" . PHP_EOL;
    }
    
    echo PHP_EOL . "=== FILAMENT FIX COMPLETE ===" . PHP_EOL;
    echo "Try login again at: https://dokterkuklinik.com/paramedis/login" . PHP_EOL;
    echo "Email: tina@paramedis.com" . PHP_EOL;
    echo "Password: password123" . PHP_EOL;
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . PHP_EOL;
    echo "File: " . $e->getFile() . ":" . $e->getLine() . PHP_EOL;
}
?>