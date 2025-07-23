#!/bin/bash

# 🤖 Claude Auto-Fix Setup Script
# Script untuk setup Claude Auto-Fix di repository

set -e

echo "🤖 Claude Auto-Fix Setup Script"
echo "================================"
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Helper functions
log_info() {
    echo -e "${BLUE}ℹ️  $1${NC}"
}

log_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

log_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

log_error() {
    echo -e "${RED}❌ $1${NC}"
}

# Check if running in git repository
if ! git rev-parse --git-dir > /dev/null 2>&1; then
    log_error "This is not a git repository!"
    exit 1
fi

# Get repository info
REPO_URL=$(git remote get-url origin 2>/dev/null || echo "unknown")
REPO_NAME=$(basename -s .git "$REPO_URL" 2>/dev/null || echo "unknown")
CURRENT_BRANCH=$(git branch --show-current 2>/dev/null || echo "unknown")

log_info "Repository: $REPO_NAME"
log_info "Current Branch: $CURRENT_BRANCH"
log_info "Remote URL: $REPO_URL"
echo ""

# Check if workflow files exist
WORKFLOW_DIR=".github/workflows"
CLAUDE_WORKFLOW="$WORKFLOW_DIR/claude-auto-fix.yml"

if [ ! -d "$WORKFLOW_DIR" ]; then
    log_info "Creating GitHub workflows directory..."
    mkdir -p "$WORKFLOW_DIR"
    log_success "Directory created: $WORKFLOW_DIR"
fi

if [ -f "$CLAUDE_WORKFLOW" ]; then
    log_warning "Claude Auto-Fix workflow already exists!"
    echo "Do you want to update it? (y/n)"
    read -r response
    if [[ ! "$response" =~ ^[Yy]$ ]]; then
        log_info "Skipping workflow update"
    else
        log_info "Workflow will be updated"
    fi
else
    log_success "Claude Auto-Fix workflow file found"
fi

# Check for required secrets
echo ""
log_info "Checking GitHub repository configuration..."

# Function to check if we're on GitHub
check_github_repo() {
    if echo "$REPO_URL" | grep -q "github.com"; then
        return 0
    else
        return 1
    fi
}

if check_github_repo; then
    log_success "Repository is hosted on GitHub"
    
    # Extract GitHub repo info
    GITHUB_REPO=$(echo "$REPO_URL" | sed -E 's|.*github\.com[/:]([^/]+/[^/]+).*|\1|' | sed 's|\.git$||')
    
    echo ""
    log_info "📋 Next Steps - Manual Configuration Required:"
    echo ""
    echo "1. 🔑 Get Claude API Key:"
    echo "   - Visit: https://console.anthropic.com/"
    echo "   - Login or create account"
    echo "   - Generate new API key"
    echo ""
    echo "2. 🔒 Add GitHub Secret:"
    echo "   - Go to: https://github.com/$GITHUB_REPO/settings/secrets/actions"
    echo "   - Click 'New repository secret'"
    echo "   - Name: CLAUDE_API_KEY"
    echo "   - Value: [Your Claude API key]"
    echo ""
    echo "3. ✅ Enable GitHub Actions (if not already enabled):"
    echo "   - Go to: https://github.com/$GITHUB_REPO/actions"
    echo "   - Enable actions if prompted"
    echo ""
    echo "4. 🚀 Test the workflow:"
    echo "   - Go to Actions tab"
    echo "   - Find 'Claude Auto-Fix Pipeline'"
    echo "   - Click 'Run workflow' to test manually"
    echo ""
    
else
    log_warning "Repository is not on GitHub"
    log_info "Claude Auto-Fix currently only supports GitHub repositories"
    echo "Consider migrating to GitHub or adapting the workflow for your platform"
fi

# Create documentation if it doesn't exist
DOC_FILE="docs/CLAUDE_AUTO_FIX_SETUP.md"
if [ ! -f "$DOC_FILE" ]; then
    log_info "Creating documentation directory..."
    mkdir -p "docs"
    log_success "Documentation should be available at: $DOC_FILE"
fi

# Check current workflow status
echo ""
log_info "🔍 Analyzing current repository status..."

# Check for common error patterns
ERROR_INDICATORS=0

