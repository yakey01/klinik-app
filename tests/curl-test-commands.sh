#!/bin/bash

# API v2 NonParamedis Dashboard - cURL Test Commands
# Comprehensive testing script for all endpoints
# 
# Usage: 
#   chmod +x curl-test-commands.sh
#   ./curl-test-commands.sh
#
# Note: Update API_BASE_URL to match your environment

set -e  # Exit on any error

# Configuration
API_BASE_URL="http://localhost/api/v2"
TEST_USER_EMAIL="sari.lestari@dokterku.com"
TEST_USER_PASSWORD="password"
DEVICE_NAME="Test Device"

# GPS coordinates within work location (Klinik Dokterku)
VALID_LATITUDE="-6.2088"
VALID_LONGITUDE="106.8456"
GPS_ACCURACY="15"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Global variables
ACCESS_TOKEN=""
TEST_COUNT=0
PASS_COUNT=0
FAIL_COUNT=0

# Helper functions
log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[PASS]${NC} $1"
    ((PASS_COUNT++))
}

log_error() {
    echo -e "${RED}[FAIL]${NC} $1"
    ((FAIL_COUNT++))
}

log_warning() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

test_counter() {
    ((TEST_COUNT++))
}

print_header() {
    echo ""
    echo "================================="
    echo " $1"
    echo "================================="
}

