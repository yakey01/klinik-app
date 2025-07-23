#!/bin/bash

# Paramedis Login 500 Error Deep Diagnosis Script
# This script will comprehensively analyze the paramedis login issue on Hostinger

set -e

echo "🔍 PARAMEDIS LOGIN 500 ERROR DEEP ANALYSIS"
echo "=========================================="

# Configuration
REMOTE_HOST="srv556.hstgr.io"
REMOTE_USER="u196138154"
REMOTE_PATH="/home/u196138154/domains/dokterkuklinik.com/public_html"

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

print_step() {
    echo -e "${BLUE}🔎 DIAGNOSIS $1:${NC} $2"
}

print_success() {
    echo -e "${GREEN}✅ FOUND:${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}⚠️  WARNING:${NC} $1"
}

print_error() {
    echo -e "${RED}❌ ISSUE:${NC} $1"
}

# Get password
echo -n "🔐 Enter SSH password for $REMOTE_USER@$REMOTE_HOST: "
read -s SSH_PASSWORD
echo

print_step "1" "Testing SSH connection and environment"
sshpass -p "$SSH_PASSWORD" ssh -o StrictHostKeyChecking=no "$REMOTE_USER@$REMOTE_HOST" << 'EOF'
cd /home/u196138154/domains/dokterkuklinik.com/public_html
echo "📍 Current directory: $(pwd)"
echo "🐘 PHP version: $(php -v | head -n1)"
echo "🎼 Laravel version: $(php artisan --version)"
echo "🔧 Environment: $(php artisan env)"
EOF

print_step "2" "Checking database connectivity and schema"
sshpass -p "$SSH_PASSWORD" ssh -o StrictHostKeyChecking=no "$REMOTE_USER@$REMOTE_HOST" << 'EOF'
cd /home/u196138154/domains/dokterkuklinik.com/public_html
echo "🗃️  Database connectivity test..."

php artisan tinker --execute="
try {
    \$dbTest = \DB::connection()->getPdo();
    echo 'Database connection: SUCCESS\n';
    
    // Check if pegawais table exists
    \$tables = \DB::select('SHOW TABLES');
    \$pegawaisExists = false;
    foreach (\$tables as \$table) {
        if (array_values((array)\$table)[0] === 'pegawais') {
            \$pegawaisExists = true;
            break;
        }
    }
    echo 'Pegawais table exists: ' . (\$pegawaisExists ? 'YES' : 'NO') . '\n';
    
    // Check pegawai table columns
    if (\$pegawaisExists) {
        \$columns = \DB::select('SHOW COLUMNS FROM pegawais');
        echo 'Pegawais table columns:\n';
        foreach (\$columns as \$col) {
            echo '  - ' . \$col->Field . ' (' . \$col->Type . ')' . (\$col->Key ? ' [' . \$col->Key . ']' : '') . '\n';
        }
    }
    
} catch (Exception \$e) {
    echo 'Database connection failed: ' . \$e->getMessage() . '\n';
}
"
EOF

print_step "3" "Checking migration status"
sshpass -p "$SSH_PASSWORD" ssh -o StrictHostKeyChecking=no "$REMOTE_USER@$REMOTE_HOST" << 'EOF'
cd /home/u196138154/domains/dokterkuklinik.com/public_html
echo "📋 Migration status check..."

