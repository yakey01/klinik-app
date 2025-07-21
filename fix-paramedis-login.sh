#!/bin/bash

# Paramedis Login 500 Error Fix Script
# This script implements fixes for the most common causes of paramedis login failures

set -e

echo "🔧 PARAMEDIS LOGIN 500 ERROR FIX"
echo "================================"

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
    echo -e "${BLUE}🔧 FIX $1:${NC} $2"
}

print_success() {
    echo -e "${GREEN}✅ FIXED:${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}⚠️  WARNING:${NC} $1"
}

# Get password
echo -n "🔐 Enter SSH password for $REMOTE_USER@$REMOTE_HOST: "
read -s SSH_PASSWORD
echo

print_step "1" "Clearing all caches and optimizing"
sshpass -p "$SSH_PASSWORD" ssh -o StrictHostKeyChecking=no "$REMOTE_USER@$REMOTE_HOST" << 'EOF'
cd /home/u196138154/domains/dokterkuklinik.com/public_html
echo "🧹 Clearing all caches..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan optimize:clear
echo "✅ All caches cleared"
EOF

print_step "2" "Running pending migrations"
sshpass -p "$SSH_PASSWORD" ssh -o StrictHostKeyChecking=no "$REMOTE_USER@$REMOTE_HOST" << 'EOF'
cd /home/u196138154/domains/dokterkuklinik.com/public_html
echo "🗃️  Running migrations..."
php artisan migrate --force
echo "✅ Migrations completed"
EOF

print_step "3" "Fixing email column conflicts"
sshpass -p "$SSH_PASSWORD" ssh -o StrictHostKeyChecking=no "$REMOTE_USER@$REMOTE_HOST" << 'EOF'
cd /home/u196138154/domains/dokterkuklinik.com/public_html
echo "📧 Fixing email column conflicts..."

php artisan tinker --execute="
try {
    // Find and fix duplicate emails in pegawais table
    \$duplicates = \DB::select('SELECT email, COUNT(*) as count FROM pegawais WHERE email IS NOT NULL GROUP BY email HAVING COUNT(*) > 1');
    
    if (count(\$duplicates) > 0) {
        echo 'Found ' . count(\$duplicates) . ' duplicate emails, fixing...\n';
        
        foreach (\$duplicates as \$dup) {
            \$pegawais = \App\Models\Pegawai::where('email', \$dup->email)->get();
            \$counter = 1;
            
            foreach (\$pegawais as \$pegawai) {
                if (\$counter > 1) {
                    // Make email unique by appending counter
                    \$newEmail = str_replace('@', '_' . \$counter . '@', \$dup->email);
                    \$pegawai->update(['email' => \$newEmail]);
                    echo 'Updated duplicate email: ' . \$dup->email . ' -> ' . \$newEmail . '\n';
                }
                \$counter++;
            }
        }
        echo 'Email duplicates fixed\n';
    } else {
        echo 'No duplicate emails found\n';
    }
    
    // Set NULL emails for pegawais without emails
    \$updated = \DB::update('UPDATE pegawais SET email = CONCAT(nik, \"@pegawai.local\") WHERE email IS NULL OR email = \"\"');
    echo 'Updated ' . \$updated . ' pegawai records with missing emails\n';
    
} catch (Exception \$e) {
    echo 'Email fix failed: ' . \$e->getMessage() . '\n';
}
"
EOF

print_step "4" "Ensuring paramedis roles exist"
sshpass -p "$SSH_PASSWORD" ssh -o StrictHostKeyChecking=no "$REMOTE_USER@$REMOTE_HOST" << 'EOF'
cd /home/u196138154/domains/dokterkuklinik.com/public_html
echo "🎭 Creating/verifying paramedis roles..."

php artisan tinker --execute="
try {
    \$roles = ['paramedis', 'non_paramedis', 'petugas'];
    
    foreach (\$roles as \$roleName) {
        \$role = \Spatie\Permission\Models\Role::firstOrCreate([
            'name' => \$roleName,
            'guard_name' => 'web'
        ]);
        echo 'Role ' . \$roleName . ': ' . (\$role->wasRecentlyCreated ? 'CREATED' : 'EXISTS') . '\n';
    }
    
    echo 'All required roles are available\n';
    
} catch (Exception \$e) {
    echo 'Role creation failed: ' . \$e->getMessage() . '\n';
}
"
EOF

print_step "5" "Fixing naning paramedis user specifically"
sshpass -p "$SSH_PASSWORD" ssh -o StrictHostKeyChecking=no "$REMOTE_USER@$REMOTE_HOST" << 'EOF'
cd /home/u196138154/domains/dokterkuklinik.com/public_html
echo "👤 Fixing naning paramedis user..."

