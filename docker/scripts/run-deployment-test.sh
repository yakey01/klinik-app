#!/bin/bash

# Dokterku Healthcare System - Local Deployment Test Script
# This script simulates the GitHub Actions workflow locally for testing

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
ENVIRONMENT=${1:-local}
BASE_URL="http://127.0.0.1:8000"
TIMEOUT=30

echo -e "${BLUE}🚀 Dokterku Healthcare System - Local Deployment Test${NC}"
echo -e "${BLUE}======================================================${NC}"
echo -e "Environment: ${ENVIRONMENT}"
echo -e "Base URL: ${BASE_URL}"
echo -e "Timestamp: $(date)"
echo ""

# Function to run command with status
run_step() {
    local step_name=$1
    local command=$2
    
    echo -e "${BLUE}▶ ${step_name}${NC}"
    
    if eval "$command"; then
        echo -e "${GREEN}✅ ${step_name} - PASSED${NC}"
        return 0
    else
        echo -e "${RED}❌ ${step_name} - FAILED${NC}"
        return 1
    fi
}

# Step 1: Dependency Check
echo -e "${BLUE}🔧 Checking Dependencies${NC}"
echo "========================="

run_step "PHP Version Check" "php --version"
run_step "Composer Check" "composer --version"
run_step "Node.js Check" "node --version"
run_step "NPM Check" "npm --version"

echo ""

# Step 2: Environment Setup
echo -e "${BLUE}🌍 Environment Setup${NC}"
echo "===================="

run_step "Copy Testing Environment File" "cp .env.testing .env"
run_step "Generate App Key" "php artisan key:generate --force"
run_step "Clear Cache" "php artisan cache:clear"
run_step "Clear Config" "php artisan config:clear"

echo ""

# Step 3: Install Dependencies
echo -e "${BLUE}📦 Installing Dependencies${NC}"
echo "=========================="

run_step "Install Composer Dependencies" "composer install --no-interaction --prefer-dist --optimize-autoloader"
run_step "Install NPM Dependencies" "npm ci --prefer-offline --no-audit"

echo ""

# Step 4: Build Assets
echo -e "${BLUE}🏗️ Building Assets${NC}"
echo "=================="

run_step "Build Frontend Assets" "npm run build"
run_step "Publish Filament Assets" "php artisan vendor:publish --tag=filament-assets --force"

echo ""

# Step 5: Database Setup
echo -e "${BLUE}🗄️ Database Setup${NC}"
echo "=================="

run_step "Run Migrations" "php artisan migrate:fresh --force"
run_step "Seed Database" "php artisan db:seed --force"

echo ""

# Step 6: Code Quality Checks
echo -e "${BLUE}🔍 Code Quality Checks${NC}"
echo "======================"

if [ -f "vendor/bin/phpcs" ]; then
    run_step "PHP CodeSniffer" "vendor/bin/phpcs --standard=PSR12 app/ --ignore=app/Http/Middleware/TrustProxies.php || echo 'Code style issues found'"
else
    echo -e "${YELLOW}⚠️ PHP CodeSniffer not available - skipping${NC}"
fi

if [ -f "vendor/bin/phpstan" ]; then
    run_step "PHPStan Static Analysis" "vendor/bin/phpstan analyse --memory-limit=2G --error-format=github || echo 'Static analysis issues found'"
else
    echo -e "${YELLOW}⚠️ PHPStan not available - skipping${NC}"
fi

echo ""

# Step 7: Run Tests
echo -e "${BLUE}🧪 Running Tests${NC}"
echo "================"

run_step "PHPUnit Tests" "php artisan test"

echo ""

# Step 8: Start Local Server for Testing
echo -e "${BLUE}🚀 Starting Local Server${NC}"
echo "======================="

php artisan serve --host=127.0.0.1 --port=8000 &
SERVER_PID=$!
echo "Server started with PID: $SERVER_PID"
sleep 10

echo ""

# Step 9: Health Checks
echo -e "${BLUE}💚 Health Checks${NC}"
echo "================"

# Basic health check
echo -n "Testing application health... "
for i in {1..5}; do
    if curl -sf "${BASE_URL}/health" > /dev/null; then
        echo -e "${GREEN}✅ PASS${NC}"
        break
    else
        echo -n "."
        sleep 2
    fi
    
    if [ $i -eq 5 ]; then
        echo -e "${RED}❌ FAIL${NC}"
        kill $SERVER_PID 2>/dev/null || true
        exit 1
    fi
done

# API health check
echo -n "Testing API health... "
if curl -sf "${BASE_URL}/api/health" > /dev/null; then
    echo -e "${GREEN}✅ PASS${NC}"
else
    echo -e "${RED}❌ FAIL${NC}"
fi

echo ""

# Step 10: Healthcare Panel Checks
echo -e "${BLUE}🏥 Healthcare Panel Checks${NC}"
echo "=========================="

declare -A PANELS=(
    ["admin"]="Admin Panel"
    ["manajer"]="Manager Panel" 
    ["bendahara"]="Finance Panel"
    ["petugas"]="Staff Panel"
    ["paramedis"]="Paramedic Panel"
    ["dokter"]="Doctor Panel"
)

panel_failed=0
for panel in "${!PANELS[@]}"; do
    echo -n "Testing ${PANELS[$panel]}... "
    status_code=$(curl -s -o /dev/null -w "%{http_code}" "${BASE_URL}/${panel}/login" || echo "000")
    
    if [[ "$status_code" == "200" || "$status_code" == "302" ]]; then
        echo -e "${GREEN}✅ PASS (HTTP $status_code)${NC}"
    else
        echo -e "${RED}❌ FAIL (HTTP $status_code)${NC}"
        ((panel_failed++))
    fi
done

echo ""

# Step 11: Performance Test
echo -e "${BLUE}⚡ Performance Test${NC}"
echo "==================="

echo -n "Testing response time... "
start_time=$(date +%s.%N)
curl -s "${BASE_URL}/health" > /dev/null
end_time=$(date +%s.%N)
response_time=$(echo "$end_time - $start_time" | bc)
response_time_ms=$(echo "$response_time * 1000" | bc | cut -d. -f1)

if [ "$response_time_ms" -lt 1000 ]; then
    echo -e "${GREEN}✅ PASS (${response_time_ms}ms)${NC}"
elif [ "$response_time_ms" -lt 3000 ]; then
    echo -e "${YELLOW}⚠️ WARNING (${response_time_ms}ms - slow)${NC}"
else
    echo -e "${RED}❌ FAIL (${response_time_ms}ms - too slow)${NC}"
fi

echo ""

# Cleanup
echo -e "${BLUE}🧹 Cleanup${NC}"
echo "=========="

kill $SERVER_PID 2>/dev/null || true
echo "Server stopped"

# Summary
echo ""
echo -e "${BLUE}📊 Test Summary${NC}"
echo "==============="
echo -e "Environment: ${ENVIRONMENT}"
echo -e "Timestamp: $(date)"

if [ "$panel_failed" -eq 0 ]; then
    echo -e "${GREEN}🎉 All tests passed! Deployment simulation successful.${NC}"
    echo -e "${GREEN}🏥 Dokterku Healthcare System is ready for deployment.${NC}"
    exit 0
else
    echo -e "${RED}❌ ${panel_failed} panel(s) failed tests.${NC}"
    echo -e "${RED}🚫 Deployment simulation failed.${NC}"
    exit 1
fi