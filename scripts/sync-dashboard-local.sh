#!/bin/bash

# Script untuk sync dashboard dari local ke production via git
# Usage: ./scripts/sync-dashboard-local.sh

echo "🚀 Dashboard Sync Tool"
echo "===================="

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check if we're in git repository
if ! git rev-parse --git-dir > /dev/null 2>&1; then
    echo -e "${RED}❌ Error: Not in a git repository${NC}"
    exit 1
fi

# Function to find dashboard files
find_dashboard_files() {
    echo -e "${YELLOW}📋 Finding dashboard files...${NC}"
    
    # Find all dashboard-related files
    dashboard_files=$(find . -type f \( \
        -path "*/resources/views/*dashboard*.blade.php" -o \
        -path "*/app/Http/Controllers/*Dashboard*.php" -o \
        -path "*/app/Filament/*Dashboard*.php" -o \
        -path "*/resources/react/*dashboard*/*" -o \
        -path "*/resources/css/*dashboard*.css" -o \
        -path "*/public/css/*dashboard*.css" \
    \) 2>/dev/null | grep -v node_modules | grep -v vendor | sort)
    
    if [ -z "$dashboard_files" ]; then
        echo -e "${RED}❌ No dashboard files found${NC}"
        return 1
    fi
    
    echo -e "${GREEN}✅ Found dashboard files:${NC}"
    echo "$dashboard_files" | head -20
    
    total_count=$(echo "$dashboard_files" | wc -l)
    if [ $total_count -gt 20 ]; then
        echo "... and $((total_count - 20)) more files"
    fi
    echo ""
    
    return 0
}

# Function to check git status
check_git_status() {
    echo -e "${YELLOW}🔍 Checking git status...${NC}"
    
    # Check for uncommitted changes
    if ! git diff-index --quiet HEAD --; then
        echo -e "${YELLOW}⚠️  You have uncommitted changes:${NC}"
        git status --short
        echo ""
        read -p "Do you want to commit these changes? (y/n): " -n 1 -r
        echo ""
        
        if [[ $REPLY =~ ^[Yy]$ ]]; then
            read -p "Enter commit message: " commit_msg
            git add .
            git commit -m "$commit_msg"
            echo -e "${GREEN}✅ Changes committed${NC}"
        else
            echo -e "${RED}❌ Please commit or stash your changes first${NC}"
            exit 1
        fi
    else
        echo -e "${GREEN}✅ Working directory clean${NC}"
    fi
}

# Function to sync with remote
sync_with_remote() {
    echo -e "${YELLOW}🔄 Syncing with remote...${NC}"
    
    # Pull latest changes
    echo "Pulling latest changes..."
    if git pull origin main; then
        echo -e "${GREEN}✅ Successfully pulled latest changes${NC}"
    else
        echo -e "${RED}❌ Failed to pull changes${NC}"
        echo "Please resolve conflicts manually"
        exit 1
    fi
    
    # Push changes
    echo "Pushing changes..."
    if git push origin main; then
        echo -e "${GREEN}✅ Successfully pushed changes${NC}"
    else
        echo -e "${RED}❌ Failed to push changes${NC}"
        exit 1
    fi
}

# Function to trigger GitHub Actions
trigger_workflow() {
    echo -e "${YELLOW}🚀 Triggering dashboard sync workflow...${NC}"
    
    # Check if gh CLI is installed
    if ! command -v gh &> /dev/null; then
        echo -e "${YELLOW}⚠️  GitHub CLI not installed${NC}"
        echo "The workflow will trigger automatically on push"
        return
    fi
    
    # Trigger workflow manually
    read -p "Do you want to trigger full dashboard sync? (y/n): " -n 1 -r
    echo ""
    
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        echo "Triggering workflow..."
        if gh workflow run sync-dashboard.yml -f sync_all=true; then
            echo -e "${GREEN}✅ Workflow triggered successfully${NC}"
            echo "Check progress at: https://github.com/$GITHUB_REPOSITORY/actions"
        else
            echo -e "${YELLOW}⚠️  Could not trigger workflow manually${NC}"
            echo "The workflow will trigger automatically on push"
        fi
    fi
}

# Function to watch for changes
watch_dashboard_changes() {
    echo -e "${YELLOW}👁️  Watching for dashboard changes...${NC}"
    echo "Press Ctrl+C to stop"
    
    # Check if fswatch is installed
    if ! command -v fswatch &> /dev/null; then
        echo -e "${RED}❌ fswatch not installed${NC}"
        echo "Install with: brew install fswatch (macOS) or apt-get install fswatch (Linux)"
        exit 1
    fi
    
    # Watch for changes
    fswatch -r -e ".*" -i ".*dashboard.*\.(blade\.php|php|jsx|css)$" \
        resources/ app/ public/css/ | while read file; do
        echo -e "${YELLOW}📝 Change detected: $file${NC}"
        
        # Auto-commit and push
        git add "$file"
        git commit -m "Update dashboard: $(basename $file)"
        git push origin main
        
        echo -e "${GREEN}✅ Changes pushed, workflow will trigger automatically${NC}"
    done
}

# Main menu
show_menu() {
    echo ""
    echo "What would you like to do?"
    echo "1) Find all dashboard files"
    echo "2) Sync dashboard changes now"
    echo "3) Watch for dashboard changes (auto-sync)"
    echo "4) Trigger full dashboard sync"
    echo "5) Exit"
    echo ""
    read -p "Select option (1-5): " choice
    
    case $choice in
        1)
            find_dashboard_files
            show_menu
            ;;
        2)
            find_dashboard_files
            check_git_status
            sync_with_remote
            trigger_workflow
            show_menu
            ;;
        3)
            watch_dashboard_changes
            ;;
        4)
            if command -v gh &> /dev/null; then
                gh workflow run sync-dashboard.yml -f sync_all=true
                echo -e "${GREEN}✅ Full sync triggered${NC}"
            else
                echo -e "${RED}❌ GitHub CLI not installed${NC}"
            fi
            show_menu
            ;;
        5)
            echo -e "${GREEN}👋 Goodbye!${NC}"
            exit 0
            ;;
        *)
            echo -e "${RED}❌ Invalid option${NC}"
            show_menu
            ;;
    esac
}

# Start
echo -e "${GREEN}Current branch: $(git branch --show-current)${NC}"
echo -e "${GREEN}Remote URL: $(git remote get-url origin)${NC}"
show_menu