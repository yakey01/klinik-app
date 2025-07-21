#!/bin/bash

# Deep Root Cause Analysis for Persistent Pegawai Edit 500 Error
echo "🔍 DEEP PEGAWAI EDIT 500 ERROR ANALYSIS"
echo "======================================="

# Use environment variable or prompt for password
if [ -z "$SSH_PASS" ]; then
    read -s -p "🔐 SSH Password: " SSH_PASS
    echo
fi

HOST="u196138154@srv556.hstgr.io"
APP_PATH="/home/u196138154/domains/dokterkuklinik.com/public_html"

echo "🚀 Performing comprehensive analysis..."

sshpass -p "$SSH_PASS" ssh -o StrictHostKeyChecking=no "$HOST" << 'DEEP_ANALYSIS'
cd /home/u196138154/domains/dokterkuklinik.com/public_html

echo "🔥 === DEEP ROOT CAUSE ANALYSIS ==="
echo "=================================="

echo
echo "📋 1. REAL-TIME ERROR CAPTURE"
echo "-----------------------------"
echo "Capturing live errors from Laravel log..."

# Monitor the log while testing
tail -f storage/logs/laravel.log &
LOG_PID=$!

# Test the problematic endpoint with curl to trigger the error
echo "Testing problematic endpoint..."
curl -s -H "User-Agent: DeepAnalysis/1.0" "https://dokterkuklinik.com/admin/pegawais/1/edit" > /tmp/pegawai_response.html

# Kill the log monitoring
sleep 2
kill $LOG_PID 2>/dev/null

echo "Latest error entries:"
tail -20 storage/logs/laravel.log | grep -A 5 -B 5 -E "(ERROR|Exception|Fatal)"

echo
echo "📋 2. DETAILED DATABASE SCHEMA ANALYSIS"
echo "---------------------------------------"
php artisan tinker --execute="
echo '=== COMPREHENSIVE DATABASE ANALYSIS ===\n';

try {
    // 1. Raw database connection test
    \$pdo = \DB::connection()->getPdo();
    echo '✅ PDO Connection: ACTIVE\n';
    echo 'Driver: ' . \$pdo->getAttribute(PDO::ATTR_DRIVER_NAME) . '\n';
    
    // 2. Check if pegawais table exists
    \$tables = \DB::select('SHOW TABLES');
    \$pegawaisExists = false;
    foreach (\$tables as \$table) {
        \$tableName = array_values((array)\$table)[0];
        if (\$tableName === 'pegawais') {
            \$pegawaisExists = true;
            break;
        }
    }
    echo 'Pegawais table exists: ' . (\$pegawaisExists ? 'YES' : 'NO') . '\n';
    
    if (!\$pegawaisExists) {
        echo '❌ CRITICAL: pegawais table missing!\n';
        exit();
    }
    
    // 3. Detailed column analysis
    echo '\n=== PEGAWAIS TABLE STRUCTURE ===\n';
    \$columns = \DB::select('SHOW COLUMNS FROM pegawais');
    foreach (\$columns as \$col) {
        echo \$col->Field . ' | ' . \$col->Type . ' | ' . \$col->Null . ' | ' . \$col->Key . ' | ' . (\$col->Default ?? 'NULL') . '\n';
    }
    
    // 4. Check for email column specifically
    \$emailColumn = \DB::select('SHOW COLUMNS FROM pegawais WHERE Field = \"email\"');
    if (count(\$emailColumn) > 0) {
        \$col = \$emailColumn[0];
        echo '\n✅ EMAIL COLUMN DETAILS:\n';
        echo 'Field: ' . \$col->Field . '\n';
        echo 'Type: ' . \$col->Type . '\n';
        echo 'Null: ' . \$col->Null . '\n';
        echo 'Key: ' . \$col->Key . '\n';
        echo 'Default: ' . (\$col->Default ?? 'NULL') . '\n';
        echo 'Extra: ' . (\$col->Extra ?? 'none') . '\n';
    } else {
        echo '\n❌ EMAIL COLUMN MISSING!\n';
    }
    
    // 5. Test direct SQL access to email column
    echo '\n=== DIRECT SQL EMAIL TEST ===\n';
    try {
        \$result = \DB::select('SELECT id, nama_lengkap, email FROM pegawais WHERE id = 1 LIMIT 1');
        if (count(\$result) > 0) {
            \$row = \$result[0];
            echo '✅ Direct SQL access successful:\n';
            echo 'ID: ' . \$row->id . '\n';
            echo 'Name: ' . \$row->nama_lengkap . '\n';
            echo 'Email: ' . (\$row->email ?? 'NULL') . '\n';
        } else {
            echo '❌ No records found with ID 1\n';
        }
    } catch (Exception \$e) {
        echo '❌ Direct SQL failed: ' . \$e->getMessage() . '\n';
    }
    
} catch (Exception \$e) {
    echo '❌ Database analysis failed: ' . \$e->getMessage() . '\n';
    echo 'File: ' . \$e->getFile() . ':' . \$e->getLine() . '\n';
}
"

