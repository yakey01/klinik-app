#!/bin/bash

# Manual Deep Analysis for Pegawai Edit 500 Error
echo "🔍 MANUAL DEEP PEGAWAI ANALYSIS"
echo "==============================="

echo "💡 Since SSH access has issues, let's analyze from local files first"

echo
echo "📋 1. LOCAL CODE ANALYSIS"
echo "-------------------------"

echo "🔍 Checking PegawaiResource for potential issues..."
echo "File: app/Filament/Resources/PegawaiResource.php"

if [ -f "app/Filament/Resources/PegawaiResource.php" ]; then
    echo "✅ PegawaiResource exists locally"
    
    # Check for email field usage in the resource
    echo "Email field references in PegawaiResource:"
    grep -n -B2 -A2 "email" app/Filament/Resources/PegawaiResource.php || echo "No email references found"
    
    echo
    echo "Form component analysis:"
    grep -n -A5 -B5 "Forms\\\Components" app/Filament/Resources/PegawaiResource.php | head -30
    
else
    echo "❌ PegawaiResource not found locally"
fi

echo
echo "📋 2. PEGAWAI MODEL ANALYSIS"
echo "----------------------------"

if [ -f "app/Models/Pegawai.php" ]; then
    echo "✅ Pegawai model exists locally"
    
    echo "Fillable fields in Pegawai model:"
    grep -n -A10 "fillable" app/Models/Pegawai.php
    
    echo
    echo "Checking for email-related code:"
    grep -n -B2 -A2 "email" app/Models/Pegawai.php || echo "No email references found"
    
else
    echo "❌ Pegawai model not found locally"
fi

echo
echo "📋 3. MIGRATION ANALYSIS"
echo "------------------------"

MIGRATION_FILE="database/migrations/2025_07_21_092713_add_email_column_to_pegawais_table.php"
if [ -f "$MIGRATION_FILE" ]; then
    echo "✅ Email migration exists locally"
    echo "Migration content:"
    cat "$MIGRATION_FILE"
else
    echo "❌ Email migration not found locally"
fi

echo
echo "📋 4. ROUTE ANALYSIS"
echo "--------------------"

echo "Checking for pegawai-related routes:"
grep -n -B2 -A2 "pegawai" routes/web.php || echo "No pegawai routes in web.php"

echo
echo "📋 5. POTENTIAL ROOT CAUSES"
echo "---------------------------"

echo "🔍 Based on persistent 500 error after cache clearing, possible causes:"
echo
echo "1. 🗃️  DATABASE CONSTRAINT VIOLATION:"
echo "   - Email column has unique constraint"
echo "   - Trying to save duplicate/invalid email"
echo "   - Foreign key constraint failures"
echo
echo "2. 🎭 FORM VALIDATION FAILURES:"
echo "   - Required field validation on email"
echo "   - Custom validation rules failing"
echo "   - Form component configuration errors"
echo
echo "3. 🔧 FILAMENT SPECIFIC ISSUES:"
echo "   - Form schema errors"
echo "   - Resource page configuration"
echo "   - Action button logic failures"
echo
echo "4. 📁 FILE PERMISSION ISSUES:"
echo "   - Storage directory permissions"
echo "   - Log file write permissions"
echo "   - Session storage issues"
echo
echo "5. 🧠 MEMORY/EXECUTION LIMITS:"
echo "   - PHP memory exhaustion"
echo "   - Script execution timeout"
echo "   - Large dataset processing"

echo
echo "📋 6. SUGGESTED DEBUGGING APPROACH"
echo "----------------------------------"

echo "💡 To identify the exact error, we need to:"
echo
echo "1. 🪲 Check Laravel logs immediately after triggering error:"
echo "   tail -f storage/logs/laravel.log"
echo
echo "2. 🔍 Check PHP error logs:"
echo "   tail -f /var/log/php_errors.log"
echo
echo "3. 🧪 Test with simplified data:"
echo "   - Try editing pegawai without email"
echo "   - Test with minimal form data"
echo
echo "4. 📊 Database integrity check:"
echo "   - Check for constraint violations"
echo "   - Verify email column is properly indexed"
echo
echo "5. 🎯 Isolate the failing component:"
echo "   - Test individual form fields"
echo "   - Test role creation separately"

echo
echo "📋 7. IMMEDIATE DIAGNOSTIC COMMANDS"
echo "-----------------------------------"

echo "🚀 Run these commands on production server:"
echo
echo "# Check recent errors:"
echo "tail -50 storage/logs/laravel.log | grep ERROR"
echo
echo "# Test database email column:"
echo "php artisan tinker --execute=\"\$p = \App\Models\Pegawai::find(1); echo \$p->email;\""
echo
echo "# Test form schema loading:"
echo "php artisan tinker --execute=\"\$form = \App\Filament\Resources\PegawaiResource::form(new \Filament\Forms\Form('test')); echo 'OK';\""
echo
echo "# Check constraint violations:"
echo "php artisan tinker --execute=\"\$duplicates = \DB::select('SELECT email, COUNT(*) FROM pegawais WHERE email IS NOT NULL GROUP BY email HAVING COUNT(*) > 1'); print_r(\$duplicates);\""

echo
echo "🎯 NEXT STEPS:"
echo "=============="
echo "1. Access production server manually"
echo "2. Run the diagnostic commands above"
echo "3. Check the actual error message in logs"
echo "4. Focus on the specific failing component"
echo
echo "The 500 error persisting after cache clear indicates a deeper issue"
echo "likely related to data constraints, form validation, or file permissions."