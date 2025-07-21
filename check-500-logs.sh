#!/bin/bash

# Check 500 Server Error Logs for Paramedis Login
# This script will extract and analyze the actual error details

set -e

echo "🔍 CHECKING 500 SERVER ERROR LOGS"
echo "=================================="

REMOTE_HOST="srv556.hstgr.io"
REMOTE_USER="u196138154"
REMOTE_PATH="/home/u196138154/domains/dokterkuklinik.com/public_html"

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

print_section() {
    echo -e "${BLUE}📋 $1${NC}"
    echo "----------------------------------------"
}

print_error() {
    echo -e "${RED}❌ ERROR FOUND:${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}⚠️  WARNING:${NC} $1"
}

print_info() {
    echo -e "${GREEN}ℹ️  INFO:${NC} $1"
}

# Get password
echo -n "🔐 Enter SSH password for $REMOTE_USER@$REMOTE_HOST: "
read -s SSH_PASSWORD
echo

print_section "1. LARAVEL APPLICATION LOGS"
sshpass -p "$SSH_PASSWORD" ssh -o StrictHostKeyChecking=no "$REMOTE_USER@$REMOTE_HOST" << 'EOF'
cd /home/u196138154/domains/dokterkuklinik.com/public_html

echo "📜 Recent Laravel errors (last 100 lines):"
if [ -f "storage/logs/laravel.log" ]; then
    echo "=== LARAVEL LOG ERRORS ==="
    tail -100 storage/logs/laravel.log | grep -A 5 -B 5 -E "(ERROR|Exception|Fatal|paramedis|naning|500)" || echo "No recent Laravel errors found"
    echo
    echo "=== LATEST LOG ENTRIES ==="
    tail -20 storage/logs/laravel.log
else
    echo "❌ Laravel log file not found"
fi
EOF

print_section "2. PHP ERROR LOGS"
sshpass -p "$SSH_PASSWORD" ssh -o StrictHostKeyChecking=no "$REMOTE_USER@$REMOTE_HOST" << 'EOF'
cd /home/u196138154/domains/dokterkuklinik.com/public_html

echo "🐘 PHP error logs:"
# Check various PHP error log locations
for log_file in "/home/u196138154/domains/dokterkuklinik.com/logs/error.log" \
                "/home/u196138154/public_html/error_log" \
                "/home/u196138154/error_log" \
                "error_log" \
                "/var/log/php_errors.log"; do
    if [ -f "$log_file" ]; then
        echo "=== FOUND: $log_file ==="
        tail -50 "$log_file" | grep -A 3 -B 3 -E "(Fatal|Error|Exception|500|paramedis)" || echo "No relevant PHP errors in this log"
        echo
    fi
done
EOF

print_section "3. WEB SERVER ERROR LOGS"
sshpass -p "$SSH_PASSWORD" ssh -o StrictHostKeyChecking=no "$REMOTE_USER@$REMOTE_HOST" << 'EOF'
# Check web server error logs
echo "🌐 Web server error logs:"
for log_file in "/var/log/apache2/error.log" \
                "/var/log/httpd/error_log" \
                "/usr/local/apache/logs/error_log" \
                "/home/u196138154/logs/error.log" \
                "/home/u196138154/domains/dokterkuklinik.com/logs/error.log"; do
    if [ -f "$log_file" ]; then
        echo "=== FOUND: $log_file ==="
        tail -30 "$log_file" | grep -A 2 -B 2 -E "(500|Internal Server Error|paramedis)" || echo "No relevant web server errors"
        echo
    fi
done
EOF

print_section "4. REAL-TIME ERROR SIMULATION"
sshpass -p "$SSH_PASSWORD" ssh -o StrictHostKeyChecking=no "$REMOTE_USER@$REMOTE_HOST" << 'EOF'
cd /home/u196138154/domains/dokterkuklinik.com/public_html

echo "🧪 Simulating paramedis login to capture real-time errors:"
echo "Testing authentication flow with detailed error capture..."

