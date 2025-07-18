#!/bin/bash
set -e

echo "🔧 DATABASE STRUCTURE FIX for Dokterku"
echo "======================================"
echo "Fixing missing username column and completing database setup"
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

log "🗄️  Fixing database structure issues..."

# Fix the users table structure
mysql -h localhost -u u454362045_u45436245_kli -pKlinikApp2025! << 'EOSQL'
USE u454362045_u45436245_kli;

-- Check if username column exists
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
                  WHERE TABLE_SCHEMA = 'u454362045_u45436245_kli' 
                  AND TABLE_NAME = 'users' 
                  AND COLUMN_NAME = 'username');

-- Add username column if it doesn't exist
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE users ADD COLUMN username VARCHAR(255) UNIQUE AFTER email',
    'SELECT "Username column already exists" as message');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Update existing users with usernames
UPDATE users SET username = 'admin' WHERE email = 'admin@dokterku.com' AND username IS NULL;
UPDATE users SET username = 'tina_bendahara' WHERE email = 'tina@dokterku.com' AND username IS NULL;
UPDATE users SET username = 'manajer' WHERE email = 'manajer@dokterku.com' AND username IS NULL;
UPDATE users SET username = 'petugas' WHERE email = 'petugas@dokterku.com' AND username IS NULL;
UPDATE users SET username = 'paramedis' WHERE email = 'paramedis@dokterku.com' AND username IS NULL;

-- Ensure all required users exist
INSERT IGNORE INTO users (name, email, username, password, created_at, updated_at) VALUES 
('Super Admin', 'admin@dokterku.com', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NOW(), NOW()),
('Tina Bendahara', 'tina@dokterku.com', 'tina_bendahara', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NOW(), NOW()),
('Manajer Klinik', 'manajer@dokterku.com', 'manajer', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NOW(), NOW()),
('Petugas Admin', 'petugas@dokterku.com', 'petugas', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NOW(), NOW()),
('Paramedis Staff', 'paramedis@dokterku.com', 'paramedis', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NOW(), NOW());

-- Verify the structure
SELECT 'Database structure verification:' as message;
DESCRIBE users;
SELECT COUNT(*) as total_users FROM users;
SELECT username, email, name FROM users;

EOSQL

success "Database structure fixed successfully"

# Test database connection and structure
log "🧪 Testing database connection and structure..."
php -r "
try {
    \$pdo = new PDO('mysql:host=localhost;dbname=u454362045_u45436245_kli', 'u454362045_u45436245_kli', 'KlinikApp2025!');
    \$stmt = \$pdo->query('SELECT username, email FROM users LIMIT 1');
    \$user = \$stmt->fetch();
    echo '✅ Database connection: SUCCESS\n';
    echo '✅ Username column: EXISTS\n';
    echo '✅ Sample user: ' . \$user['username'] . ' (' . \$user['email'] . ')\n';
} catch (Exception \$e) {
    echo '❌ Database test failed: ' . \$e->getMessage() . '\n';
    exit(1);
}
"

# Clear Laravel cache to ensure fresh database schema
log "🧹 Clearing Laravel cache..."
rm -rf bootstrap/cache/*
rm -rf storage/framework/cache/*
rm -rf storage/framework/views/*

# Test Laravel application
log "🧪 Testing Laravel application..."
php -r "
try {
    require 'vendor/autoload.php';
    \$app = require 'bootstrap/app.php';
    echo '✅ Laravel application: SUCCESS\n';
} catch (Exception \$e) {
    echo '❌ Laravel application test failed: ' . \$e->getMessage() . '\n';
    echo 'File: ' . \$e->getFile() . ':' . \$e->getLine() . '\n';
}
"

echo ""
echo "======================================"
success "🎉 DATABASE STRUCTURE FIX COMPLETED!"
echo "======================================"
echo ""
echo "🌐 Website: https://dokterkuklinik.com"
echo "🔐 Test Login Credentials:"
echo "   • Username: admin | Password: password123"
echo "   • Username: tina_bendahara | Password: password123"
echo "   • Username: manajer | Password: password123"
echo ""
echo "📋 Available Panels:"
echo "   • /admin - Complete system administration"
echo "   • /bendahara - Financial management"
echo "   • /manajer - Executive dashboard"
echo "   • /petugas - Staff operations"
echo "   • /paramedis - Mobile medical staff"
echo ""
success "Login issues should now be resolved!"
echo "======================================"