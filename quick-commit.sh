#!/bin/bash

# 🚀 Quick Commit with Auto-Fix
# Usage: ./quick-commit.sh "Your commit message"

set -e

COMMIT_MESSAGE="$1"

if [ -z "$COMMIT_MESSAGE" ]; then
    echo "Usage: ./quick-commit.sh 'Your commit message'"
    exit 1
fi

echo "🚀 Quick Commit with Auto-Fix System"
echo "===================================="
echo ""

# Check if we're in a git repo
if ! git rev-parse --git-dir > /dev/null 2>&1; then
    echo "❌ Not in a git repository!"
    exit 1
fi

# Stage all changes
echo "📝 Staging changes..."
git add .

# Show what will be committed
echo "📋 Files to be committed:"
git diff --staged --name-only | sed 's/^/  - /'
echo ""

# Commit with auto-fix trigger message
echo "💾 Creating commit..."
git commit -m "$COMMIT_MESSAGE

🤖 This commit will trigger automatic deployment and error fixing
🔧 Auto-fix will run if any tests fail
🚀 Deploy workflow will run automatically

[auto-fix-enabled]
[simple-deploy]

Generated with Quick Commit Script"

# Push to trigger workflow
echo "🚀 Pushing to trigger workflows..."
git push origin main

echo ""
echo "✅ Commit and push completed!"
echo "🔗 Monitor progress at: https://github.com/$(git remote get-url origin | sed 's/.*github.com[:/]\([^/]*\/[^/.]*\).*/\1/' | sed 's/\.git$//')/actions"
echo ""
echo "📋 What happens next:"
echo "1. 🧪 Tests will run automatically"
echo "2. 🔧 If tests fail, auto-fix will attempt repairs"
echo "3. 📝 Fixed code will be committed automatically"
echo "4. 🚀 Successful build will be deployed"
echo ""
echo "🎯 Use 'gh run list' to check workflow status"