php artisan migrate:status | head -20
echo
echo "🔍 Checking for email migration specifically..."
ls -la database/migrations/*email*pegawais* 2>/dev/null || echo "❌ Email migration file not found"
EOF

print_step "4" "Analyzing paramedis user data integrity"
sshpass -p "$SSH_PASSWORD" ssh -o StrictHostKeyChecking=no "$REMOTE_USER@$REMOTE_HOST" << 'EOF'
cd /home/u196138154/domains/dokterkuklinik.com/public_html
echo "👤 Paramedis user data analysis..."

php artisan tinker --execute="
try {
    // Check for 'naning' specifically
    \$naning = \App\Models\Pegawai::where('username', 'naning')
        ->orWhere('nama_lengkap', 'LIKE', '%naning%')
        ->orWhere('nama_lengkap', 'LIKE', '%Naning%')
        ->first();
    
    if (\$naning) {
        echo 'FOUND naning paramedis user:\n';
        echo '  ID: ' . \$naning->id . '\n';
        echo '  Nama: ' . \$naning->nama_lengkap . '\n';
        echo '  Username: ' . \$naning->username . '\n';
        echo '  NIK: ' . \$naning->nik . '\n';
        echo '  Jenis Pegawai: ' . \$naning->jenis_pegawai . '\n';
        echo '  Email: ' . (\$naning->email ?? 'NULL') . '\n';
        echo '  Aktif: ' . (\$naning->aktif ? 'YES' : 'NO') . '\n';
        echo '  Password exists: ' . (!empty(\$naning->password) ? 'YES' : 'NO') . '\n';
    } else {
        echo 'NANING USER NOT FOUND\n';
        
        // Show all paramedis users
        \$paramedis = \App\Models\Pegawai::where('jenis_pegawai', 'Paramedis')->get();
        echo 'All Paramedis users found: ' . \$paramedis->count() . '\n';
        foreach (\$paramedis as \$p) {
            echo '  - ' . \$p->nama_lengkap . ' (' . \$p->username . ')\n';
        }
    }
    
    // Check for duplicate emails
    \$duplicateEmails = \DB::select('SELECT email, COUNT(*) as count FROM pegawais WHERE email IS NOT NULL GROUP BY email HAVING COUNT(*) > 1');
    if (count(\$duplicateEmails) > 0) {
        echo 'DUPLICATE EMAILS FOUND:\n';
        foreach (\$duplicateEmails as \$dup) {
            echo '  - ' . \$dup->email . ' (count: ' . \$dup->count . ')\n';
        }
    } else {
        echo 'No duplicate emails found\n';
    }
    
} catch (Exception \$e) {
    echo 'User data analysis failed: ' . \$e->getMessage() . '\n';
}
"
EOF

print_step "5" "Checking roles and permissions"
sshpass -p "$SSH_PASSWORD" ssh -o StrictHostKeyChecking=no "$REMOTE_USER@$REMOTE_HOST" << 'EOF'
cd /home/u196138154/domains/dokterkuklinik.com/public_html
echo "🎭 Roles and permissions check..."

php artisan tinker --execute="
try {
    // Check if roles exist
    \$roles = ['paramedis', 'non_paramedis', 'petugas'];
    foreach (\$roles as \$roleName) {
        \$role = \Spatie\Permission\Models\Role::where('name', \$roleName)->first();
        echo 'Role ' . \$roleName . ': ' . (\$role ? 'EXISTS (ID: ' . \$role->id . ')' : 'MISSING') . '\n';
    }
    
    // Check users table
    \$userCount = \App\Models\User::count();
    echo 'Total users in users table: ' . \$userCount . '\n';
    
    // Check for naning in users table
    \$naningUser = \App\Models\User::where('username', 'naning')->first();
    echo 'Naning in users table: ' . (\$naningUser ? 'EXISTS (ID: ' . \$naningUser->id . ')' : 'NOT FOUND') . '\n';
    
} catch (Exception \$e) {
    echo 'Roles check failed: ' . \$e->getMessage() . '\n';
}
"
EOF

print_step "6" "Testing authentication flow manually"
sshpass -p "$SSH_PASSWORD" ssh -o StrictHostKeyChecking=no "$REMOTE_USER@$REMOTE_HOST" << 'EOF'
cd /home/u196138154/domains/dokterkuklinik.com/public_html
echo "🔐 Manual authentication flow test..."

php artisan tinker --execute="
try {
    // Test the exact authentication flow from UnifiedAuthController
    \$identifier = 'naning'; // or whatever the actual username is
    
    // Step 1: Find pegawai
    \$pegawai = \App\Models\Pegawai::where('username', \$identifier)
        ->orWhere('nik', \$identifier)
        ->first();
    
    if (!\$pegawai) {
        echo 'Step 1 FAILED: Pegawai not found for identifier: ' . \$identifier . '\n';
        exit();
    } else {
        echo 'Step 1 SUCCESS: Pegawai found - ' . \$pegawai->nama_lengkap . '\n';
    }
    
    // Step 2: Check if pegawai is active
    if (!\$pegawai->aktif) {
        echo 'Step 2 FAILED: Pegawai is not active\n';
        exit();
    } else {
        echo 'Step 2 SUCCESS: Pegawai is active\n';
    }
    
    // Step 3: Role mapping
    \$roleName = match(\$pegawai->jenis_pegawai) {
        'Paramedis' => 'paramedis',
        'Non-Paramedis' => 'non_paramedis',
        default => 'petugas'
    };
    echo 'Step 3: Role mapping - ' . \$pegawai->jenis_pegawai . ' -> ' . \$roleName . '\n';
    
    // Step 4: Find role
    \$role = \Spatie\Permission\Models\Role::where('name', \$roleName)->first();
    if (!\$role) {
        echo 'Step 4 FAILED: Role not found - ' . \$roleName . '\n';
        exit();
    } else {
        echo 'Step 4 SUCCESS: Role found - ' . \$role->name . ' (ID: ' . \$role->id . ')\n';
    }
    
    // Step 5: Check for existing user
    \$existingUser = \App\Models\User::where('username', \$pegawai->username)->first();
    if (\$existingUser) {
        echo 'Step 5: Existing user found - ' . \$existingUser->name . '\n';
    } else {
        echo 'Step 5: No existing user, would need to create new user\n';
        
        // Test user creation data
        \$userEmail = \$pegawai->nik . '@pegawai.local';
        echo 'Would create user with email: ' . \$userEmail . '\n';
        
        // Check if this email already exists
        \$emailExists = \App\Models\User::where('email', \$userEmail)->exists();
        echo 'Email conflict check: ' . (\$emailExists ? 'CONFLICT EXISTS' : 'OK') . '\n';
    }
    
    echo 'Authentication flow analysis complete\n';
    
} catch (Exception \$e) {
    echo 'Authentication test failed: ' . \$e->getMessage() . '\n';
    echo 'Stack trace: ' . \$e->getTraceAsString() . '\n';
}
"
EOF

print_step "7" "Checking application logs"
sshpass -p "$SSH_PASSWORD" ssh -o StrictHostKeyChecking=no "$REMOTE_USER@$REMOTE_HOST" << 'EOF'
cd /home/u196138154/domains/dokterkuklinik.com/public_html
echo "📜 Recent application logs..."

echo "🔍 Laravel logs (last 50 lines):"
tail -50 storage/logs/laravel.log 2>/dev/null | grep -E "(ERROR|Exception|paramedis|naning)" || echo "No relevant errors found in Laravel logs"

echo
echo "🔍 Error logs (if available):"
tail -20 /home/u196138154/domains/dokterkuklinik.com/logs/error.log 2>/dev/null | tail -10 || echo "Error log not accessible"
EOF

print_step "8" "Configuration and cache status"
sshpass -p "$SSH_PASSWORD" ssh -o StrictHostKeyChecking=no "$REMOTE_USER@$REMOTE_HOST" << 'EOF'
cd /home/u196138154/domains/dokterkuklinik.com/public_html
echo "⚙️  Configuration and cache status..."

echo "🔧 Config cache status:"
php artisan config:show auth.guards.pegawai 2>/dev/null || echo "Pegawai guard not configured"

echo "📦 Route cache status:"
php artisan route:list | grep -E "(login|auth)" | head -5

echo "🗂️  View cache status:"
ls -la bootstrap/cache/ | head -5

echo "💾 Application cache status:"
php artisan cache:table-status 2>/dev/null || echo "Cache table status not available"
EOF

echo
echo "🏁 DIAGNOSIS COMPLETE"
echo "===================="
echo "📊 Analysis Summary:"
echo "1. Database connectivity and schema"
echo "2. Migration status verification" 
echo "3. Paramedis user data integrity"
echo "4. Role and permission configuration"
echo "5. Authentication flow simulation"
echo "6. Application error logs"
echo "7. Configuration and cache status"
echo
echo "🔧 Next steps based on findings above:"
echo "- Check for database constraint violations"
echo "- Verify naning user exists and is properly configured"
echo "- Look for role mapping issues"
echo "- Check for email column conflicts"
echo "- Review authentication middleware"

# Clean up password
unset SSH_PASSWORD