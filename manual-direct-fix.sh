#!/bin/bash
set -e

echo "🔧 MANUAL DIRECT FIX - Perbaikan Langsung di Server"
echo "===================================================="
echo "Kita akan langsung check dan fix masalah di server"
echo ""

# Color functions
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

log() { echo -e "${BLUE}[$(date '+%H:%M:%S')]${NC} $1"; }
error() { echo -e "${RED}❌ ERROR:${NC} $1"; }
success() { echo -e "${GREEN}✅ SUCCESS:${NC} $1"; }
info() { echo -e "${YELLOW}ℹ️  INFO:${NC} $1"; }

# 1. CHECK ACTUAL ERROR
log "🔍 Checking actual error di server..."
echo ""
echo "=== CHECK ERROR LOG ==="

# Cari error yang sebenarnya (bukan hanya stack trace)
if [ -f "storage/logs/laravel.log" ]; then
    info "Mencari error message yang sebenarnya..."
    
    # Cari baris yang ada kata ERROR tapi bukan stack trace
    grep -E "^\[.*\] production\.ERROR:" storage/logs/laravel.log | tail -5 || echo "No ERROR lines found"
    
    echo ""
    info "Mencari exception details..."
    grep -B 2 "exception.*Error" storage/logs/laravel.log | tail -10 || echo "No exception details found"
fi

# 2. CHECK CURRENT STATE
log "📋 Checking current state..."
echo ""
echo "=== CURRENT STATE CHECK ==="

# Check if vendor exists
if [ -d "vendor" ]; then
    info "Vendor directory exists"
    # Check for problematic packages
    if [ -d "vendor/bezhansalleh" ]; then
        error "PROBLEM FOUND: bezhansalleh/filament-shield masih ada!"
    else
        success "bezhansalleh/filament-shield tidak ditemukan"
    fi
else
    error "Vendor directory tidak ada!"
fi

# Check .env
if [ -f ".env" ]; then
    info "Checking .env file..."
    if grep -q "^APP_KEY=base64:" .env; then
        success "APP_KEY exists"
    else
        error "APP_KEY missing or invalid!"
    fi
else
    error ".env file tidak ada!"
fi

# 3. DIRECT FIX - BUAT SIMPLE PHP FILE UNTUK TEST
log "🛠️ Creating direct test file..."
echo ""
echo "=== CREATING DIRECT TEST ==="