echo
echo "📋 3. ELOQUENT MODEL ANALYSIS"
echo "-----------------------------"
php artisan tinker --execute="
echo '=== ELOQUENT MODEL DEEP ANALYSIS ===\n';

try {
    // 1. Test Pegawai model loading
    echo 'Testing Pegawai model instantiation...\n';
    \$pegawai = new \App\Models\Pegawai();
    echo '✅ Model instantiation: OK\n';
    
    // 2. Check fillable fields
    \$fillable = \$pegawai->getFillable();
    echo 'Fillable fields count: ' . count(\$fillable) . '\n';
    echo 'Email in fillable: ' . (in_array('email', \$fillable) ? 'YES' : 'NO') . '\n';
    
    // 3. Check guarded fields
    \$guarded = \$pegawai->getGuarded();
    echo 'Guarded fields: ' . (empty(\$guarded) ? 'none' : implode(', ', \$guarded)) . '\n';
    
    // 4. Test finding specific record
    echo '\nTesting record retrieval...\n';
    \$pegawai1 = \App\Models\Pegawai::find(1);
    
    if (\$pegawai1) {
        echo '✅ Pegawai ID 1 found via Eloquent\n';
        echo 'Name: ' . \$pegawai1->nama_lengkap . '\n';
        echo 'Username: ' . (\$pegawai1->username ?? 'NULL') . '\n';
        
        // 5. Critical test: Access email attribute
        echo '\nTesting email attribute access...\n';
        try {
            \$email = \$pegawai1->email;
            echo '✅ Email attribute access: SUCCESS\n';
            echo 'Email value: ' . (\$email ?? 'NULL') . '\n';
        } catch (Exception \$e) {
            echo '❌ Email attribute access FAILED: ' . \$e->getMessage() . '\n';
            echo 'This is likely the root cause!\n';
        }
        
        // 6. Test all attributes
        echo '\nAll model attributes:\n';
        \$attributes = \$pegawai1->getAttributes();
        foreach (\$attributes as \$key => \$value) {
            if (\$key === 'email') {
                echo '🎯 ' . \$key . ': ' . (\$value ?? 'NULL') . '\n';
            } else {
                echo '   ' . \$key . ': ' . (strlen(\$value ?? '') > 50 ? substr(\$value, 0, 47) . '...' : (\$value ?? 'NULL')) . '\n';
            }
        }
        
    } else {
        echo '❌ Pegawai ID 1 not found via Eloquent\n';
        
        // Check if any pegawai records exist
        \$count = \App\Models\Pegawai::count();
        echo 'Total pegawai records: ' . \$count . '\n';
        
        if (\$count > 0) {
            \$first = \App\Models\Pegawai::first();
            echo 'First record ID: ' . \$first->id . '\n';
            echo 'First record name: ' . \$first->nama_lengkap . '\n';
        }
    }
    
} catch (Exception \$e) {
    echo '❌ Eloquent model analysis failed: ' . \$e->getMessage() . '\n';
    echo 'File: ' . \$e->getFile() . ':' . \$e->getLine() . '\n';
    echo 'Stack trace:\n' . \$e->getTraceAsString() . '\n';
}
"

echo
echo "📋 4. FILAMENT RESOURCE ANALYSIS"
echo "--------------------------------"
php artisan tinker --execute="
echo '=== FILAMENT RESOURCE ANALYSIS ===\n';

