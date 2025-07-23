#!/bin/bash

# 🔧 Trigger Auto-Fix via Dummy Commit
# This bypasses workflow_dispatch issues by creating a small commit

echo "🔧 Triggering Auto-Fix via commit method..."
echo ""

# Create or update trigger file
TRIGGER_FILE=".github/trigger-autofix"
TIMESTAMP=$(date '+%Y-%m-%d %H:%M:%S')

echo "Auto-fix triggered at: $TIMESTAMP" > "$TRIGGER_FILE"
echo "Trigger reason: Manual fix request" >> "$TRIGGER_FILE"
echo "User: $(git config user.name)" >> "$TRIGGER_FILE"

# Stage and commit
git add "$TRIGGER_FILE"
git commit -m "🔧 TRIGGER: Manual auto-fix request

Request auto-fix to run on current codebase.

Timestamp: $TIMESTAMP
Trigger: Manual request via script

[force-autofix]
[manual-trigger]"

# Push to trigger workflows
git push origin main

echo "✅ Auto-fix trigger committed and pushed!"
echo "🔗 Monitor at: https://github.com/$(git remote get-url origin | sed 's/.*github.com[:/]\([^/]*\/[^/.]*\).*/\1/' | sed 's/\.git$//')/actions"
echo ""
echo "🤖 Auto-fix will run as part of the deployment workflow"
echo "📋 Use 'gh run list' to monitor progress"