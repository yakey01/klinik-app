#!/bin/bash

# Dokterku Healthcare System Deployment Health Check Script
# This script performs comprehensive health checks after deployment

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
ENVIRONMENT=${1:-staging}
BASE_URL=${2:-http://localhost:8000}
TIMEOUT=30
RETRY_COUNT=5

echo -e "${BLUE}🏥 Dokterku Healthcare System - Deployment Health Check${NC}"
echo -e "${BLUE}=================================================${NC}"
echo -e "Environment: ${ENVIRONMENT}"
echo -e "Base URL: ${BASE_URL}"
echo -e "Timestamp: $(date)"
echo ""

# Function to check HTTP endpoint
check_endpoint() {
    local endpoint=$1
    local expected_code=${2:-200}
    local description=$3
    
    echo -n "Checking ${description}... "
    
    for i in $(seq 1 $RETRY_COUNT); do
        response_code=$(curl -s -o /dev/null -w "%{http_code}" --max-time $TIMEOUT "${BASE_URL}${endpoint}" || echo "000")
        
        if [[ "$response_code" == "$expected_code" ]] || [[ "$response_code" == "302" ]]; then
            echo -e "${GREEN}✅ PASS${NC} (HTTP $response_code)"
            return 0
        fi
        
        if [ $i -lt $RETRY_COUNT ]; then
            echo -n "."
            sleep 2
        fi
    done
    
    echo -e "${RED}❌ FAIL${NC} (HTTP $response_code)"
    return 1
}

# Function to check database connection
check_database() {
    echo -n "Checking database connection... "
    
    if docker-compose -f docker-compose.${ENVIRONMENT}.yml ps mysql | grep -q "Up"; then
        if docker-compose -f docker-compose.${ENVIRONMENT}.yml exec -T mysql mysqladmin ping -h localhost --silent; then
            echo -e "${GREEN}✅ PASS${NC}"
            return 0
        fi
    fi
    
    echo -e "${RED}❌ FAIL${NC}"
    return 1
}

# Function to check Redis connection
check_redis() {
    echo -n "Checking Redis connection... "
    
    if docker-compose -f docker-compose.${ENVIRONMENT}.yml ps redis | grep -q "Up"; then
        if docker-compose -f docker-compose.${ENVIRONMENT}.yml exec -T redis redis-cli ping | grep -q "PONG"; then
            echo -e "${GREEN}✅ PASS${NC}"
            return 0
        fi
    fi
    
    echo -e "${RED}❌ FAIL${NC}"
    return 1
}

# Function to check application logs
check_logs() {
    echo -n "Checking application logs for errors... "
    
    # Check for recent errors in application logs
    error_count=$(docker-compose -f docker-compose.${ENVIRONMENT}.yml logs app | grep -i "error\|exception\|fatal" | tail -10 | wc -l)
    
    if [ "$error_count" -eq 0 ]; then
        echo -e "${GREEN}✅ PASS${NC} (No recent errors)"
        return 0
    else
        echo -e "${YELLOW}⚠️ WARNING${NC} ($error_count recent errors found)"
        return 0  # Don't fail deployment for warnings
    fi
}

# Function to check disk space
check_disk_space() {
    echo -n "Checking disk space... "
    
    # Check if disk usage is less than 85%
    disk_usage=$(df / | awk 'NR==2 {print $5}' | sed 's/%//')
    
    if [ "$disk_usage" -lt 85 ]; then
        echo -e "${GREEN}✅ PASS${NC} (${disk_usage}% used)"
        return 0
    elif [ "$disk_usage" -lt 95 ]; then
        echo -e "${YELLOW}⚠️ WARNING${NC} (${disk_usage}% used - monitor closely)"
        return 0
    else
        echo -e "${RED}❌ FAIL${NC} (${disk_usage}% used - critical)"
        return 1
    fi
}

# Function to check memory usage
check_memory() {
    echo -n "Checking memory usage... "
    
    # Check memory usage
    memory_usage=$(free | grep Mem | awk '{printf "%.0f", $3/$2 * 100.0}')
    
    if [ "$memory_usage" -lt 80 ]; then
        echo -e "${GREEN}✅ PASS${NC} (${memory_usage}% used)"
        return 0
    elif [ "$memory_usage" -lt 90 ]; then
        echo -e "${YELLOW}⚠️ WARNING${NC} (${memory_usage}% used)"
        return 0
    else
        echo -e "${RED}❌ FAIL${NC} (${memory_usage}% used - critical)"
        return 1
    fi
}

# Function to check SSL certificate (production only)
check_ssl() {
    if [ "$ENVIRONMENT" != "production" ]; then
        return 0
    fi
    
    echo -n "Checking SSL certificate... "
    
    # Extract domain from BASE_URL
    domain=$(echo "$BASE_URL" | sed -e 's|^[^/]*//||' -e 's|/.*$||')
    
    # Check SSL certificate expiration
    expiry_date=$(echo | openssl s_client -servername "$domain" -connect "$domain:443" 2>/dev/null | openssl x509 -noout -dates | grep notAfter | cut -d= -f2)
    
    if [ -n "$expiry_date" ]; then
        expiry_timestamp=$(date -d "$expiry_date" +%s)
        current_timestamp=$(date +%s)
        days_until_expiry=$(( (expiry_timestamp - current_timestamp) / 86400 ))
        
        if [ "$days_until_expiry" -gt 30 ]; then
            echo -e "${GREEN}✅ PASS${NC} (expires in $days_until_expiry days)"
            return 0
        elif [ "$days_until_expiry" -gt 7 ]; then
            echo -e "${YELLOW}⚠️ WARNING${NC} (expires in $days_until_expiry days)"
            return 0
        else
            echo -e "${RED}❌ FAIL${NC} (expires in $days_until_expiry days)"
            return 1
        fi
    else
        echo -e "${RED}❌ FAIL${NC} (unable to check certificate)"
        return 1
    fi
}

# Initialize counters
PASSED=0
FAILED=0
WARNINGS=0

# Basic Infrastructure Checks
echo -e "${BLUE}🔧 Infrastructure Checks${NC}"
echo "=========================="

if check_database; then ((PASSED++)); else ((FAILED++)); fi
if check_redis; then ((PASSED++)); else ((FAILED++)); fi
if check_logs; then ((PASSED++)); else ((WARNINGS++)); fi
if check_disk_space; then ((PASSED++)); else ((FAILED++)); fi
if check_memory; then ((PASSED++)); else ((FAILED++)); fi
if check_ssl; then ((PASSED++)); else ((FAILED++)); fi

echo ""

# Application Health Checks
echo -e "${BLUE}🏥 Healthcare Application Checks${NC}"
echo "=================================="

if check_endpoint "/health" 200 "Application Health"; then ((PASSED++)); else ((FAILED++)); fi

echo ""

# Healthcare Panel Checks
echo -e "${BLUE}🔐 Healthcare Panel Accessibility${NC}"
echo "=================================="

declare -A PANELS=(
    ["admin"]="Admin Panel (System Management)"
    ["manajer"]="Manager Panel (Operations)" 
    ["bendahara"]="Finance Panel (Accounting)"
    ["petugas"]="Staff Panel (General Staff)"
    ["paramedis"]="Paramedic Panel (Medical Staff)"
    ["dokter"]="Doctor Panel (Medical Professionals)"
)

for panel in "${!PANELS[@]}"; do
    if check_endpoint "/${panel}/login" 200 "${PANELS[$panel]}"; then
        ((PASSED++))
    else
        # Try with 302 redirect (might be normal for some panels)
        if check_endpoint "/${panel}/login" 302 "${PANELS[$panel]} (redirect)"; then
            ((PASSED++))
        else
            ((FAILED++))
        fi
    fi
done

echo ""

# API Endpoint Checks
echo -e "${BLUE}🔌 API Endpoint Checks${NC}"
echo "======================"

if check_endpoint "/api/health" 200 "API Health Check"; then ((PASSED++)); else ((FAILED++)); fi

# Test auth endpoint (401 is expected without credentials)
if check_endpoint "/api/v1/auth/me" 401 "Auth API (Unauthorized as expected)"; then ((PASSED++)); else ((FAILED++)); fi

echo ""

# Performance Checks
echo -e "${BLUE}⚡ Performance Checks${NC}"
echo "===================="

echo -n "Checking response time... "
start_time=$(date +%s.%N)
response_code=$(curl -s -o /dev/null -w "%{http_code}" --max-time $TIMEOUT "${BASE_URL}/health")
end_time=$(date +%s.%N)
response_time=$(echo "$end_time - $start_time" | bc)
response_time_ms=$(echo "$response_time * 1000" | bc | cut -d. -f1)

if [ "$response_time_ms" -lt 1000 ]; then
    echo -e "${GREEN}✅ PASS${NC} (${response_time_ms}ms)"
    ((PASSED++))
elif [ "$response_time_ms" -lt 3000 ]; then
    echo -e "${YELLOW}⚠️ WARNING${NC} (${response_time_ms}ms - slow)"
    ((WARNINGS++))
else
    echo -e "${RED}❌ FAIL${NC} (${response_time_ms}ms - too slow)"
    ((FAILED++))
fi

echo ""

# Summary
echo -e "${BLUE}📊 Health Check Summary${NC}"
echo "======================="
echo -e "Environment: ${ENVIRONMENT}"
echo -e "Timestamp: $(date)"
echo -e "Total Checks: $((PASSED + FAILED + WARNINGS))"
echo -e "${GREEN}Passed: ${PASSED}${NC}"
echo -e "${YELLOW}Warnings: ${WARNINGS}${NC}"
echo -e "${RED}Failed: ${FAILED}${NC}"

if [ "$FAILED" -eq 0 ]; then
    echo ""
    echo -e "${GREEN}🎉 All critical health checks passed!${NC}"
    echo -e "${GREEN}🏥 Dokterku Healthcare System is operational.${NC}"
    
    if [ "$WARNINGS" -gt 0 ]; then
        echo -e "${YELLOW}⚠️ Please review warnings for optimization opportunities.${NC}"
    fi
    
    exit 0
else
    echo ""
    echo -e "${RED}❌ ${FAILED} critical health check(s) failed!${NC}"
    echo -e "${RED}🚨 Deployment requires attention before going live.${NC}"
    exit 1
fi