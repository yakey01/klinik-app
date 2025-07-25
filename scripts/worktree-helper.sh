#!/bin/bash

# Dokterku Worktree Management Helper
# This script helps manage the multiple worktrees for the Dokterku project

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Base directory
BASE_DIR="/Users/kym/Herd"
MAIN_REPO="Dokterku"

# Function to print colored output
print_status() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

print_header() {
    echo -e "${BLUE}=== $1 ===${NC}"
}

# Function to list all worktrees
list_worktrees() {
    print_header "Current Worktrees"
    git worktree list
}

# Function to create a new feature worktree
create_feature() {
    local feature_name=$1
    if [ -z "$feature_name" ]; then
        print_error "Feature name is required"
        echo "Usage: $0 feature <feature-name>"
        exit 1
    fi
    
    local branch_name="feature/$feature_name"
    local worktree_dir="../$MAIN_REPO-feature-$feature_name"
    
    print_status "Creating feature worktree: $worktree_dir"
    git worktree add "$worktree_dir" -b "$branch_name" develop
    
    print_status "Feature worktree created successfully!"
    print_status "Directory: $worktree_dir"
    print_status "Branch: $branch_name"
}

# Function to remove a worktree
remove_worktree() {
    local worktree_path=$1
    if [ -z "$worktree_path" ]; then
        print_error "Worktree path is required"
        echo "Usage: $0 remove <worktree-path>"
        exit 1
    fi
    
    print_warning "Removing worktree: $worktree_path"
    git worktree remove "$worktree_path"
    print_status "Worktree removed successfully!"
}

# Function to switch to a worktree
switch_to() {
    local worktree_name=$1
    if [ -z "$worktree_name" ]; then
        print_error "Worktree name is required"
        echo "Available worktrees:"
        list_worktrees
        exit 1
    fi
    
    local target_dir="../$MAIN_REPO-$worktree_name"
    if [ -d "$target_dir" ]; then
        print_status "Switching to: $target_dir"
        cd "$target_dir"
        pwd
    else
        print_error "Worktree not found: $target_dir"
        list_worktrees
        exit 1
    fi
}

# Function to sync all worktrees
sync_all() {
    print_header "Syncing All Worktrees"
    
    # Fetch latest changes
    print_status "Fetching latest changes..."
    git fetch origin
    
    # List all worktrees and their status
    print_status "Worktree status:"
    git worktree list
    
    print_status "Sync completed!"
}

# Function to clean up merged feature branches
cleanup_merged() {
    print_header "Cleaning Up Merged Feature Branches"
    
    # Get merged branches
    merged_branches=$(git branch --merged develop | grep "feature/" | grep -v "develop" | grep -v "main" || true)
    
    if [ -z "$merged_branches" ]; then
        print_status "No merged feature branches to clean up"
        return
    fi
    
    echo "Merged feature branches:"
    echo "$merged_branches"
    
    read -p "Do you want to delete these branches? (y/N): " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        echo "$merged_branches" | while read -r branch; do
            if [ -n "$branch" ]; then
                print_status "Deleting branch: $branch"
                git branch -d "$branch"
            fi
        done
        print_status "Cleanup completed!"
    else
        print_status "Cleanup cancelled"
    fi
}

# Function to show help
show_help() {
    echo "Dokterku Worktree Management Helper"
    echo
    echo "Usage: $0 <command> [options]"
    echo
    echo "Commands:"
    echo "  list                    List all worktrees"
    echo "  feature <name>          Create a new feature worktree"
    echo "  remove <path>           Remove a worktree"
    echo "  switch <name>           Switch to a worktree directory"
    echo "  sync                    Sync all worktrees with remote"
    echo "  cleanup                 Clean up merged feature branches"
    echo "  help                    Show this help message"
    echo
    echo "Examples:"
    echo "  $0 feature user-management"
    echo "  $0 switch develop"
    echo "  $0 remove ../Dokterku-feature-user-management"
    echo
}

# Main command processing
case "$1" in
    "list")
        list_worktrees
        ;;
    "feature")
        create_feature "$2"
        ;;
    "remove")
        remove_worktree "$2"
        ;;
    "switch")
        switch_to "$2"
        ;;
    "sync")
        sync_all
        ;;
    "cleanup")
        cleanup_merged
        ;;
    "help"|"--help"|"-h"|"")
        show_help
        ;;
    *)
        print_error "Unknown command: $1"
        show_help
        exit 1
        ;;
esac