# Buat test file yang bypass Laravel
cat > public/test-direct.php << 'EOPHP'
<?php
// Direct PHP test - bypass Laravel
header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>
<html>
<head>
    <title>Dokterku - Direct Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .success { color: #27ae60; }
        .error { color: #e74c3c; }
        .info { color: #3498db; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🔧 Dokterku Direct Test</h1>
        <p class='info'>This test bypasses Laravel to check server functionality.</p>
        
        <h2>1. PHP Info</h2>
        <p>PHP Version: " . PHP_VERSION . "</p>
        <p>Server: " . $_SERVER['SERVER_SOFTWARE'] . "</p>
        <p>Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "</p>
        
        <h2>2. File Check</h2>";

// Check important files
$files = [
    'vendor/autoload.php' => '../vendor/autoload.php',
    'bootstrap/app.php' => '../bootstrap/app.php',
    '.env' => '../.env',
    'composer.json' => '../composer.json'
];

foreach ($files as $name => $path) {
    if (file_exists($path)) {
        echo "<p class='success'>✅ $name exists</p>";
    } else {
        echo "<p class='error'>❌ $name NOT FOUND</p>";
    }
}

echo "<h2>3. Database Test</h2>";
try {
    $pdo = new PDO('mysql:host=localhost;dbname=u454362045_u45436245_kli', 'u454362045_u45436245_kli', 'KlinikApp2025!');
    echo "<p class='success'>✅ Database connection SUCCESS</p>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch();
    echo "<p class='info'>Users in database: " . $result['count'] . "</p>";
} catch (Exception $e) {
    echo "<p class='error'>❌ Database error: " . $e->getMessage() . "</p>";
}

echo "<h2>4. Laravel Test</h2>";
if (file_exists('../vendor/autoload.php')) {
    try {
        require_once '../vendor/autoload.php';
        echo "<p class='success'>✅ Autoload SUCCESS</p>";
        
        try {
            $app = require_once '../bootstrap/app.php';
            echo "<p class='success'>✅ Bootstrap SUCCESS</p>";
            echo "<p class='info'>Laravel Version: " . $app->version() . "</p>";
        } catch (Exception $e) {
            echo "<p class='error'>❌ Bootstrap ERROR: " . $e->getMessage() . "</p>";
            echo "<pre>" . $e->getFile() . ":" . $e->getLine() . "</pre>";
        }
    } catch (Exception $e) {
        echo "<p class='error'>❌ Autoload ERROR: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p class='error'>❌ Vendor directory not found</p>";
}

echo "
        <h2>5. Next Steps</h2>
        <p class='info'>Based on the results above, we can determine the exact issue.</p>
        <ul>
            <li><a href='/'>Try main site</a></li>
            <li><a href='/test'>Try Laravel test route</a></li>
            <li><a href='/test-simple.php'>Try simple PHP test</a></li>
        </ul>
    </div>
</body>
</html>";
?>
EOPHP

# Buat simple PHP test juga
cat > public/test-simple.php << 'EOPHP'
<?php
// Ultra simple test
echo "Dokterku Healthcare System - Simple PHP Test<br>";
echo "Time: " . date('Y-m-d H:i:s') . "<br>";
echo "PHP Version: " . PHP_VERSION . "<br>";
echo "Status: If you see this, PHP is working!";
?>
EOPHP

success "Direct test files created"

# 4. EMERGENCY FIX - BUAT MINIMAL WORKING SITE
log "🚨 Creating emergency minimal site..."
echo ""
echo "=== EMERGENCY MINIMAL SITE ==="

# Backup index.php lama
if [ -f "public/index.php" ]; then
    mv public/index.php public/index.php.backup
fi

# Buat emergency index.php
cat > public/index.php << 'EOPHP'
<?php
// Emergency index.php - minimal Laravel bootstrap

// Check if we can load Laravel
if (file_exists(__DIR__.'/../vendor/autoload.php')) {
    try {
        require __DIR__.'/../vendor/autoload.php';
        $app = require_once __DIR__.'/../bootstrap/app.php';
        
        // Try to handle request
        $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
        $response = $kernel->handle(
            $request = Illuminate\Http\Request::capture()
        )->send();
        $kernel->terminate($request, $response);
        
    } catch (Exception $e) {
        // Laravel failed - show emergency page
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>Dokterku - Maintenance Mode</title>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <style>
                body { 
                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
                    min-height: 100vh; 
                    display: flex; 
                    align-items: center; 
                    justify-content: center; 
                    margin: 0;
                    padding: 20px;
                }
                .container { 
                    background: white; 
                    padding: 40px; 
                    border-radius: 20px; 
                    box-shadow: 0 25px 50px rgba(0,0,0,0.15); 
                    text-align: center; 
                    max-width: 600px; 
                    width: 100%;
                }
                h1 { color: #2c3e50; margin-bottom: 20px; }
                .status { 
                    background: #f39c12; 
                    color: white; 
                    padding: 15px; 
                    border-radius: 10px; 
                    margin: 20px 0; 
                    font-weight: bold;
                }
                .links { margin-top: 30px; }
                .links a { 
                    display: inline-block;
                    margin: 0 10px; 
                    padding: 10px 20px; 
                    background: #3498db; 
                    color: white; 
                    text-decoration: none; 
                    border-radius: 5px; 
                }
                .links a:hover { background: #2980b9; }
                .error-details {
                    background: #f8f9fa;
                    padding: 15px;
                    border-radius: 5px;
                    text-align: left;
                    margin-top: 20px;
                    font-size: 12px;
                    color: #7f8c8d;
                }
            </style>
        </head>
        <body>
            <div class="container">
                <h1>🏥 Dokterku Healthcare System</h1>
                <div class="status">
                    🔧 System Under Maintenance
                </div>
                <p>We're currently updating the system. Please check back shortly.</p>
                
                <div class="links">
                    <a href="/test-direct.php">System Check</a>
                    <a href="/test-simple.php">Simple Test</a>
                </div>
                
                <div class="error-details">
                    <strong>Technical Details:</strong><br>
                    Error: <?php echo htmlspecialchars($e->getMessage()); ?><br>
                    Time: <?php echo date('Y-m-d H:i:s'); ?>
                </div>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
} else {
    // No vendor directory - show setup page
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Dokterku - Setup Required</title>
        <meta charset="utf-8">
        <style>
            body { font-family: Arial, sans-serif; margin: 40px; background: #f5f5f5; }
            .container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); max-width: 600px; margin: 0 auto; }
            .error { color: #e74c3c; }
            .warning { color: #f39c12; }
        </style>
    </head>
    <body>
        <div class="container">
            <h1 class="error">⚠️ Setup Required</h1>
            <p>The vendor directory is missing. Please run composer install.</p>
            <p class="warning">Current directory: <?php echo __DIR__; ?></p>
            <p><a href="/test-direct.php">Run System Check</a></p>
        </div>
    </body>
    </html>
    <?php
}
?>
EOPHP

success "Emergency index.php created"

# 5. FIX PERMISSIONS
log "🔐 Fixing permissions..."
chmod 644 public/index.php
chmod 644 public/test-direct.php
chmod 644 public/test-simple.php
chmod -R 777 storage/ 2>/dev/null || true
chmod -R 755 bootstrap/cache/ 2>/dev/null || true

success "Permissions fixed"

echo ""
echo "===================================================="
success "🔧 MANUAL DIRECT FIX COMPLETED!"
echo "===================================================="
echo ""
echo "📋 WHAT WAS DONE:"
echo "   🔍 Checked actual error messages"
echo "   📁 Created direct test files"
echo "   🚨 Created emergency index.php with fallback"
echo "   🔐 Fixed file permissions"
echo ""
echo "🌐 TEST THESE URLs:"
echo "   • https://dokterkuklinik.com/test-direct.php (System check)"
echo "   • https://dokterkuklinik.com/test-simple.php (Simple PHP test)"
echo "   • https://dokterkuklinik.com (Main site with fallback)"
echo ""
echo "📌 NEXT STEPS:"
echo "   1. Check /test-direct.php untuk diagnosa lengkap"
echo "   2. Lihat error details yang muncul"
echo "   3. Kita bisa fix berdasarkan hasil diagnosa"
echo ""
echo "✅ Website sekarang punya fallback dan diagnostic tools!"
echo "===================================================="