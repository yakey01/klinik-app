<?php

echo "🔍 Testing Database Connection...\n";

// Database configuration
$hosts = ['localhost', '127.0.0.1', 'mysql.dokterkuklinik.com', 'mysql.hostinger.com', 'mysql'];
$port = 3306;
$database = 'u454362045_u45436245_kli';
$username = 'u454362045_u45436245_kli';
$password = 'LaTahzan@01';

$connected = false;
$workingHost = null;

foreach ($hosts as $host) {
    echo "Testing connection to: {$host}:{$port}\n";
    
    // Test port connectivity first
    $connection = @fsockopen($host, $port, $errno, $errstr, 5);
    if (\!$connection) {
        echo "⚠️  Cannot reach {$host}:{$port} - {$errstr}\n";
        continue;
    }
    fclose($connection);
    echo "✅ Port {$port} is reachable on {$host}\n";
    
    // Test MySQL connection
    try {
        $dsn = "mysql:host={$host};port={$port};dbname={$database}";
        $pdo = new PDO($dsn, $username, $password, [
            PDO::ATTR_TIMEOUT => 5,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        
        // Test a simple query
        $stmt = $pdo->query('SELECT 1');
        if ($stmt) {
            echo "✅ MySQL connection successful to {$host}\!\n";
            $connected = true;
            $workingHost = $host;
            break;
        }
    } catch (PDOException $e) {
        echo "❌ MySQL connection failed to {$host}: " . $e->getMessage() . "\n";
    }
}

if ($connected) {
    echo "\n🎉 SUCCESS: Database connection established\!\n";
    echo "Working host: {$workingHost}\n";
    echo "Database: {$database}\n";
    
    // Test Laravel artisan command
    echo "\n🔧 Testing Laravel artisan commands...\n";
    
    $output = [];
    $returnCode = 0;
    exec('php artisan config:cache 2>&1', $output, $returnCode);
    
    if ($returnCode === 0) {
        echo "✅ Config caching successful\n";
    } else {
        echo "❌ Config caching failed:\n";
        echo implode("\n", $output) . "\n";
    }
    
    exit(0);
} else {
    echo "\n❌ FAILURE: Could not connect to any database host\!\n";
    echo "\n🔧 Troubleshooting steps:\n";
    echo "1. Verify MySQL service is running\n";
    echo "2. Check database credentials\n";
    echo "3. Verify user permissions:\n";
    echo "   GRANT ALL PRIVILEGES ON {$database}.* TO '{$username}'@'localhost';\n";
    echo "   FLUSH PRIVILEGES;\n";
    echo "4. Check firewall settings\n";
    echo "5. Contact hosting provider if issues persist\n";
    
    exit(1);
}
EOF < /dev/null