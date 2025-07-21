#!/bin/bash

# Hostinger Admin Codebase Master Synchronization Script
# Executes complete admin codebase replacement from localhost to Hostinger

set -e  # Exit on any error

# Configuration
LOCAL_PATH="/Users/kym/Herd/Dokterku"
HOSTINGER_HOST="153.92.8.132"
HOSTINGER_PORT="65002"
HOSTINGER_USER="u454362045"

echo "=========================================="
echo "HOSTINGER ADMIN CODEBASE SYNCHRONIZATION"
echo "=========================================="
echo "🚀 Starting complete admin codebase replacement"
echo "📅 Timestamp: $(date)"
echo "📁 Local Path: $LOCAL_PATH"
echo "🌐 Remote Host: $HOSTINGER_HOST:$HOSTINGER_PORT"
echo "👤 Remote User: $HOSTINGER_USER"
echo "=========================================="

# Function to check if sshpass is installed
check_dependencies() {
    echo ""
    echo "🔍 Checking dependencies..."
    
    if ! command -v sshpass &> /dev/null; then
        echo "❌ Error: sshpass is required but not installed"
        echo "📦 Install with: brew install sshpass"
        exit 1
    fi
    
    if ! command -v curl &> /dev/null; then
        echo "❌ Error: curl is required but not installed"
        exit 1
    fi
    
    echo "✅ All dependencies satisfied"
}

# Function to make scripts executable
make_scripts_executable() {
    echo ""
    echo "🔧 Making synchronization scripts executable..."
    
    chmod +x "$LOCAL_PATH/hostinger-admin-backup.sh" 2>/dev/null || echo "⚠️  Backup script not found"
    chmod +x "$LOCAL_PATH/hostinger-admin-sync-controllers.sh" 2>/dev/null || echo "⚠️  Controllers sync script not found"
    chmod +x "$LOCAL_PATH/hostinger-admin-sync-views.sh" 2>/dev/null || echo "⚠️  Views sync script not found"  
    chmod +x "$LOCAL_PATH/hostinger-admin-cache-clear.sh" 2>/dev/null || echo "⚠️  Cache clear script not found"
    
    echo "✅ Scripts made executable"
}

# Function to execute script and check result
execute_script() {
    local script_path="$1"
    local script_name="$2"
    
    echo ""
    echo "================================================================"
    echo "🎯 EXECUTING: $script_name"
    echo "================================================================"
    
    if [ -f "$script_path" ]; then
        if bash "$script_path"; then
            echo ""
            echo "✅ SUCCESS: $script_name completed successfully"
            echo "================================================================"
        else
            echo ""
            echo "❌ ERROR: $script_name failed"
            echo "🛑 Stopping synchronization process"
            echo "================================================================"
            exit 1
        fi
    else
        echo "❌ ERROR: Script not found: $script_path"
        exit 1
    fi
}

# Function to create master summary
create_master_summary() {
    local sync_timestamp="$1"
    
    echo ""
    echo "📝 Creating master synchronization summary..."
    
    cat > "admin_sync_master_summary_${sync_timestamp}.txt" << EOF
HOSTINGER ADMIN CODEBASE SYNCHRONIZATION SUMMARY
================================================

Master Sync Timestamp: $(date)
Local Source Path: $LOCAL_PATH
Remote Target: $HOSTINGER_USER@$HOSTINGER_HOST:$HOSTINGER_PORT

SYNCHRONIZATION PHASES COMPLETED:
=================================

✅ Phase 1: Backup Creation
   - Backed up existing admin files on Hostinger
   - Created timestamped backup directory
   - Preserved original .env and configurations

✅ Phase 2: Controllers & Middleware Sync
   - Replaced all HTTP controllers
   - Updated admin middleware  
   - Synced complete Filament admin structure
   - Updated routes and service providers

✅ Phase 3: Views & Assets Sync
   - Replaced admin and Filament views
   - Updated all view components and layouts
   - Synced JavaScript and CSS assets
   - Updated build and vendor assets

✅ Phase 4: Cache Clear & Verification
   - Cleared all Laravel caches
   - Optimized production configurations
   - Set proper file permissions
   - Verified admin panel accessibility

COMPONENTS SYNCHRONIZED:
=======================
- app/Http/Controllers/ (All controller files)
- app/Http/Middleware/ (All middleware files)  
- app/Filament/ (Complete admin panel structure)
- app/Providers/ (Service providers)
- routes/ (Route definitions)
- resources/views/admin/ (Admin views)
- resources/views/filament/ (Filament views)
- resources/views/components/ (View components)
- resources/views/layouts/ (Layout templates)
- public/js/ (JavaScript assets)
- public/css/ (CSS assets)
- public/build/ (Build assets)
- resources/css/ (CSS resources)  
- resources/js/ (JavaScript resources)

FINAL STATUS: ADMIN CODEBASE REPLACEMENT COMPLETED
==================================================

The admin codebase has been completely replaced with the localhost version.
All caches have been cleared and the system optimized for production.

Next Steps:
1. Test admin panel login functionality
2. Verify all admin features work correctly
3. Monitor application logs for any issues
4. Update any environment-specific configurations if needed

Backup Location: Available in timestamped backup directories
Support: Check individual phase summary files for detailed logs

EOF

    echo "✅ Master summary created: admin_sync_master_summary_${sync_timestamp}.txt"
}

# Main execution flow
main() {
    local sync_timestamp=$(date +%Y%m%d_%H%M%S)
    
    echo ""
    echo "🚀 STARTING ADMIN CODEBASE SYNCHRONIZATION"
    echo "==========================================="
    
    # Check dependencies
    check_dependencies
    
    # Make scripts executable
    make_scripts_executable
    
    # Change to local directory
    cd "$LOCAL_PATH"
    
    # Phase 1: Backup existing files
    execute_script "./hostinger-admin-backup.sh" "Admin Files Backup"
    
    # Phase 2: Sync controllers and middleware
    execute_script "./hostinger-admin-sync-controllers.sh" "Controllers & Middleware Sync"
    
    # Phase 3: Sync views and assets
    execute_script "./hostinger-admin-sync-views.sh" "Views & Assets Sync"
    
    # Phase 4: Clear caches and verify
    execute_script "./hostinger-admin-cache-clear.sh" "Cache Clear & Verification"
    
    # Create master summary
    create_master_summary "$sync_timestamp"
    
    # Final success message
    echo ""
    echo "🎉 ADMIN CODEBASE SYNCHRONIZATION COMPLETED!"
    echo "============================================"
    echo "✅ All phases executed successfully"
    echo "📁 Check summary files for detailed results"
    echo "🌐 Admin panel: https://dokterkuklinik.com/admin"
    echo ""
    echo "🔗 Quick Access URLs:"
    echo "   📊 Admin Dashboard: https://dokterkuklinik.com/admin"
    echo "   🔐 Admin Login: https://dokterkuklinik.com/admin/login"
    echo ""
    echo "📋 Summary Files Created:"
    ls -la *summary*.txt 2>/dev/null | tail -5 || echo "   Check current directory for summary files"
    echo ""
    echo "✨ Admin codebase replacement successful!"
    echo "============================================"
}

# Execute main function
main "$@"