<?php
/**
 * Emergency Database Connection Test for Hostinger
 * Upload this file to test database connectivity
 */

echo "🔌 Testing Database Connection on Hostinger\n\n";

// Test configurations to try
$configs = [
    [
        'host' => 'localhost',
        'db' => 'u454362045_u45436245_kli',
        'user' => 'u454362045_u45436245_kli',
        'pass' => 'LaTahzan@01'
    ],
    [
        'host' => '127.0.0.1',
        'db' => 'u454362045_u45436245_kli',
        'user' => 'u454362045_u45436245_kli',
        'pass' => 'LaTahzan@01'
    ],
    [
        'host' => 'mysql.dokterkuklinik.com',
        'db' => 'u454362045_u45436245_kli',
        'user' => 'u454362045_u45436245_kli',
        'pass' => 'LaTahzan@01'
    ]
];

foreach ($configs as $i => $config) {
    echo "Test " . ($i + 1) . ": {$config['host']}\n";
    echo "Database: {$config['db']}\n";
    echo "Username: {$config['user']}\n";
    
    try {
        $dsn = "mysql:host={$config['host']};dbname={$config['db']};charset=utf8mb4";
        $pdo = new PDO($dsn, $config['user'], $config['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        
        echo "✅ CONNECTION SUCCESS!\n";
        
        // Test a simple query
        $stmt = $pdo->query("SELECT DATABASE() as current_db, NOW() as current_time");
        $result = $stmt->fetch();
        echo "Current DB: " . $result['current_db'] . "\n";
        echo "Server Time: " . $result['current_time'] . "\n";
        
        // List tables
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "Tables count: " . count($tables) . "\n";
        if (count($tables) > 0) {
            echo "Sample tables: " . implode(', ', array_slice($tables, 0, 5)) . "\n";
        }
        
        echo "🎉 THIS CONFIG WORKS! Use these settings.\n\n";
        break;
        
    } catch (Exception $e) {
        echo "❌ CONNECTION FAILED: " . $e->getMessage() . "\n\n";
    }
}

echo "📋 Current server info:\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "Server: " . $_SERVER['SERVER_NAME'] ?? 'Unknown' . "\n";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown' . "\n";
?>