# Function to make API request and validate response
api_request() {
    local method="$1"
    local endpoint="$2"
    local data="$3"
    local auth_header="$4"
    local expected_status="$5"
    local test_name="$6"
    
    test_counter
    log_info "Test #$TEST_COUNT: $test_name"
    log_info "Request: $method $endpoint"
    
    # Build curl command
    local curl_cmd="curl -s -w 'HTTP_STATUS:%{http_code}' -X $method"
    
    if [ ! -z "$auth_header" ]; then
        curl_cmd="$curl_cmd -H 'Authorization: Bearer $ACCESS_TOKEN'"
    fi
    
    curl_cmd="$curl_cmd -H 'Content-Type: application/json'"
    curl_cmd="$curl_cmd -H 'Accept: application/json'"
    
    if [ ! -z "$data" ]; then
        curl_cmd="$curl_cmd -d '$data'"
    fi
    
    curl_cmd="$curl_cmd '$API_BASE_URL$endpoint'"
    
    # Execute request
    local response=$(eval $curl_cmd)
    local http_status=$(echo "$response" | grep -o 'HTTP_STATUS:[0-9]*' | cut -d: -f2)
    local body=$(echo "$response" | sed 's/HTTP_STATUS:[0-9]*$//')
    
    # Validate response
    if [ "$http_status" = "$expected_status" ]; then
        log_success "HTTP Status: $http_status (Expected: $expected_status)"
        
        # Validate JSON structure
        if echo "$body" | jq . >/dev/null 2>&1; then
            log_success "Valid JSON response"
            
            # Check required fields
            local has_status=$(echo "$body" | jq -r 'has("status")')
            local has_message=$(echo "$body" | jq -r 'has("message")')
            local has_meta=$(echo "$body" | jq -r 'has("meta")')
            
            if [ "$has_status" = "true" ] && [ "$has_message" = "true" ] && [ "$has_meta" = "true" ]; then
                log_success "Required response fields present (status, message, meta)"
            else
                log_error "Missing required response fields"
            fi
            
            # Extract token if login response
            if [[ "$endpoint" == "/auth/login" ]] && [ "$http_status" = "200" ]; then
                ACCESS_TOKEN=$(echo "$body" | jq -r '.data.token // empty')
                if [ ! -z "$ACCESS_TOKEN" ]; then
                    log_success "Access token extracted"
                else
                    log_error "Failed to extract access token"
                fi
            fi
            
        else
            log_error "Invalid JSON response"
        fi
    else
        log_error "HTTP Status: $http_status (Expected: $expected_status)"
    fi
    
    # Pretty print response (first 500 chars)
    if [ ${#body} -gt 500 ]; then
        echo "Response: $(echo "$body" | cut -c1-500)..."
    else
        echo "Response: $body"
    fi
    
    echo ""
}

# Test Functions

test_system_health() {
    print_header "SYSTEM HEALTH TESTS"
    
    # Test API health endpoint
    api_request "GET" "/system/health" "" "" "200" "System Health Check"
    
    # Test API version endpoint
    api_request "GET" "/system/version" "" "" "200" "System Version Info"
}

test_authentication() {
    print_header "AUTHENTICATION TESTS"
    
    # Test login
    local login_data="{
        \"login\": \"$TEST_USER_EMAIL\",
        \"password\": \"$TEST_USER_PASSWORD\",
        \"device_name\": \"$DEVICE_NAME\"
    }"
    
    api_request "POST" "/auth/login" "$login_data" "" "200" "User Login"
    
    if [ -z "$ACCESS_TOKEN" ]; then
        log_error "Cannot proceed with authenticated tests - no access token"
        return 1
    fi
    
    # Test user profile
    api_request "GET" "/auth/me" "" "auth" "200" "Get User Profile"
}

test_nonparamedis_dashboard() {
    print_header "NON-PARAMEDIS DASHBOARD TESTS"
    
    if [ -z "$ACCESS_TOKEN" ]; then
        log_error "Skipping dashboard tests - no access token"
        return 1
    fi
    
    # Test health check endpoint
    api_request "GET" "/dashboards/nonparamedis/test" "" "auth" "200" "Dashboard Health Check"
    
    # Test main dashboard
    api_request "GET" "/dashboards/nonparamedis/" "" "auth" "200" "Main Dashboard Data"
    
    # Test attendance status
    api_request "GET" "/dashboards/nonparamedis/attendance/status" "" "auth" "200" "Attendance Status"
    
    # Test today history
    api_request "GET" "/dashboards/nonparamedis/attendance/today-history" "" "auth" "200" "Today History"
}

test_attendance_actions() {
    print_header "ATTENDANCE ACTION TESTS"
    
    if [ -z "$ACCESS_TOKEN" ]; then
        log_error "Skipping attendance tests - no access token"
        return 1
    fi
    
    # Test check-in
    local checkin_data="{
        \"latitude\": $VALID_LATITUDE,
        \"longitude\": $VALID_LONGITUDE,
        \"accuracy\": $GPS_ACCURACY
    }"
    
    api_request "POST" "/dashboards/nonparamedis/attendance/checkin" "$checkin_data" "auth" "200" "Check-in Attendance"
    
    # Wait a bit to simulate work time
    log_info "Simulating work time (2 seconds)..."
    sleep 2
    
    # Test check-out
    local checkout_data="{
        \"latitude\": $VALID_LATITUDE,
        \"longitude\": $VALID_LONGITUDE,
        \"accuracy\": $GPS_ACCURACY
    }"
    
    api_request "POST" "/dashboards/nonparamedis/attendance/checkout" "$checkout_data" "auth" "200" "Check-out Attendance"
}

test_data_validation() {
    print_header "DATA VALIDATION TESTS"
    
    if [ -z "$ACCESS_TOKEN" ]; then
        log_error "Skipping validation tests - no access token"
        return 1
    fi
    
    # Test invalid GPS coordinates
    local invalid_gps="{
        \"latitude\": 91,
        \"longitude\": 181,
        \"accuracy\": 15
    }"
    
    api_request "POST" "/dashboards/nonparamedis/attendance/checkin" "$invalid_gps" "auth" "422" "Invalid GPS Coordinates"
    
    # Test missing GPS data
    local missing_gps="{
        \"accuracy\": 15
    }"
    
    api_request "POST" "/dashboards/nonparamedis/attendance/checkin" "$missing_gps" "auth" "422" "Missing GPS Data"
    
    # Test GPS out of range (far from work location)
    local far_gps="{
        \"latitude\": -6.3000,
        \"longitude\": 106.9000,
        \"accuracy\": 15
    }"
    
    api_request "POST" "/dashboards/nonparamedis/attendance/checkin" "$far_gps" "auth" "422" "GPS Out of Range"
}

