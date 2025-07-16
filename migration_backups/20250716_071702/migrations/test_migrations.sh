#!/bin/bash

# Laravel Migration Testing Script
# Purpose: Test migration integrity before and after refactoring
# Usage: ./test_migrations.sh

set -e

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${GREEN}=== Laravel Migration Testing Suite ===${NC}"
echo -e "This script will test your migrations for common issues"
echo ""

# Function to run a test
run_test() {
    local test_name=$1
    local test_command=$2
    
    echo -e "${YELLOW}Running: ${test_name}${NC}"
    
    if eval $test_command; then
        echo -e "${GREEN}✓ ${test_name} passed${NC}"
        return 0
    else
        echo -e "${RED}✗ ${test_name} failed${NC}"
        return 1
    fi
}

# Test 1: Check Laravel is accessible
echo -e "${BLUE}=== Pre-flight Checks ===${NC}"
run_test "Laravel accessible" "php artisan --version > /dev/null 2>&1"

# Test 2: Check database connection
run_test "Database connection" "php artisan db:show > /dev/null 2>&1"

# Test 3: Migration status
echo ""
echo -e "${BLUE}=== Current Migration Status ===${NC}"
php artisan migrate:status

# Test 4: Check for syntax errors in migrations
echo ""
echo -e "${BLUE}=== Syntax Validation ===${NC}"
ERROR_COUNT=0
for file in database/migrations/*.php; do
    if ! php -l "$file" > /dev/null 2>&1; then
        echo -e "${RED}✗ Syntax error in: $(basename $file)${NC}"
        ERROR_COUNT=$((ERROR_COUNT + 1))
    fi
done

if [ $ERROR_COUNT -eq 0 ]; then
    echo -e "${GREEN}✓ All migration files have valid PHP syntax${NC}"
else
    echo -e "${RED}✗ Found $ERROR_COUNT files with syntax errors${NC}"
fi

# Test 5: Dry run migration
echo ""
echo -e "${BLUE}=== Migration Dry Run ===${NC}"
echo -e "${YELLOW}Testing migration on a test database...${NC}"
echo "Note: This requires a test database configured in .env.testing"

# Function to test migrations
test_migrations() {
    echo ""
    echo -e "${YELLOW}1. Testing fresh migration...${NC}"
    if php artisan migrate:fresh --env=testing --force > /dev/null 2>&1; then
        echo -e "${GREEN}✓ Fresh migration successful${NC}"
    else
        echo -e "${RED}✗ Fresh migration failed${NC}"
        echo "Run manually to see errors: php artisan migrate:fresh --env=testing"
        return 1
    fi
    
    echo ""
    echo -e "${YELLOW}2. Testing rollback (last 10 migrations)...${NC}"
    if php artisan migrate:rollback --step=10 --env=testing --force > /dev/null 2>&1; then
        echo -e "${GREEN}✓ Rollback successful${NC}"
    else
        echo -e "${RED}✗ Rollback failed${NC}"
        echo "Run manually to see errors: php artisan migrate:rollback --step=10 --env=testing"
        return 1
    fi
    
    echo ""
    echo -e "${YELLOW}3. Testing re-migration...${NC}"
    if php artisan migrate --env=testing --force > /dev/null 2>&1; then
        echo -e "${GREEN}✓ Re-migration successful${NC}"
    else
        echo -e "${RED}✗ Re-migration failed${NC}"
        echo "Run manually to see errors: php artisan migrate --env=testing"
        return 1
    fi
    
    echo ""
    echo -e "${YELLOW}4. Testing refresh...${NC}"
    if php artisan migrate:refresh --env=testing --force > /dev/null 2>&1; then
        echo -e "${GREEN}✓ Refresh successful${NC}"
    else
        echo -e "${RED}✗ Refresh failed${NC}"
        echo "Run manually to see errors: php artisan migrate:refresh --env=testing"
        return 1
    fi
}

# Ask user if they want to run destructive tests
echo ""
echo -e "${YELLOW}Do you want to run destructive migration tests on the TEST database?${NC}"
echo -e "${RED}WARNING: This will drop and recreate all tables in the TEST database!${NC}"
read -p "Continue? (y/N) " -n 1 -r
echo ""

if [[ $REPLY =~ ^[Yy]$ ]]; then
    test_migrations
else
    echo -e "${YELLOW}Skipping destructive tests${NC}"
fi

# Test 6: Check for common issues
echo ""
echo -e "${BLUE}=== Common Issues Check ===${NC}"

# Check for duplicate timestamps
echo -e "${YELLOW}Checking for duplicate timestamps...${NC}"
DUPLICATES=$(ls database/migrations/*.php | sed 's/.*\///;s/_.*//' | sort | uniq -d)
if [ -z "$DUPLICATES" ]; then
    echo -e "${GREEN}✓ No duplicate timestamps found${NC}"
else
    echo -e "${RED}✗ Found duplicate timestamps:${NC}"
    echo "$DUPLICATES"
fi

# Check for circular dependencies (basic check)
echo ""
echo -e "${YELLOW}Checking for potential circular dependencies...${NC}"
if grep -r "pegawai_id.*foreign.*users" database/migrations/*.php > /dev/null && \
   grep -r "user_id.*foreign.*pegawais" database/migrations/*.php > /dev/null; then
    echo -e "${RED}✗ Potential circular dependency detected between users and pegawais${NC}"
else
    echo -e "${GREEN}✓ No obvious circular dependencies found${NC}"
fi

# Summary
echo ""
echo -e "${BLUE}=== Test Summary ===${NC}"
echo "1. Run the backup script before making changes:"
echo -e "   ${GREEN}./database/migrations/backup_migrations.sh${NC}"
echo ""
echo "2. Fix any issues found above, especially:"
echo "   - Syntax errors"
echo "   - Duplicate timestamps"
echo "   - Circular dependencies"
echo ""
echo "3. After refactoring, run this test again to verify"
echo ""
echo -e "${GREEN}Testing complete!${NC}"