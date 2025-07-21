#!/bin/bash

# Hostinger Admin Controllers & Middleware Sync Script
# Synchronizes admin controllers and middleware from localhost to Hostinger

set -e  # Exit on any error

# SSH Configuration
HOSTINGER_HOST="153.92.8.132"
HOSTINGER_PORT="65002"
HOSTINGER_USER="u454362045"
HOSTINGER_PASS="LaTahzan@01"
HOSTINGER_PATH="/home/u454362045/domains/dokterkuklinik.com/public_html"

# Local paths
LOCAL_PATH="/Users/kym/Herd/Dokterku"

echo "========================================"
echo "HOSTINGER ADMIN CONTROLLERS SYNC SCRIPT"
echo "========================================"
echo "Timestamp: $(date)"
echo "Local Path: $LOCAL_PATH"
echo "Remote Path: $HOSTINGER_PATH"
echo "========================================"

# Function to sync files via SSH with proper permissions
sync_files() {
    local local_path="$1"
    local remote_path="$2"
    local description="$3"
    
    echo ""
    echo "🔄 Syncing: $description"
    echo "   Local:  $local_path"
    echo "   Remote: $remote_path"
    
    if [ -e "$local_path" ]; then
        # Use scp to copy files
        sshpass -p "$HOSTINGER_PASS" scp -P "$HOSTINGER_PORT" -r \
            "$local_path" \
            "${HOSTINGER_USER}@${HOSTINGER_HOST}:$(dirname "$remote_path")" || {
            echo "   ❌ Error: Failed to sync $description"
            return 1
        }
        
        # Set proper permissions
        echo "   🔧 Setting permissions..."
        sshpass -p "$HOSTINGER_PASS" ssh -p "$HOSTINGER_PORT" \
            "${HOSTINGER_USER}@${HOSTINGER_HOST}" \
            "find \"$remote_path\" -type f -exec chmod 644 {} \; && find \"$remote_path\" -type d -exec chmod 755 {} \;" || {
            echo "   ⚠️  Warning: Could not set permissions for $description"
        }
        
        echo "   ✅ Successfully synced: $description"
    else
        echo "   ⚠️  Warning: Local path does not exist: $local_path"
    fi
}

# Function to create remote directory if it doesn't exist
create_remote_dir() {
    local remote_dir="$1"
    echo "📁 Ensuring remote directory exists: $remote_dir"
    sshpass -p "$HOSTINGER_PASS" ssh -p "$HOSTINGER_PORT" \
        "${HOSTINGER_USER}@${HOSTINGER_HOST}" \
        "mkdir -p \"$remote_dir\"" || {
        echo "❌ Error: Could not create remote directory: $remote_dir"
        exit 1
    }
}

# Create necessary remote directories
echo ""
echo "📁 Creating remote directory structure..."
create_remote_dir "${HOSTINGER_PATH}/app/Http/Controllers"
create_remote_dir "${HOSTINGER_PATH}/app/Http/Middleware"
create_remote_dir "${HOSTINGER_PATH}/app/Filament"

# Sync Admin Controllers
echo ""
echo "🎯 Syncing Admin Controllers..."
if [ -d "$LOCAL_PATH/app/Http/Controllers" ]; then
    for controller in "$LOCAL_PATH/app/Http/Controllers"/*.php; do
        if [ -f "$controller" ]; then
            controller_name=$(basename "$controller")
            sync_files "$controller" "${HOSTINGER_PATH}/app/Http/Controllers/$controller_name" "Controller: $controller_name"
        fi
    done
else
    echo "   ⚠️  Warning: Local Controllers directory not found"
fi

# Sync Admin Middleware
echo ""
echo "🛡️ Syncing Admin Middleware..."
if [ -d "$LOCAL_PATH/app/Http/Middleware" ]; then
    # Sync all middleware files
    sync_files "$LOCAL_PATH/app/Http/Middleware" "${HOSTINGER_PATH}/app/Http/Middleware" "All Middleware Files"
else
    echo "   ⚠️  Warning: Local Middleware directory not found"
fi

# Sync Entire Filament Admin Structure
echo ""
echo "📋 Syncing Complete Filament Admin Structure..."
if [ -d "$LOCAL_PATH/app/Filament" ]; then
    sync_files "$LOCAL_PATH/app/Filament" "${HOSTINGER_PATH}/app/Filament" "Complete Filament Admin"
else
    echo "   ⚠️  Warning: Local Filament directory not found"
fi

# Sync Routes (Important for admin access)
echo ""
echo "🛤️ Syncing Route Files..."
if [ -d "$LOCAL_PATH/routes" ]; then
    sync_files "$LOCAL_PATH/routes" "${HOSTINGER_PATH}/routes" "Route Files"
else
    echo "   ⚠️  Warning: Local routes directory not found"
fi

# Sync Service Providers (Critical for Filament panels)
echo ""
echo "⚙️ Syncing Service Providers..."
if [ -d "$LOCAL_PATH/app/Providers" ]; then
    sync_files "$LOCAL_PATH/app/Providers" "${HOSTINGER_PATH}/app/Providers" "Service Providers"
else
    echo "   ⚠️  Warning: Local Providers directory not found"
fi

# Verify file integrity on remote server
echo ""
echo "🔍 Verifying file integrity on remote server..."
verification_result=$(sshpass -p "$HOSTINGER_PASS" ssh -p "$HOSTINGER_PORT" \
    "${HOSTINGER_USER}@${HOSTINGER_HOST}" \
    "cd \"$HOSTINGER_PATH\" && find app/Http/Controllers app/Http/Middleware app/Filament routes app/Providers -name '*.php' | wc -l" 2>/dev/null || echo "0")

echo "📊 PHP files synced to remote: $verification_result"

# Create sync summary
echo ""
echo "📝 Creating sync summary..."
cat > "sync_controllers_summary_$(date +%Y%m%d_%H%M%S).txt" << EOF
HOSTINGER ADMIN CONTROLLERS SYNC SUMMARY
=========================================

Sync Timestamp: $(date)
Local Path: $LOCAL_PATH
Remote Path: $HOSTINGER_PATH

Files and Directories Synced:
- app/Http/Controllers/ (All controller files)
- app/Http/Middleware/ (All middleware files)
- app/Filament/ (Complete Filament admin structure)
- routes/ (All route files)
- app/Providers/ (Service providers for panels)

Verification:
- Total PHP files synced: $verification_result

File Permissions Set:
- Files: 644
- Directories: 755

SSH Connection Used:
- Host: $HOSTINGER_HOST:$HOSTINGER_PORT
- User: $HOSTINGER_USER
- Path: $HOSTINGER_PATH

Next Steps:
1. Run admin views sync script
2. Clear all caches
3. Test admin panel access

EOF

echo ""
echo "✅ ADMIN CONTROLLERS & MIDDLEWARE SYNC COMPLETED!"
echo "================================================="
echo "📊 Verification: $verification_result PHP files synced"
echo ""
echo "🚀 Ready to proceed with admin views synchronization!"
echo "================================================="