test_security() {
    print_header "SECURITY TESTS"
    
    # Test unauthenticated access
    api_request "GET" "/dashboards/nonparamedis/test" "" "" "401" "Unauthenticated Access - Should Fail"
    api_request "GET" "/dashboards/nonparamedis/" "" "" "401" "Dashboard Without Auth - Should Fail"
    
    # Test with invalid token
    local old_token="$ACCESS_TOKEN"
    ACCESS_TOKEN="invalid_token_12345"
    api_request "GET" "/dashboards/nonparamedis/test" "" "auth" "401" "Invalid Token - Should Fail"
    ACCESS_TOKEN="$old_token"
}

test_work_locations() {
    print_header "WORK LOCATIONS TESTS"
    
    # Test public work locations endpoint
    api_request "GET" "/locations/work-locations" "" "" "200" "Get Work Locations (Public)"
}

test_rate_limiting() {
    print_header "RATE LIMITING TESTS"
    
    if [ -z "$ACCESS_TOKEN" ]; then
        log_error "Skipping rate limit tests - no access token"
        return 1
    fi
    
    log_info "Testing rate limiting (making multiple rapid requests)..."
    
    # Make 12 rapid requests to attendance endpoint (limit is 10/minute)
    for i in {1..12}; do
        log_info "Request $i/12"
        local response=$(curl -s -w 'HTTP_STATUS:%{http_code}' \
            -H "Authorization: Bearer $ACCESS_TOKEN" \
            -H "Content-Type: application/json" \
            -H "Accept: application/json" \
            "$API_BASE_URL/dashboards/nonparamedis/attendance/status")
        
        local http_status=$(echo "$response" | grep -o 'HTTP_STATUS:[0-9]*' | cut -d: -f2)
        
        if [ "$http_status" = "429" ]; then
            log_success "Rate limiting triggered at request $i (HTTP 429)"
            break
        elif [ $i -eq 12 ]; then
            log_warning "Rate limiting not triggered after 12 requests"
        fi
        
        # Small delay between requests
        sleep 0.1
    done
}

cleanup_test_data() {
    print_header "CLEANUP"
    
    if [ ! -z "$ACCESS_TOKEN" ]; then
        log_info "Logging out to clean up session..."
        api_request "POST" "/auth/logout" "" "auth" "200" "Logout"
    fi
}

generate_report() {
    print_header "TEST SUMMARY"
    
    echo "Total Tests: $TEST_COUNT"
    echo "Passed: $PASS_COUNT"
    echo "Failed: $FAIL_COUNT"
    
    local success_rate=$((PASS_COUNT * 100 / TEST_COUNT))
    echo "Success Rate: $success_rate%"
    
    if [ $FAIL_COUNT -eq 0 ]; then
        log_success "ALL TESTS PASSED! ✅"
    else
        log_error "Some tests failed. Check output above for details."
    fi
    
    echo ""
    echo "Test Coverage Summary:"
    echo "• System Health: ✅"
    echo "• Authentication: ✅"
    echo "• Dashboard Endpoints: ✅"
    echo "• Attendance Actions: ✅"
    echo "• Data Validation: ✅"
    echo "• Security: ✅"
    echo "• Rate Limiting: ✅"
    echo ""
}

# Main execution
main() {
    print_header "API v2 NON-PARAMEDIS DASHBOARD TEST SUITE"
    log_info "Starting comprehensive API testing..."
    log_info "Base URL: $API_BASE_URL"
    log_info "Test User: $TEST_USER_EMAIL"
    echo ""
    
    # Check if jq is available
    if ! command -v jq &> /dev/null; then
        log_warning "jq is not installed. JSON validation will be limited."
    fi
    
    # Run all test suites
    test_system_health
    test_authentication
    test_work_locations
    test_nonparamedis_dashboard
    test_attendance_actions
    test_data_validation
    test_security
    test_rate_limiting
    cleanup_test_data
    
    # Generate final report
    generate_report
}

# Execute main function
main "$@"