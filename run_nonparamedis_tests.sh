#!/bin/bash

# NonParamedis Dashboard Testing Suite
# Comprehensive testing for 100% real data integration

echo "================================================"
echo "NonParamedis Dashboard Testing Suite"
echo "================================================"
echo ""

# Set environment for testing
export APP_ENV=testing
export DB_CONNECTION=sqlite
export DB_DATABASE=:memory:

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    local status=$1
    local message=$2
    
    case $status in
        "INFO")
            echo -e "${BLUE}[INFO]${NC} $message"
            ;;
        "SUCCESS")
            echo -e "${GREEN}[SUCCESS]${NC} $message"
            ;;
        "WARNING")
            echo -e "${YELLOW}[WARNING]${NC} $message"
            ;;
        "ERROR")
            echo -e "${RED}[ERROR]${NC} $message"
            ;;
    esac
}

# Function to run a specific test class
run_test_class() {
    local test_class=$1
    local description=$2
    
    echo ""
    print_status "INFO" "Running: $description"
    echo "----------------------------------------"
    
    if php artisan test --filter="$test_class" --stop-on-failure; then
        print_status "SUCCESS" "$description - PASSED"
        return 0
    else
        print_status "ERROR" "$description - FAILED"
        return 1
    fi
}

# Function to run API tests with curl
run_api_tests() {
    echo ""
    print_status "INFO" "Running Manual API Tests"
    echo "----------------------------------------"
    
    # Start Laravel development server in background
    php artisan serve --port=8080 &
    SERVER_PID=$!
    
    # Wait for server to start
    sleep 3
    
    # Test API endpoints
    local api_tests=0
    local api_passed=0
    
    # Test health endpoint
    ((api_tests++))
    if curl -s -o /dev/null -w "%{http_code}" http://localhost:8080/api/v2/system/health | grep -q "200"; then
        print_status "SUCCESS" "Health endpoint - PASSED"
        ((api_passed++))
    else
        print_status "ERROR" "Health endpoint - FAILED"
    fi
    
    # Test work locations endpoint
    ((api_tests++))
    if curl -s -o /dev/null -w "%{http_code}" http://localhost:8080/api/v2/locations/work-locations | grep -q "200"; then
        print_status "SUCCESS" "Work locations endpoint - PASSED"
        ((api_passed++))
    else
        print_status "ERROR" "Work locations endpoint - FAILED"
    fi
    
    # Test unauthorized access
    ((api_tests++))
    if curl -s -o /dev/null -w "%{http_code}" http://localhost:8080/api/v2/dashboards/nonparamedis/ | grep -q "401"; then
        print_status "SUCCESS" "Unauthorized access protection - PASSED"
        ((api_passed++))
    else
        print_status "ERROR" "Unauthorized access protection - FAILED"
    fi
    
    # Clean up server
    kill $SERVER_PID 2>/dev/null
    
    echo ""
    print_status "INFO" "API Tests: $api_passed/$api_tests passed"
    
    return $((api_tests - api_passed))
}

# Function to check database connectivity
check_database() {
    echo ""
    print_status "INFO" "Checking Database Connectivity"
    echo "----------------------------------------"
    
    if php artisan migrate:fresh --env=testing --force; then
        print_status "SUCCESS" "Database migration - PASSED"
        
        if php artisan db:seed --class=DatabaseSeeder --env=testing --force; then
            print_status "SUCCESS" "Database seeding - PASSED"
            return 0
        else
            print_status "ERROR" "Database seeding - FAILED"
            return 1
        fi
    else
        print_status "ERROR" "Database migration - FAILED"
        return 1
    fi
}

# Function to validate test environment
validate_environment() {
    echo ""
    print_status "INFO" "Validating Test Environment"
    echo "----------------------------------------"
    
    local errors=0
    
    # Check PHP version
    if php --version | grep -q "PHP 8"; then
        print_status "SUCCESS" "PHP 8.x detected"
    else
        print_status "ERROR" "PHP 8.x required"
        ((errors++))
    fi
    
    # Check Laravel installation
    if php artisan --version | grep -q "Laravel Framework"; then
        print_status "SUCCESS" "Laravel Framework detected"
    else
        print_status "ERROR" "Laravel Framework not detected"
        ((errors++))
    fi
    
    # Check required extensions
    local required_extensions=("pdo" "pdo_sqlite" "mbstring" "openssl" "tokenizer" "xml" "ctype" "json" "bcmath")
    
    for extension in "${required_extensions[@]}"; do
        if php -m | grep -q "$extension"; then
            print_status "SUCCESS" "PHP extension $extension - OK"
        else
            print_status "ERROR" "PHP extension $extension - MISSING"
            ((errors++))
        fi
    done
    
    return $errors
}

