#!/bin/bash

echo "🔧 Fixing test files to use centralized role setup..."

# List of test files that need fixing
TEST_FILES=(
    "tests/Feature/AutomaticRoleAssignmentTest.php"
    "tests/Feature/Performance/DashboardPerformanceTest.php"
    "tests/Feature/Performance/ComponentPerformanceTest.php"
    "tests/Feature/Performance/DataExportPerformanceTest.php"
    "tests/Feature/Workflows/PetugasWorkflowTest.php"
    "tests/Feature/Workflows/ErrorHandlingIntegrationTest.php"
    "tests/Feature/Workflows/PerformanceIntegrationTest.php"
    "tests/Feature/Security/InputValidationSecurityTest.php"
    "tests/Feature/Workflows/ValidationFlowIntegrationTest.php"
    "tests/Feature/Security/PermissionSecurityTest.php"
    "tests/Feature/Security/DataScopingSecurityTest.php"
    "tests/Feature/Security/AuthenticationSecurityTest.php"
    "tests/Feature/Integration/RealtimeNotificationsIntegrationTest.php"
    "tests/Feature/Resources/PasienResourceTest.php"
    "tests/Feature/Integration/ValidationWorkflowIntegrationTest.php"
    "tests/Feature/Integration/BulkOperationsIntegrationTest.php"
    "tests/Unit/Widgets/QuickActionsWidgetTest.php"
)

for file in "${TEST_FILES[@]}"; do
    if [ -f "$file" ]; then
        echo "Processing: $file"
        
        # Create backup
        cp "$file" "$file.backup"
        
        # Remove Role::firstOrCreate lines and replace with comments
        sed -i '' '/Role::firstOrCreate/d' "$file"
        
        # Add comment about roles being handled by base TestCase
        # Find the setUp method and add comment
        if grep -q "protected function setUp" "$file"; then
            sed -i '' '/protected function setUp/a\
        // Roles are already created by base TestCase
' "$file"
        fi
        
        echo "✅ Fixed: $file"
    else
        echo "⚠️ File not found: $file"
    fi
done

echo "🎉 All test files have been updated!"
echo "📝 Remember to update any test methods that reference roles to use \$this->getRole('role_name')" 