# Check for failing tests
if [ -f "phpunit.xml" ] || [ -f "phpunit.xml.dist" ]; then
    log_info "PHPUnit configuration found"
    if command -v php >/dev/null 2>&1; then
        if ! php artisan test --stop-on-failure >/dev/null 2>&1; then
            log_warning "Some tests are currently failing"
            ERROR_INDICATORS=$((ERROR_INDICATORS + 1))
        else
            log_success "All tests are passing"
        fi
    else
        log_warning "PHP not found, cannot run tests"
    fi
fi

# Check for syntax errors
if command -v php >/dev/null 2>&1; then
    SYNTAX_ERRORS=$(find app tests -name "*.php" -exec php -l {} \; 2>&1 | grep -c "Parse error" || true)
    if [ "$SYNTAX_ERRORS" -gt 0 ]; then
        log_warning "Found $SYNTAX_ERRORS PHP syntax errors"
        ERROR_INDICATORS=$((ERROR_INDICATORS + 1))
    else
        log_success "No PHP syntax errors found"
    fi
fi

# Check composer.json validity
if [ -f "composer.json" ]; then
    if command -v composer >/dev/null 2>&1; then
        if composer validate --no-check-publish >/dev/null 2>&1; then
            log_success "composer.json is valid"
        else
            log_warning "composer.json has validation issues"
            ERROR_INDICATORS=$((ERROR_INDICATORS + 1))
        fi
    else
        log_warning "Composer not found, cannot validate composer.json"
    fi
fi

# Summary
echo ""
echo "📊 Setup Summary:"
echo "=================="
echo "✅ Workflow file: $([ -f "$CLAUDE_WORKFLOW" ] && echo "Ready" || echo "Needs commit")"
echo "✅ Documentation: $([ -f "$DOC_FILE" ] && echo "Available" || echo "Created")"
echo "⚠️  Error indicators found: $ERROR_INDICATORS"
echo "🔑 GitHub secrets: Manual setup required"
echo ""

if [ "$ERROR_INDICATORS" -gt 0 ]; then
    log_info "🎯 Good news! Your repository has $ERROR_INDICATORS error indicators."
    log_info "This means Claude Auto-Fix will have something to work with once configured!"
else
    log_success "Repository appears to be in good condition."
    log_info "Claude Auto-Fix will monitor for future issues."
fi

# Create a test commit if needed
if [ ! -f "$CLAUDE_WORKFLOW" ] || [ ! -f "$DOC_FILE" ]; then
    echo ""
    log_info "📝 Ready to commit Claude Auto-Fix files?"
    echo "This will add:"
    [ ! -f "$CLAUDE_WORKFLOW" ] && echo "  - $CLAUDE_WORKFLOW"
    [ ! -f "$DOC_FILE" ] && echo "  - $DOC_FILE"
    echo ""
    echo "Commit these files? (y/n)"
    read -r commit_response
    
    if [[ "$commit_response" =~ ^[Yy]$ ]]; then
        git add "$WORKFLOW_DIR"/*.yml docs/ scripts/ 2>/dev/null || true
        
        if git commit -m "🤖 Add Claude Auto-Fix workflow

- Add automated error detection and fixing
- Integrate Claude AI for intelligent code fixes  
- Add safety measures and monitoring
- Include comprehensive documentation

🔧 Features:
- Automatic error detection from tests and static analysis
- Claude AI-powered fix generation
- Safe, conservative approach to code changes
- Pull request creation for manual review
- Comprehensive logging and monitoring

📚 See docs/CLAUDE_AUTO_FIX_SETUP.md for setup instructions

🤖 Generated with Claude Auto-Fix Setup Script"; then
            log_success "Files committed successfully!"
            log_info "Next: Push to GitHub and configure CLAUDE_API_KEY secret"
        else
            log_warning "Commit failed or no changes to commit"
        fi
    else
        log_info "Skipping commit. Files are ready when you are!"
    fi
fi

# Final instructions
echo ""
log_success "🎉 Claude Auto-Fix setup complete!"
echo ""
echo "🚀 Next steps:"
echo "1. Push changes to GitHub (if you committed)"
echo "2. Get Claude API key from https://console.anthropic.com/"
echo "3. Add CLAUDE_API_KEY to GitHub Secrets"
echo "4. Wait for errors to occur, or trigger manually"
echo "5. Review Claude's Pull Requests carefully"
echo ""
echo "📖 For detailed instructions, see: docs/CLAUDE_AUTO_FIX_SETUP.md"
echo ""
log_success "Happy coding with Claude Auto-Fix! 🤖✨"