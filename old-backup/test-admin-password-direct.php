<?php
/**
 * Direct Admin Password Test - Local vs Hostinger Comparison
 * Test password verification without Laravel dependencies
 */

echo "🔧 DIRECT ADMIN PASSWORD TESTING\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n";
echo "===============================\n\n";

// Test both local copy and remote Hostinger
$tests = [
    'local' => [
        'host' => 'localhost',
        'user' => 'u454362045_u45436245_kli',
        'pass' => 'LaTahzan@01',
        'db' => 'u454362045_u45436245_kli'
    ]
];

foreach ($tests as $environment => $config) {
    echo "🔍 TESTING {$environment} ENVIRONMENT\n";
    echo str_repeat("=", 30) . "\n";
    
    try {
        // Connect to database
        $pdo = new PDO(
            "mysql:host={$config['host']};dbname={$config['db']};charset=utf8mb4",
            $config['user'],
            $config['pass'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );
        
        echo "✅ Database connection successful\n";
        
        // Get admin user
        $stmt = $pdo->prepare("
            SELECT 
                id, 
                name, 
                username, 
                email, 
                role_id, 
                is_active,
                password,
                LENGTH(password) as password_length,
                SUBSTRING(password, 1, 30) as password_preview,
                created_at,
                updated_at
            FROM users 
            WHERE email = 'admin@dokterku.com'
            ORDER BY id
        ");
        
        $stmt->execute();
        $admin = $stmt->fetch();
        
        if ($admin) {
            echo "✅ Admin user found:\n";
            echo "   ID: {$admin['id']}\n";
            echo "   Name: {$admin['name']}\n";
            echo "   Email: {$admin['email']}\n";
            echo "   Username: {$admin['username']}\n";
            echo "   Role ID: {$admin['role_id']}\n";
            echo "   Active: {$admin['is_active']}\n";
            echo "   Password Length: {$admin['password_length']}\n";
            echo "   Password Preview: {$admin['password_preview']}...\n";
            echo "   Created: {$admin['created_at']}\n";
            echo "   Updated: {$admin['updated_at']}\n\n";
            
            // Test common passwords using PHP's password_verify
            $testPasswords = ['admin123', 'admin', 'password', '12345', 'LaTahzan@01'];
            $passwordHash = $admin['password'];
            
            echo "🔐 Testing password combinations:\n";
            foreach ($testPasswords as $testPass) {
                if (password_verify($testPass, $passwordHash)) {
                    echo "   ✅ MATCH: '{$testPass}' - SUCCESS!\n";
                } else {
                    echo "   ❌ FAIL: '{$testPass}'\n";
                }
            }
            
            // Also test with bcrypt verification (Laravel uses bcrypt)
            echo "\n🔐 Testing Laravel bcrypt verification:\n";
            foreach ($testPasswords as $testPass) {
                // Simulate Laravel's Hash::check behavior
                if (substr($passwordHash, 0, 4) === '$2y$' || substr($passwordHash, 0, 4) === '$2a$' || substr($passwordHash, 0, 4) === '$2b$') {
                    if (password_verify($testPass, $passwordHash)) {
                        echo "   ✅ BCRYPT MATCH: '{$testPass}' - SUCCESS!\n";
                    } else {
                        echo "   ❌ BCRYPT FAIL: '{$testPass}'\n";
                    }
                }
            }
            
            // Analyze hash type
            echo "\n🔍 Hash Analysis:\n";
            if (substr($passwordHash, 0, 4) === '$2y$') {
                echo "   Hash Type: bcrypt (PHP 5.3.7+)\n";
            } elseif (substr($passwordHash, 0, 4) === '$2a$') {
                echo "   Hash Type: bcrypt (original)\n";
            } elseif (substr($passwordHash, 0, 4) === '$2b$') {
                echo "   Hash Type: bcrypt (fixed)\n";
            } else {
                echo "   Hash Type: Unknown - {$admin['password_preview']}\n";
            }
            
            // Check role information
            echo "\n👤 Role Information:\n";
            $roleStmt = $pdo->prepare("SELECT * FROM roles WHERE id = ?");
            $roleStmt->execute([$admin['role_id']]);
            $role = $roleStmt->fetch();
            
            if ($role) {
                echo "   Role Name: {$role['name']}\n";
                echo "   Display Name: {$role['display_name']}\n";
            } else {
                echo "   ❌ Role not found for ID: {$admin['role_id']}\n";
            }
            
        } else {
            echo "❌ Admin user not found with email 'admin@dokterku.com'\n";
            
            // Check for any admin users
            $stmt = $pdo->prepare("
                SELECT id, name, username, email, role_id 
                FROM users 
                WHERE role_id IN (SELECT id FROM roles WHERE name = 'admin')
                   OR username LIKE '%admin%'
                   OR email LIKE '%admin%'
                ORDER BY id
            ");
            $stmt->execute();
            $admins = $stmt->fetchAll();
            
            if ($admins) {
                echo "\n🔍 Found other potential admin users:\n";
                foreach ($admins as $user) {
                    echo "   ID: {$user['id']}, Email: {$user['email']}, Username: {$user['username']}\n";
                }
            }
        }
        
    } catch (PDOException $e) {
        echo "❌ Database error: " . $e->getMessage() . "\n";
    }
    
    echo "\n" . str_repeat("-", 50) . "\n\n";
}

// Now test the same on Hostinger via SSH command
echo "🌐 TESTING HOSTINGER REMOTE\n";
echo str_repeat("=", 30) . "\n";

$hostingerCommand = 'sshpass -p "LaTahzan@01" ssh -o StrictHostKeyChecking=no -p 65002 u454362045@153.92.8.132 "cd domains/dokterkuklinic.com/public_html && mysql -h localhost -u u454362045_u45436245_kli -pLaTahzan@01 u454362045_u45436245_kli -e \"SELECT id, name, username, email, role_id, is_active, LENGTH(password) as password_length, SUBSTRING(password, 1, 30) as password_preview FROM users WHERE email = \\'admin@dokterku.com\\' ORDER BY id;\" 2>/dev/null"';

$hostingerResult = shell_exec($hostingerCommand);

echo "Hostinger Result:\n";
echo $hostingerResult ?: "No output from Hostinger";

echo "\n🎯 SUMMARY\n";
echo "==========\n";
echo "Check the password verification results above.\n";
echo "If local verification works but web login fails, the issue is likely:\n";
echo "- Web authentication middleware\n";
echo "- Session configuration\n";
echo "- Route/controller issues\n";
echo "- CSRF token problems\n\n";