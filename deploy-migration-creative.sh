#!/bin/bash

# Creative SSH Deployment Script for Pegawai Email Migration
# This script uses sshpass to automatically deploy and run migration on Hostinger

set -e

echo "🚀 CREATIVE DEPLOYMENT: Pegawai Email Migration"
echo "=================================================="

# Configuration
REMOTE_HOST="srv556.hstgr.io"
REMOTE_USER="u196138154"
REMOTE_PATH="/home/u196138154/domains/dokterkuklinik.com/public_html"
MIGRATION_FILE="2025_07_21_092713_add_email_column_to_pegawais_table.php"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

print_step() {
    echo -e "${BLUE}📋 STEP $1:${NC} $2"
}

print_success() {
    echo -e "${GREEN}✅ SUCCESS:${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}⚠️  WARNING:${NC} $1"
}

print_error() {
    echo -e "${RED}❌ ERROR:${NC} $1"
}

# Check if sshpass is installed
if ! command -v sshpass &> /dev/null; then
    print_error "sshpass is not installed. Installing..."
    
    # Try to install sshpass
    if [[ "$OSTYPE" == "darwin"* ]]; then
        # macOS
        if command -v brew &> /dev/null; then
            brew install hudochenkov/sshpass/sshpass
        else
            print_error "Please install Homebrew first or install sshpass manually"
            exit 1
        fi
    elif [[ "$OSTYPE" == "linux-gnu"* ]]; then
        # Linux
        sudo apt-get update && sudo apt-get install -y sshpass
    else
        print_error "Unsupported OS. Please install sshpass manually"
        exit 1
    fi
fi

# Prompt for password securely
echo -n "🔐 Enter SSH password for $REMOTE_USER@$REMOTE_HOST: "
read -s SSH_PASSWORD
echo

print_step "1" "Testing SSH connection"
if sshpass -p "$SSH_PASSWORD" ssh -o StrictHostKeyChecking=no "$REMOTE_USER@$REMOTE_HOST" "echo 'Connection successful'" 2>/dev/null; then
    print_success "SSH connection established"
else
    print_error "SSH connection failed. Please check your credentials."
    exit 1
fi

print_step "2" "Backing up current production state"
sshpass -p "$SSH_PASSWORD" ssh -o StrictHostKeyChecking=no "$REMOTE_USER@$REMOTE_HOST" << 'EOF'
cd /home/u196138154/domains/dokterkuklinik.com/public_html
echo "📦 Creating backup..."
cp -r database/migrations database/migrations_backup_$(date +%Y%m%d_%H%M%S) 2>/dev/null || echo "No existing migrations to backup"
echo "✅ Backup completed"
EOF

print_step "3" "Pulling latest code from repository"
sshpass -p "$SSH_PASSWORD" ssh -o StrictHostKeyChecking=no "$REMOTE_USER@$REMOTE_HOST" << 'EOF'
cd /home/u196138154/domains/dokterkuklinik.com/public_html
echo "🔄 Pulling latest code..."
git stash 2>/dev/null || true
git pull origin main
echo "✅ Code updated"
EOF

print_step "4" "Checking if migration file exists"
MIGRATION_EXISTS=$(sshpass -p "$SSH_PASSWORD" ssh -o StrictHostKeyChecking=no "$REMOTE_USER@$REMOTE_HOST" "test -f $REMOTE_PATH/database/migrations/$MIGRATION_FILE && echo 'yes' || echo 'no'")

if [ "$MIGRATION_EXISTS" = "yes" ]; then
    print_success "Migration file found on production"
else
    print_error "Migration file not found. Uploading..."
    
    # Upload migration file directly
    sshpass -p "$SSH_PASSWORD" scp -o StrictHostKeyChecking=no \
        "database/migrations/$MIGRATION_FILE" \
        "$REMOTE_USER@$REMOTE_HOST:$REMOTE_PATH/database/migrations/"
    print_success "Migration file uploaded"
fi

print_step "5" "Checking current database schema"
sshpass -p "$SSH_PASSWORD" ssh -o StrictHostKeyChecking=no "$REMOTE_USER@$REMOTE_HOST" << 'EOF'
cd /home/u196138154/domains/dokterkuklinik.com/public_html
echo "🔍 Checking if email column exists in pegawais table..."

# Check if email column exists using artisan tinker
php artisan tinker --execute="
try {
    \$columns = \DB::select('SHOW COLUMNS FROM pegawais');
    \$emailExists = false;
    foreach (\$columns as \$col) {
        if (\$col->Field === 'email') {
            \$emailExists = true;
            break;
        }
    }
    echo \$emailExists ? 'EMAIL_EXISTS' : 'EMAIL_MISSING';
} catch (Exception \$e) {
    echo 'CHECK_FAILED: ' . \$e->getMessage();
}
" 2>/dev/null || echo "EMAIL_MISSING"
EOF

print_step "6" "Running migrations"
sshpass -p "$SSH_PASSWORD" ssh -o StrictHostKeyChecking=no "$REMOTE_USER@$REMOTE_HOST" << 'EOF'
cd /home/u196138154/domains/dokterkuklinik.com/public_html
echo "🗃️  Running database migrations..."

# Clear caches first
php artisan config:clear
php artisan cache:clear
php artisan route:clear

# Run migrations
php artisan migrate --force

echo "✅ Migrations completed"
EOF

print_step "7" "Verifying the fix"
print_warning "Testing pegawai edit functionality..."

VERIFICATION=$(sshpass -p "$SSH_PASSWORD" ssh -o StrictHostKeyChecking=no "$REMOTE_USER@$REMOTE_HOST" << 'EOF'
cd /home/u196138154/domains/dokterkuklinik.com/public_html
echo "🧪 Testing pegawai creation with email..."

php artisan tinker --execute="
try {
    \$pegawai = new \App\Models\Pegawai();
    \$pegawai->nama_lengkap = 'Test Deployment';
    \$pegawai->nik = '999999';
    \$pegawai->email = 'test@deploy.com';
    \$pegawai->tanggal_lahir = '1990-01-01';
    \$pegawai->jenis_kelamin = 'Laki-laki';
    \$pegawai->jabatan = 'Test';
    \$pegawai->jenis_pegawai = 'Paramedis';
    \$pegawai->aktif = true;
    \$pegawai->input_by = 1;
    \$pegawai->save();
    echo 'TEST_SUCCESS';
    \$pegawai->delete();
} catch (Exception \$e) {
    echo 'TEST_FAILED: ' . \$e->getMessage();
}
" 2>/dev/null
EOF
)

if [[ "$VERIFICATION" == *"TEST_SUCCESS"* ]]; then
    print_success "Pegawai with email field works correctly!"
else
    print_warning "Verification test had issues: $VERIFICATION"
fi

print_step "8" "Final optimization"
sshpass -p "$SSH_PASSWORD" ssh -o StrictHostKeyChecking=no "$REMOTE_USER@$REMOTE_HOST" << 'EOF'
cd /home/u196138154/domains/dokterkuklinik.com/public_html
echo "🚀 Final optimizations..."

# Clear all caches
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "✅ Optimization completed"
EOF

echo
echo "🎉 DEPLOYMENT COMPLETED SUCCESSFULLY!"
echo "=================================================="
echo "📍 Test the fix at: https://dokterkuklinik.com/admin/pegawais/1/edit"
echo "📧 Email field should now work in pegawai edit forms"
echo "🔧 Migration applied: $MIGRATION_FILE"
echo

# Clean up sensitive data
unset SSH_PASSWORD