try {
    // 1. Test PegawaiResource loading
    echo 'Testing PegawaiResource class loading...\n';
    \$resourceClass = 'App\Filament\Resources\PegawaiResource';
    
    if (class_exists(\$resourceClass)) {
        echo '✅ PegawaiResource class exists\n';
        
        // 2. Test model method
        \$modelClass = \$resourceClass::getModel();
        echo 'Model class: ' . \$modelClass . '\n';
        
        // 3. Test if we can get form schema
        echo 'Testing form schema access...\n';
        try {
            \$form = \$resourceClass::form(new \Filament\Forms\Form(\$resourceClass));
            echo '✅ Form schema accessible\n';
        } catch (Exception \$e) {
            echo '❌ Form schema error: ' . \$e->getMessage() . '\n';
        }
        
        // 4. Test pages
        echo 'Testing resource pages...\n';
        \$pages = \$resourceClass::getPages();
        foreach (\$pages as \$key => \$page) {
            echo '  Page: ' . \$key . ' => ' . \$page . '\n';
        }
        
    } else {
        echo '❌ PegawaiResource class not found\n';
    }
    
} catch (Exception \$e) {
    echo '❌ Filament resource analysis failed: ' . \$e->getMessage() . '\n';
    echo 'File: ' . \$e->getFile() . ':' . \$e->getLine() . '\n';
}
"

echo
echo "📋 5. MIGRATION STATUS DEEP DIVE"
echo "--------------------------------"
php artisan migrate:status | grep -E "(email|pegawai)"

echo
echo "Checking migration file integrity..."
if [ -f "database/migrations/2025_07_21_092713_add_email_column_to_pegawais_table.php" ]; then
    echo "✅ Email migration file exists"
    echo "File size: $(stat -c%s database/migrations/2025_07_21_092713_add_email_column_to_pegawais_table.php) bytes"
    echo "Last modified: $(stat -c%y database/migrations/2025_07_21_092713_add_email_column_to_pegawais_table.php)"
else
    echo "❌ Email migration file missing!"
fi

echo
echo "📋 6. PERMISSIONS AND FILE INTEGRITY"
echo "------------------------------------"
echo "Model file permissions:"
ls -la app/Models/Pegawai.php

echo "Resource file permissions:"
ls -la app/Filament/Resources/PegawaiResource.php

echo "Storage permissions:"
ls -la storage/logs/

echo
echo "📋 7. PHP ERROR LOG ANALYSIS"
echo "----------------------------"
echo "Checking for PHP fatal errors..."

# Check various PHP error log locations
for log_file in "/home/u196138154/domains/dokterkuklinik.com/logs/error.log" \
                "error_log" \
                "/tmp/php_errors.log" \
                "/var/log/php_errors.log"; do
    if [ -f "$log_file" ]; then
        echo "=== Found: $log_file ==="
        tail -20 "$log_file" | grep -E "(Fatal|Error|pegawai|edit)" || echo "No relevant errors"
        echo
    fi
done

echo
echo "📋 8. MEMORY AND RESOURCE ANALYSIS"
echo "----------------------------------"
echo "PHP configuration:"
php -r "
echo 'Memory limit: ' . ini_get('memory_limit') . '\n';
echo 'Max execution time: ' . ini_get('max_execution_time') . '\n';
echo 'Error reporting: ' . ini_get('error_reporting') . '\n';
echo 'Display errors: ' . ini_get('display_errors') . '\n';
echo 'Log errors: ' . ini_get('log_errors') . '\n';
echo 'Error log: ' . ini_get('error_log') . '\n';
"

echo
echo "📋 9. TESTING ACTUAL EDIT ENDPOINT"
echo "----------------------------------"
echo "Testing with detailed curl output..."

curl -v -H "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8" \
     -H "User-Agent: DeepAnalysis/1.0" \
     "https://dokterkuklinik.com/admin/pegawais/1/edit" 2>&1 | head -50

echo
echo "🎯 === DEEP ANALYSIS COMPLETE ==="
echo "================================="
echo
echo "🔍 CHECK THE OUTPUT ABOVE FOR:"
echo "1. ❌ Database connection issues"
echo "2. ❌ Missing email column"
echo "3. ❌ Eloquent model attribute access failures"
echo "4. ❌ Filament resource loading errors"
echo "5. ❌ Migration integrity problems"
echo "6. ❌ Permission/file access issues"
echo "7. ❌ PHP fatal errors or memory issues"
echo "8. ❌ HTTP response errors"
echo
echo "💡 The root cause should be visible in one of the sections above"

DEEP_ANALYSIS

echo
echo "🏁 Deep analysis completed!"
echo "Review the output above to identify the specific root cause."

# Clean up
unset SSH_PASS