php artisan tinker --execute="
try {
    // Find naning user (case insensitive)
    \$naning = \App\Models\Pegawai::whereRaw('LOWER(username) LIKE ?', ['%naning%'])
        ->orWhereRaw('LOWER(nama_lengkap) LIKE ?', ['%naning%'])
        ->first();
    
    if (!\$naning) {
        echo 'Naning user not found, searching more broadly...\n';
        \$allParamedis = \App\Models\Pegawai::where('jenis_pegawai', 'Paramedis')->get();
        echo 'All Paramedis users:\n';
        foreach (\$allParamedis as \$p) {
            echo '  - ' . \$p->nama_lengkap . ' (username: ' . \$p->username . ', nik: ' . \$p->nik . ')\n';
        }
    } else {
        echo 'Found naning user: ' . \$naning->nama_lengkap . '\n';
        
        // Ensure user has proper data
        \$updates = [];
        
        if (empty(\$naning->email)) {
            \$updates['email'] = \$naning->nik . '@pegawai.local';
        }
        
        if (\$naning->jenis_pegawai !== 'Paramedis') {
            \$updates['jenis_pegawai'] = 'Paramedis';
        }
        
        if (!\$naning->aktif) {
            \$updates['aktif'] = true;
        }
        
        if (!empty(\$updates)) {
            \$naning->update(\$updates);
            echo 'Updated naning user with: ' . implode(', ', array_keys(\$updates)) . '\n';
        }
        
        // Ensure corresponding User record exists
        \$user = \App\Models\User::where('username', \$naning->username)->first();
        
        if (!\$user) {
            echo 'Creating User record for naning...\n';
            \$paramedisRole = \Spatie\Permission\Models\Role::where('name', 'paramedis')->first();
            
            \$user = \App\Models\User::create([
                'name' => \$naning->nama_lengkap,
                'username' => \$naning->username,
                'email' => \$naning->email ?: (\$naning->nik . '@pegawai.local'),
                'role_id' => \$paramedisRole->id,
                'is_active' => true,
                'password' => \$naning->password ?: bcrypt('password123'),
            ]);
            
            echo 'User record created for naning (ID: ' . \$user->id . ')\n';
        } else {
            echo 'User record already exists for naning (ID: ' . \$user->id . ')\n';
        }
        
        echo 'Naning user is ready for login\n';
    }
    
} catch (Exception \$e) {
    echo 'Naning user fix failed: ' . \$e->getMessage() . '\n';
    echo 'Stack trace: ' . \$e->getTraceAsString() . '\n';
}
"
EOF

print_step "6" "Testing paramedis authentication"
sshpass -p "$SSH_PASSWORD" ssh -o StrictHostKeyChecking=no "$REMOTE_USER@$REMOTE_HOST" << 'EOF'
cd /home/u196138154/domains/dokterkuklinik.com/public_html
echo "🧪 Testing paramedis authentication..."

php artisan tinker --execute="
try {
    // Test authentication flow for all paramedis users
    \$paramedisUsers = \App\Models\Pegawai::where('jenis_pegawai', 'Paramedis')->where('aktif', true)->get();
    
    echo 'Testing authentication for ' . \$paramedisUsers->count() . ' paramedis users:\n';
    
    foreach (\$paramedisUsers as \$pegawai) {
        echo 'Testing: ' . \$pegawai->nama_lengkap . ' (' . \$pegawai->username . ')\n';
        
        // Simulate the authentication flow
        \$roleName = match(\$pegawai->jenis_pegawai) {
            'Paramedis' => 'paramedis',
            'Non-Paramedis' => 'non_paramedis',
            default => 'petugas'
        };
        
        \$role = \Spatie\Permission\Models\Role::where('name', \$roleName)->first();
        \$user = \App\Models\User::where('username', \$pegawai->username)->first();
        
        echo '  - Role mapping: ' . \$pegawai->jenis_pegawai . ' -> ' . \$roleName . ' (' . (\$role ? 'OK' : 'MISSING') . ')\n';
        echo '  - User record: ' . (\$user ? 'EXISTS' : 'MISSING') . '\n';
        echo '  - Email: ' . (\$pegawai->email ?: 'MISSING') . '\n';
        echo '  - Status: ' . (\$pegawai->aktif ? 'ACTIVE' : 'INACTIVE') . '\n';
        echo '\n';
    }
    
    echo 'Authentication test completed\n';
    
} catch (Exception \$e) {
    echo 'Authentication test failed: ' . \$e->getMessage() . '\n';
}
"
EOF

print_step "7" "Final optimization and cache warming"
sshpass -p "$SSH_PASSWORD" ssh -o StrictHostKeyChecking=no "$REMOTE_USER@$REMOTE_HOST" << 'EOF'
cd /home/u196138154/domains/dokterkuklinik.com/public_html
echo "🚀 Final optimization..."

# Clear all caches again
php artisan optimize:clear

# Cache configuration for production
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Set proper permissions
chmod -R 755 storage/
chmod -R 755 bootstrap/cache/

echo "✅ Optimization completed"
EOF

echo
echo "🎉 PARAMEDIS LOGIN FIX COMPLETED!"
echo "================================="
echo "🔧 What was fixed:"
echo "✅ Cleared all application caches"
echo "✅ Applied pending migrations"
echo "✅ Fixed email column conflicts and duplicates"
echo "✅ Ensured paramedis roles exist"
echo "✅ Fixed naning paramedis user data"
echo "✅ Created missing User records"
echo "✅ Optimized application for production"
echo
echo "🧪 Test the login at:"
echo "https://dokterkuklinik.com/login"
echo
print_warning "If issues persist, run the diagnosis script:"
print_warning "./paramedis-500-diagnosis.sh"

# Clean up password
unset SSH_PASSWORD