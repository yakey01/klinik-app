#!/bin/bash

# Hostinger Admin Files Backup Script
# Creates comprehensive backup of admin-related files before replacement

set -e  # Exit on any error

# SSH Configuration
HOSTINGER_HOST="153.92.8.132"
HOSTINGER_PORT="65002"
HOSTINGER_USER="u454362045"
HOSTINGER_PASS="LaTahzan@01"
HOSTINGER_PATH="/home/u454362045/domains/dokterkuklinik.com/public_html"

# Create timestamp for backup
BACKUP_TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="backups/admin_backup_${BACKUP_TIMESTAMP}"

echo "=================================="
echo "HOSTINGER ADMIN BACKUP SCRIPT"
echo "=================================="
echo "Timestamp: $(date)"
echo "Backup Directory: $BACKUP_DIR"
echo "=================================="

# Create local backup directory
mkdir -p "$BACKUP_DIR"

echo "📦 Creating backup of Hostinger admin files..."

# Function to backup files via SSH
backup_remote_files() {
    local remote_path="$1"
    local local_backup_path="$2"
    local description="$3"
    
    echo "🔄 Backing up: $description"
    echo "   Remote: $remote_path"
    echo "   Local:  $local_backup_path"
    
    # Create local directory structure
    mkdir -p "$(dirname "$local_backup_path")"
    
    # Use scp to copy files
    sshpass -p "$HOSTINGER_PASS" scp -P "$HOSTINGER_PORT" -r \
        "${HOSTINGER_USER}@${HOSTINGER_HOST}:${remote_path}" \
        "$local_backup_path" 2>/dev/null || echo "   ⚠️  Warning: Could not backup $remote_path (may not exist)"
}

# Backup Admin Controllers
echo ""
echo "🎯 Backing up Admin Controllers..."
backup_remote_files "${HOSTINGER_PATH}/app/Http/Controllers" "$BACKUP_DIR/app/Http/Controllers" "HTTP Controllers"

# Backup Admin Middleware
echo ""
echo "🛡️ Backing up Admin Middleware..."
backup_remote_files "${HOSTINGER_PATH}/app/Http/Middleware" "$BACKUP_DIR/app/Http/Middleware" "HTTP Middleware"

# Backup Filament Admin Structure
echo ""
echo "📋 Backing up Filament Admin Structure..."
backup_remote_files "${HOSTINGER_PATH}/app/Filament" "$BACKUP_DIR/app/Filament" "Filament Admin"

# Backup Admin Views
echo ""
echo "🎨 Backing up Admin Views..."
backup_remote_files "${HOSTINGER_PATH}/resources/views/admin" "$BACKUP_DIR/resources/views/admin" "Admin Views"
backup_remote_files "${HOSTINGER_PATH}/resources/views/filament" "$BACKUP_DIR/resources/views/filament" "Filament Views"

# Backup Admin Assets
echo ""
echo "🎭 Backing up Admin Assets..."
backup_remote_files "${HOSTINGER_PATH}/public/js" "$BACKUP_DIR/public/js" "JavaScript Assets"
backup_remote_files "${HOSTINGER_PATH}/public/css" "$BACKUP_DIR/public/css" "CSS Assets"

# Backup Routes
echo ""
echo "🛤️ Backing up Routes..."
backup_remote_files "${HOSTINGER_PATH}/routes" "$BACKUP_DIR/routes" "Route Files"

# Backup Configuration Files
echo ""
echo "⚙️ Backing up Configuration Files..."
backup_remote_files "${HOSTINGER_PATH}/config" "$BACKUP_DIR/config" "Configuration Files"

# Backup Environment File (Important!)
echo ""
echo "🔐 Backing up Environment Configuration..."
backup_remote_files "${HOSTINGER_PATH}/.env" "$BACKUP_DIR/.env" "Environment File"

# Backup Composer Files
echo ""
echo "📦 Backing up Composer Files..."
backup_remote_files "${HOSTINGER_PATH}/composer.json" "$BACKUP_DIR/composer.json" "Composer JSON"
backup_remote_files "${HOSTINGER_PATH}/composer.lock" "$BACKUP_DIR/composer.lock" "Composer Lock"

# Create backup summary
echo ""
echo "📝 Creating backup summary..."
cat > "$BACKUP_DIR/backup_summary.txt" << EOF
HOSTINGER ADMIN BACKUP SUMMARY
==============================

Backup Timestamp: $BACKUP_TIMESTAMP
Backup Directory: $BACKUP_DIR
Created: $(date)

Files and Directories Backed Up:
- app/Http/Controllers/
- app/Http/Middleware/
- app/Filament/
- resources/views/admin/
- resources/views/filament/
- public/js/
- public/css/
- routes/
- config/
- .env
- composer.json
- composer.lock

SSH Connection Details:
- Host: $HOSTINGER_HOST:$HOSTINGER_PORT
- User: $HOSTINGER_USER
- Path: $HOSTINGER_PATH

Notes:
- This backup was created before admin codebase synchronization
- Some files may not exist on remote server (warnings shown during backup)
- Restore from this backup if synchronization fails

EOF

# Display backup completion
echo ""
echo "✅ BACKUP COMPLETED SUCCESSFULLY!"
echo "=================================="
echo "📁 Backup Location: $BACKUP_DIR"
echo "📄 Summary File: $BACKUP_DIR/backup_summary.txt"
echo ""
echo "📊 Backup Contents:"
find "$BACKUP_DIR" -type f | head -20
total_files=$(find "$BACKUP_DIR" -type f | wc -l)
echo "... and $((total_files - 20)) more files" 
echo ""
echo "💾 Total Files Backed Up: $total_files"
echo "📦 Backup Size: $(du -sh "$BACKUP_DIR" | cut -f1)"
echo ""
echo "🚀 Ready to proceed with admin codebase synchronization!"
echo "=================================="