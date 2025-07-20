<?php

// Test database connection for Hostinger
echo "🔍 Testing Database Connection for Hostinger\n";
echo "============================================\n\n";

// Database configurations to test
$configs = [
    [
        'name' => 'Localhost',
        'host' => 'localhost',
        'port' => 3306,
        'database' => 'u454362045_u45436245_kli',
        'username' => 'u454362045_u45436245_kli',
        'password' => 'LaTahzan@01'
    ],
    [
        'name' => '127.0.0.1',
        'host' => '127.0.0.1',
        'port' => 3306,
        'database' => 'u454362045_u45436245_kli',
        'username' => 'u454362045_u45436245_kli',
        'password' => 'LaTahzan@01'
    ],
    [
        'name' => 'MySQL Hostinger',
        'host' => 'mysql.hostinger.com',
        'port' => 3306,
        'database' => 'u454362045_u45436245_kli',
        'username' => 'u454362045_u45436245_kli',
        'password' => 'LaTahzan@01'
    ]
];

foreach ($configs as $config) {
    echo "Testing: {$config['name']}\n";
    echo "Host: {$config['host']}:{$config['port']}\n";
    echo "Database: {$config['database']}\n";
    echo "Username: {$config['username']}\n";
    
    try {
        $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset=utf8mb4";
        $pdo = new PDO($dsn, $config['username'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT => 10
        ]);
        
        echo "✅ SUCCESS: Database connection established\n";
        
        // Test query
        $stmt = $pdo->query("SELECT VERSION() as version");
        $result = $stmt->fetch();
        echo "📊 MySQL Version: {$result['version']}\n";
        
        // Test if tables exist
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "📋 Tables found: " . count($tables) . "\n";
        
        if (count($tables) > 0) {
            echo "📝 Sample tables: " . implode(', ', array_slice($tables, 0, 5)) . "\n";
        }
        
        echo "\n";
        break; // Stop on first successful connection
        
    } catch (PDOException $e) {
        echo "❌ FAILED: " . $e->getMessage() . "\n";
        echo "Error Code: " . $e->getCode() . "\n\n";
    }
}

// Test with mysqli as fallback
echo "🔄 Testing with mysqli fallback...\n";
try {
    $mysqli = new mysqli('localhost', 'u454362045_u45436245_kli', 'LaTahzan@01', 'u454362045_u45436245_kli', 3306);
    
    if ($mysqli->connect_error) {
        echo "❌ mysqli connection failed: " . $mysqli->connect_error . "\n";
    } else {
        echo "✅ mysqli connection successful\n";
        echo "📊 Server info: " . $mysqli->server_info . "\n";
        $mysqli->close();
    }
} catch (Exception $e) {
    echo "❌ mysqli exception: " . $e->getMessage() . "\n";
}

echo "\n🔍 Environment Information:\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "PDO MySQL: " . (extension_loaded('pdo_mysql') ? '✅ Available' : '❌ Not Available') . "\n";
echo "MySQLi: " . (extension_loaded('mysqli') ? '✅ Available' : '❌ Not Available') . "\n";
echo "Current working directory: " . getcwd() . "\n";

// Check if .env file exists
if (file_exists('.env')) {
    echo "📄 .env file exists\n";
    $env_content = file_get_contents('.env');
    if (preg_match('/DB_HOST=(.+)/', $env_content, $matches)) {
        echo "🔧 Current DB_HOST in .env: " . trim($matches[1]) . "\n";
    }
} else {
    echo "⚠️ .env file not found\n";
} 