php artisan tinker --execute="
try {
    echo 'Starting paramedis login simulation...\n';
    
    // Enable detailed error reporting
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
    
    // Find a paramedis user to test with
    \$paramedis = \App\Models\Pegawai::where('jenis_pegawai', 'Paramedis')->first();
    
    if (!\$paramedis) {
        echo 'ERROR: No paramedis users found in database\n';
        exit();
    }
    
    echo 'Testing with paramedis: ' . \$paramedis->nama_lengkap . ' (username: ' . \$paramedis->username . ')\n';
    
    // Simulate the exact UnifiedAuthController login flow
    \$identifier = \$paramedis->username;
    
    // Step 1: Find pegawai (this should work)
    \$pegawai = \App\Models\Pegawai::where('username', \$identifier)
        ->orWhere('nik', \$identifier)
        ->first();
    
    if (!\$pegawai) {
        echo 'STEP 1 FAILED: Pegawai not found\n';
        exit();
    }
    echo 'STEP 1 SUCCESS: Pegawai found\n';
    
    // Step 2: Check active status
    if (!\$pegawai->aktif) {
        echo 'STEP 2 FAILED: Pegawai not active\n';
        exit();
    }
    echo 'STEP 2 SUCCESS: Pegawai is active\n';
    
    // Step 3: Role mapping (potential issue here)
    \$roleName = match(\$pegawai->jenis_pegawai) {
        'Paramedis' => 'paramedis',
        'Non-Paramedis' => 'non_paramedis',
        default => 'petugas'
    };
    echo 'STEP 3 SUCCESS: Role mapped - ' . \$pegawai->jenis_pegawai . ' -> ' . \$roleName . '\n';
    
    // Step 4: Find role (potential failure point)
    \$role = \Spatie\Permission\Models\Role::where('name', \$roleName)->first();
    if (!\$role) {
        echo 'STEP 4 FAILED: Role \"' . \$roleName . '\" not found in database\n';
        echo 'Available roles: ';
        \$allRoles = \Spatie\Permission\Models\Role::all();
        foreach (\$allRoles as \$r) {
            echo \$r->name . ', ';
        }
        echo '\n';
        exit();
    }
    echo 'STEP 4 SUCCESS: Role found - ' . \$role->name . ' (ID: ' . \$role->id . ')\n';
    
    // Step 5: Check existing user
    \$existingUser = \App\Models\User::where('username', \$pegawai->username)->first();
    if (\$existingUser) {
        echo 'STEP 5: Existing user found - ' . \$existingUser->name . '\n';
    } else {
        echo 'STEP 5: Creating new user...\n';
        
        // This is where the error likely occurs
        \$userEmail = \$pegawai->nik . '@pegawai.local';
        
        // Check for email conflicts
        \$emailConflict = \App\Models\User::where('email', \$userEmail)->exists();
        if (\$emailConflict) {
            echo 'STEP 5 FAILED: Email conflict - ' . \$userEmail . ' already exists\n';
            exit();
        }
        
        try {
            \$user = \App\Models\User::create([
                'name' => \$pegawai->nama_lengkap,
                'username' => \$pegawai->username,
                'email' => \$userEmail,
                'role_id' => \$role->id,
                'is_active' => \$pegawai->aktif,
                'password' => \$pegawai->password,
            ]);
            echo 'STEP 5 SUCCESS: User created - ID: ' . \$user->id . '\n';
        } catch (Exception \$e) {
            echo 'STEP 5 FAILED: User creation error - ' . \$e->getMessage() . '\n';
            echo 'SQL State: ' . (\$e->getCode() ?? 'unknown') . '\n';
            exit();
        }
    }
    
    echo 'LOGIN SIMULATION COMPLETED SUCCESSFULLY\n';
    echo 'No errors found in authentication flow\n';
    
} catch (Exception \$e) {
    echo 'CRITICAL ERROR DURING SIMULATION:\n';
    echo 'Message: ' . \$e->getMessage() . '\n';
    echo 'File: ' . \$e->getFile() . ':' . \$e->getLine() . '\n';
    echo 'Code: ' . \$e->getCode() . '\n';
    echo 'Stack trace:\n' . \$e->getTraceAsString() . '\n';
} catch (Error \$e) {
    echo 'FATAL ERROR DURING SIMULATION:\n';
    echo 'Message: ' . \$e->getMessage() . '\n';
    echo 'File: ' . \$e->getFile() . ':' . \$e->getLine() . '\n';
    echo 'Stack trace:\n' . \$e->getTraceAsString() . '\n';
}
"
EOF

