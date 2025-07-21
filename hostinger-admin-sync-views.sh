#!/bin/bash

# Hostinger Admin Views & Assets Sync Script
# Synchronizes admin views and assets from localhost to Hostinger

set -e  # Exit on any error

# SSH Configuration
HOSTINGER_HOST="153.92.8.132"
HOSTINGER_PORT="65002"
HOSTINGER_USER="u454362045"
HOSTINGER_PASS="LaTahzan@01"
HOSTINGER_PATH="/home/u454362045/domains/dokterkuklinik.com/public_html"

# Local paths
LOCAL_PATH="/Users/kym/Herd/Dokterku"

echo "===================================="
echo "HOSTINGER ADMIN VIEWS SYNC SCRIPT"
echo "===================================="
echo "Timestamp: $(date)"
echo "Local Path: $LOCAL_PATH"
echo "Remote Path: $HOSTINGER_PATH"
echo "===================================="

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
create_remote_dir "${HOSTINGER_PATH}/resources/views"
create_remote_dir "${HOSTINGER_PATH}/public/js"
create_remote_dir "${HOSTINGER_PATH}/public/css"

# Sync Admin Views
echo ""
echo "🎨 Syncing Admin Views..."

# Sync admin directory views
if [ -d "$LOCAL_PATH/resources/views/admin" ]; then
    sync_files "$LOCAL_PATH/resources/views/admin" "${HOSTINGER_PATH}/resources/views/admin" "Admin Views Directory"
fi

# Sync Filament views
if [ -d "$LOCAL_PATH/resources/views/filament" ]; then
    sync_files "$LOCAL_PATH/resources/views/filament" "${HOSTINGER_PATH}/resources/views/filament" "Filament Views Directory"
fi

# Sync all view components
if [ -d "$LOCAL_PATH/resources/views/components" ]; then
    sync_files "$LOCAL_PATH/resources/views/components" "${HOSTINGER_PATH}/resources/views/components" "View Components"
fi

# Sync layouts
if [ -d "$LOCAL_PATH/resources/views/layouts" ]; then
    sync_files "$LOCAL_PATH/resources/views/layouts" "${HOSTINGER_PATH}/resources/views/layouts" "Layout Views"
fi

# Sync Admin Assets
echo ""
echo "🎭 Syncing Admin Assets..."

# Sync all JavaScript files
if [ -d "$LOCAL_PATH/public/js" ]; then
    sync_files "$LOCAL_PATH/public/js" "${HOSTINGER_PATH}/public/js" "JavaScript Assets"
fi

# Sync all CSS files
if [ -d "$LOCAL_PATH/public/css" ]; then
    sync_files "$LOCAL_PATH/public/css" "${HOSTINGER_PATH}/public/css" "CSS Assets"
fi

# Sync images and other assets
if [ -d "$LOCAL_PATH/public/images" ]; then
    sync_files "$LOCAL_PATH/public/images" "${HOSTINGER_PATH}/public/images" "Image Assets"
fi

# Sync Resource Assets (CSS/JS source files)
echo ""
echo "📦 Syncing Resource Assets..."

# Sync CSS resources
if [ -d "$LOCAL_PATH/resources/css" ]; then
    sync_files "$LOCAL_PATH/resources/css" "${HOSTINGER_PATH}/resources/css" "CSS Resources"
fi

# Sync JS resources
if [ -d "$LOCAL_PATH/resources/js" ]; then
    sync_files "$LOCAL_PATH/resources/js" "${HOSTINGER_PATH}/resources/js" "JavaScript Resources"
fi

# Sync Build Assets (if they exist)
echo ""
echo "🏗️ Syncing Build Assets..."

# Sync build directory
if [ -d "$LOCAL_PATH/public/build" ]; then
    sync_files "$LOCAL_PATH/public/build" "${HOSTINGER_PATH}/public/build" "Build Assets"
fi

