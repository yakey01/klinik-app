<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    // Get admin user
    $admin = App\Models\User::find(1);
    if (!$admin) {
        echo "ERROR: Admin user not found\n";
        exit;
    }
    
    // Login as admin
    auth()->login($admin);
    
    echo "✅ Logged in as: " . $admin->name . "\n";
    echo "✅ Role: " . $admin->role->name . "\n";
    
    // Test route exists
    try {
        $url = route('filament.admin.resources.work-locations.index');
        echo "✅ Route generates: " . $url . "\n";
    } catch (Exception $e) {
        echo "❌ Route error: " . $e->getMessage() . "\n";
    }
    
    // Test resource exists
    try {
        $resource = App\Filament\Resources\WorkLocationResource::class;
        echo "✅ Resource class: " . $resource . "\n";
        echo "✅ Resource model: " . $resource::getModel() . "\n";
    } catch (Exception $e) {
        echo "❌ Resource error: " . $e->getMessage() . "\n";
    }
    
    // Test Filament panel
    try {
        $panel = Filament\Facades\Filament::getPanel('admin');
        echo "✅ Admin panel exists: " . $panel->getId() . "\n";
        echo "✅ Admin panel path: " . $panel->getPath() . "\n";
        
        // Check if resource is registered
        $resources = $panel->getResources();
        $workLocationResourceExists = in_array(App\Filament\Resources\WorkLocationResource::class, $resources);
        echo ($workLocationResourceExists ? "✅" : "❌") . " WorkLocationResource registered in panel\n";
        
    } catch (Exception $e) {
        echo "❌ Panel error: " . $e->getMessage() . "\n";
    }
    
    // Test middleware
    echo "\n🔍 Testing middleware:\n";
    $middlewares = [
        'auth',
        App\Http\Middleware\AdminMiddleware::class,
        App\Http\Middleware\RedirectToUnifiedAuth::class
    ];
    
    foreach ($middlewares as $middleware) {
        echo "Testing: " . $middleware . "\n";
        try {
            if (class_exists($middleware)) {
                echo "  ✅ Class exists\n";
            } else {
                echo "  ❌ Class not found\n";
            }
        } catch (Exception $e) {
            echo "  ❌ Error: " . $e->getMessage() . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ FATAL ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}