print_section "5. DATABASE CONNECTION TEST"
sshpass -p "$SSH_PASSWORD" ssh -o StrictHostKeyChecking=no "$REMOTE_USER@$REMOTE_HOST" << 'EOF'
cd /home/u196138154/domains/dokterkuklinik.com/public_html

echo "🗃️  Testing database operations that might cause 500 errors:"

php artisan tinker --execute="
try {
    // Test database connection
    \$pdo = \DB::connection()->getPdo();
    echo 'Database connection: SUCCESS\n';
    
    // Test migrations table
    \$migrations = \DB::table('migrations')->count();
    echo 'Migrations table accessible: YES (' . \$migrations . ' migrations)\n';
    
    // Test pegawais table access
    \$pegawaiCount = \DB::table('pegawais')->count();
    echo 'Pegawais table accessible: YES (' . \$pegawaiCount . ' records)\n';
    
    // Test users table access
    \$userCount = \DB::table('users')->count();
    echo 'Users table accessible: YES (' . \$userCount . ' records)\n';
    
    // Test roles table access
    \$roleCount = \DB::table('roles')->count();
    echo 'Roles table accessible: YES (' . \$roleCount . ' records)\n';
    
    // Test problematic columns
    \$emailColumn = \DB::select('SHOW COLUMNS FROM pegawais WHERE Field = \"email\"');
    echo 'Email column in pegawais: ' . (count(\$emailColumn) > 0 ? 'EXISTS' : 'MISSING') . '\n';
    
    if (count(\$emailColumn) > 0) {
        \$col = \$emailColumn[0];
        echo 'Email column details: ' . \$col->Type . ' ' . \$col->Null . ' ' . \$col->Key . '\n';
    }
    
} catch (Exception \$e) {
    echo 'DATABASE ERROR: ' . \$e->getMessage() . '\n';
    echo 'This might be the cause of 500 errors\n';
}
"
EOF

print_section "6. MEMORY AND RESOURCE CHECK"
sshpass -p "$SSH_PASSWORD" ssh -o StrictHostKeyChecking=no "$REMOTE_USER@$REMOTE_HOST" << 'EOF'
cd /home/u196138154/domains/dokterkuklinik.com/public_html

echo "💾 Memory and resource usage:"
echo "PHP memory limit: $(php -r "echo ini_get('memory_limit');")"
echo "PHP max execution time: $(php -r "echo ini_get('max_execution_time');")"
echo "PHP error reporting: $(php -r "echo ini_get('error_reporting');")"
echo "PHP display errors: $(php -r "echo ini_get('display_errors');")"

echo
echo "📁 Storage permissions:"
ls -la storage/ | head -5
echo
echo "📦 Bootstrap cache permissions:"
ls -la bootstrap/cache/ | head -5
EOF

echo
echo "🔍 LOG ANALYSIS COMPLETE"
echo "========================"
echo
print_info "Check the output above for:"
print_info "1. Specific error messages in Laravel logs"
print_info "2. PHP fatal errors or exceptions"
print_info "3. Database connection issues"
print_info "4. Permission problems"
print_info "5. Memory limit exceeded errors"
print_info "6. Missing database tables/columns"
echo
print_warning "If no clear errors found above, the issue might be:"
print_warning "- Frontend JavaScript errors"
print_warning "- HTTP server configuration"
print_warning "- SSL/HTTPS redirect issues"
print_warning "- Session storage problems"

# Clean up password
unset SSH_PASSWORD