# Main execution
main() {
    local total_errors=0
    
    # Validate environment
    if ! validate_environment; then
        print_status "ERROR" "Environment validation failed"
        exit 1
    fi
    
    # Setup database
    if ! check_database; then
        print_status "ERROR" "Database setup failed"
        ((total_errors++))
    fi
    
    # Install dependencies if needed
    if [ ! -d "vendor" ]; then
        print_status "INFO" "Installing dependencies..."
        composer install --no-interaction --prefer-dist --optimize-autoloader
    fi
    
    # Clear caches
    php artisan config:clear
    php artisan cache:clear
    php artisan route:clear
    
    echo ""
    print_status "INFO" "Starting Test Suite Execution"
    echo "================================================"
    
    # Run test classes
    local test_results=()
    
    # 1. Comprehensive API Tests
    if run_test_class "NonParamedisComprehensiveTest" "1. Comprehensive API Endpoint Tests"; then
        test_results+=("PASS: Comprehensive API Tests")
    else
        test_results+=("FAIL: Comprehensive API Tests")
        ((total_errors++))
    fi
    
    # 2. Database Integration Tests
    if run_test_class "NonParamedisDatabaseIntegrationTest" "2. Database Integration Tests"; then
        test_results+=("PASS: Database Integration Tests")
    else
        test_results+=("FAIL: Database Integration Tests")
        ((total_errors++))
    fi
    
    # 3. Authentication Tests
    if run_test_class "NonParamedisAuthTest" "3. Authentication & Authorization Tests"; then
        test_results+=("PASS: Authentication Tests")
    else
        test_results+=("FAIL: Authentication Tests")
        ((total_errors++))
    fi
    
    # 4. Performance & Security Tests
    if run_test_class "NonParamedisPerformanceSecurityTest" "4. Performance & Security Tests"; then
        test_results+=("PASS: Performance & Security Tests")
    else
        test_results+=("FAIL: Performance & Security Tests")
        ((total_errors++))
    fi
    
    # 5. Frontend Integration Tests
    if run_test_class "NonParamedisFrontendIntegrationTest" "5. Frontend Integration Tests"; then
        test_results+=("PASS: Frontend Integration Tests")
    else
        test_results+=("FAIL: Frontend Integration Tests")
        ((total_errors++))
    fi
    
    # 6. Manual API Tests
    if run_api_tests; then
        test_results+=("PASS: Manual API Tests")
    else
        test_results+=("FAIL: Manual API Tests")
        ((total_errors++))
    fi
    
    # Generate summary report
    echo ""
    echo "================================================"
    print_status "INFO" "TEST SUITE SUMMARY"
    echo "================================================"
    
    for result in "${test_results[@]}"; do
        if [[ $result == PASS* ]]; then
            print_status "SUCCESS" "$result"
        else
            print_status "ERROR" "$result"
        fi
    done
    
    echo ""
    if [ $total_errors -eq 0 ]; then
        print_status "SUCCESS" "🎉 ALL TESTS PASSED! NonParamedis dashboard is ready for production."
        echo ""
        print_status "INFO" "✅ Real data integration: VERIFIED"
        print_status "INFO" "✅ API endpoints: FUNCTIONAL"
        print_status "INFO" "✅ Database operations: RELIABLE"
        print_status "INFO" "✅ Authentication: SECURE"
        print_status "INFO" "✅ Performance: OPTIMIZED"
        print_status "INFO" "✅ Security: VALIDATED"
        echo ""
        return 0
    else
        print_status "ERROR" "❌ $total_errors test suite(s) failed. Please review the errors above."
        echo ""
        print_status "WARNING" "🔧 Fix the failing tests before deploying to production."
        echo ""
        return 1
    fi
}

# Check if script is being run directly
if [[ "${BASH_SOURCE[0]}" == "${0}" ]]; then
    main "$@"
    exit $?
fi