# Sync vendor assets (Filament, etc.)
if [ -d "$LOCAL_PATH/public/vendor" ]; then
    sync_files "$LOCAL_PATH/public/vendor" "${HOSTINGER_PATH}/public/vendor" "Vendor Assets"
fi

# Sync Configuration Files for Assets
echo ""
echo "⚙️ Syncing Asset Configuration..."

# Sync package.json and related files
if [ -f "$LOCAL_PATH/package.json" ]; then
    sync_files "$LOCAL_PATH/package.json" "${HOSTINGER_PATH}/package.json" "Package.json"
fi

if [ -f "$LOCAL_PATH/vite.config.js" ]; then
    sync_files "$LOCAL_PATH/vite.config.js" "${HOSTINGER_PATH}/vite.config.js" "Vite Configuration"
fi

if [ -f "$LOCAL_PATH/tailwind.config.js" ]; then
    sync_files "$LOCAL_PATH/tailwind.config.js" "${HOSTINGER_PATH}/tailwind.config.js" "Tailwind Configuration"
fi

# Verify file counts on remote server
echo ""
echo "🔍 Verifying file integrity on remote server..."

# Count view files
view_files=$(sshpass -p "$HOSTINGER_PASS" ssh -p "$HOSTINGER_PORT" \
    "${HOSTINGER_USER}@${HOSTINGER_HOST}" \
    "cd \"$HOSTINGER_PATH\" && find resources/views -name '*.blade.php' | wc -l" 2>/dev/null || echo "0")

# Count asset files
js_files=$(sshpass -p "$HOSTINGER_PASS" ssh -p "$HOSTINGER_PORT" \
    "${HOSTINGER_USER}@${HOSTINGER_HOST}" \
    "cd \"$HOSTINGER_PATH\" && find public/js -name '*.js' | wc -l" 2>/dev/null || echo "0")

css_files=$(sshpass -p "$HOSTINGER_PASS" ssh -p "$HOSTINGER_PORT" \
    "${HOSTINGER_USER}@${HOSTINGER_HOST}" \
    "cd \"$HOSTINGER_PATH\" && find public/css -name '*.css' | wc -l" 2>/dev/null || echo "0")

echo "📊 Blade template files synced: $view_files"
echo "📊 JavaScript files synced: $js_files"  
echo "📊 CSS files synced: $css_files"

# Create sync summary
echo ""
echo "📝 Creating sync summary..."
cat > "sync_views_summary_$(date +%Y%m%d_%H%M%S).txt" << EOF
HOSTINGER ADMIN VIEWS & ASSETS SYNC SUMMARY
===========================================

Sync Timestamp: $(date)
Local Path: $LOCAL_PATH
Remote Path: $HOSTINGER_PATH

Directories and Files Synced:
- resources/views/admin/ (Admin views)
- resources/views/filament/ (Filament views)  
- resources/views/components/ (View components)
- resources/views/layouts/ (Layout views)
- public/js/ (JavaScript assets)
- public/css/ (CSS assets)
- public/images/ (Image assets)
- public/build/ (Build assets)
- public/vendor/ (Vendor assets)
- resources/css/ (CSS resources)
- resources/js/ (JavaScript resources)
- package.json (Package configuration)
- vite.config.js (Vite configuration)
- tailwind.config.js (Tailwind configuration)

Verification Results:
- Blade template files: $view_files
- JavaScript files: $js_files
- CSS files: $css_files

File Permissions Set:
- Files: 644
- Directories: 755

SSH Connection Used:
- Host: $HOSTINGER_HOST:$HOSTINGER_PORT
- User: $HOSTINGER_USER
- Path: $HOSTINGER_PATH

Next Steps:
1. Run cache clearing script
2. Test admin panel functionality
3. Verify asset loading

EOF

echo ""
echo "✅ ADMIN VIEWS & ASSETS SYNC COMPLETED!"
echo "======================================="
echo "📊 View Files: $view_files | JS Files: $js_files | CSS Files: $css_files"
echo ""
echo "🚀 Ready to proceed with cache clearing!"
echo "======================================="