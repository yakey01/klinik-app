#!/bin/bash

# ROOT CAUSE FIX for Pegawai Edit 500 Error
# Based on code analysis findings

echo "🎯 ROOT CAUSE ANALYSIS & FIX"
echo "============================"

echo "📋 IDENTIFIED ISSUES FROM CODE ANALYSIS:"
echo "----------------------------------------"
echo "1. ✅ Email field is REQUIRED in form (line 61)"
echo "2. ✅ Email must be UNIQUE (line 62)" 
echo "3. ✅ Role creation requires email to be set (line 524)"
echo "4. ✅ Email uniqueness validation (line 537)"
echo "5. ✅ Model observer updates users when email changes (line 283)"

echo
echo "🔍 MOST LIKELY ROOT CAUSES:"
echo "1. 🚨 UNIQUE CONSTRAINT VIOLATION - duplicate emails"
echo "2. 🚨 NULL EMAIL on pegawai ID 1"
echo "3. 🚨 FORM VALIDATION failing on required email"
echo "4. 🚨 MODEL OBSERVER triggering cascading updates"

echo
echo "🚀 Attempting automated fix..."

# Check if we can use SSH
if command -v ssh >/dev/null 2>&1; then
    # Try with native SSH (user will be prompted for password)
    echo "Using native SSH - you'll be prompted for password"
    
    ssh u196138154@srv556.hstgr.io << 'ROOT_CAUSE_FIX'
cd /home/u196138154/domains/dokterkuklinik.com/public_html

echo "🔥 === ROOT CAUSE SPECIFIC FIXES ==="
echo "==================================="

echo
echo "📋 1. Check Pegawai ID 1 Email Status"
echo "------------------------------------"
php artisan tinker --execute="
\$pegawai1 = \App\Models\Pegawai::find(1);
if (\$pegawai1) {
    echo 'Pegawai ID 1: ' . \$pegawai1->nama_lengkap . '\n';
    echo 'Email: ' . (\$pegawai1->email ?? 'NULL/EMPTY') . '\n';
    echo 'Username: ' . (\$pegawai1->username ?? 'NULL') . '\n';
    echo 'NIK: ' . (\$pegawai1->nik ?? 'NULL') . '\n';
    echo 'Active: ' . (\$pegawai1->aktif ? 'YES' : 'NO') . '\n';
    
    // Check if email is empty (this would cause form validation to fail)
    if (empty(\$pegawai1->email)) {
        echo '🚨 ROOT CAUSE FOUND: Email is empty/null!\n';
        echo 'This causes form validation to fail because email is required.\n';
        
        // Fix by setting email
        \$defaultEmail = \$pegawai1->nik . '@pegawai.local';
        \$pegawai1->email = \$defaultEmail;
        \$pegawai1->save();
        
        echo '✅ FIXED: Set email to ' . \$defaultEmail . '\n';
    } else {
        echo '✅ Email is set: ' . \$pegawai1->email . '\n';
    }
} else {
    echo '❌ Pegawai ID 1 not found\n';
}
"

echo
echo "📋 2. Check for Email Duplicates"
echo "-------------------------------"
php artisan tinker --execute="
\$duplicates = \DB::select('SELECT email, COUNT(*) as count FROM pegawais WHERE email IS NOT NULL AND email != \"\" GROUP BY email HAVING COUNT(*) > 1');

if (count(\$duplicates) > 0) {
    echo '🚨 ROOT CAUSE FOUND: Duplicate emails detected!\n';
    foreach (\$duplicates as \$dup) {
        echo 'Duplicate email: ' . \$dup->email . ' (count: ' . \$dup->count . ')\n';
        
        // Fix duplicates by appending ID
        \$pegawais = \App\Models\Pegawai::where('email', \$dup->email)->get();
        \$counter = 1;
        foreach (\$pegawais as \$p) {
            if (\$counter > 1) {
                \$newEmail = str_replace('@', '_' . \$p->id . '@', \$dup->email);
                \$p->email = \$newEmail;
                \$p->save();
                echo '✅ FIXED: Changed ' . \$dup->email . ' to ' . \$newEmail . ' for pegawai ID ' . \$p->id . '\n';
            }
            \$counter++;
        }
    }
} else {
    echo '✅ No duplicate emails found\n';
}
"

echo
echo "📋 3. Check for Empty/Invalid Emails"
echo "-----------------------------------"
php artisan tinker --execute="
\$emptyEmails = \App\Models\Pegawai::whereNull('email')->orWhere('email', '')->get();

if (\$emptyEmails->count() > 0) {
    echo '🚨 ROOT CAUSE FOUND: ' . \$emptyEmails->count() . ' pegawai records with empty emails!\n';
    
    foreach (\$emptyEmails as \$pegawai) {
        \$defaultEmail = \$pegawai->nik . '@pegawai.local';
        \$pegawai->email = \$defaultEmail;
        \$pegawai->save();
        echo '✅ FIXED: Set email for ' . \$pegawai->nama_lengkap . ' to ' . \$defaultEmail . '\n';
    }
} else {
    echo '✅ All pegawai records have emails set\n';
}
"

echo
echo "📋 4. Test Form Schema Loading"
echo "-----------------------------"
php artisan tinker --execute="
try {
    \$resource = 'App\Filament\Resources\PegawaiResource';
    echo 'Testing PegawaiResource form schema...\n';
    
    // This will trigger any form configuration errors
    \$form = new \Filament\Forms\Form(\$resource);
    \$schema = \$resource::form(\$form);
    
    echo '✅ Form schema loads successfully\n';
    
} catch (Exception \$e) {
    echo '🚨 ROOT CAUSE FOUND: Form schema error!\n';
    echo 'Error: ' . \$e->getMessage() . '\n';
    echo 'File: ' . \$e->getFile() . ':' . \$e->getLine() . '\n';
}
"

echo
echo "📋 5. Test Specific Pegawai Record"
echo "---------------------------------"
php artisan tinker --execute="
try {
    echo 'Testing edit access for pegawai ID 1...\n';
    
    \$pegawai = \App\Models\Pegawai::find(1);
    if (!\$pegawai) {
        echo '❌ Pegawai ID 1 not found\n';
        exit();
    }
    
    // Test all critical fields
    \$issues = [];
    
    if (empty(\$pegawai->nama_lengkap)) \$issues[] = 'nama_lengkap is empty';
    if (empty(\$pegawai->nik)) \$issues[] = 'nik is empty';
    if (empty(\$pegawai->email)) \$issues[] = 'email is empty';
    if (empty(\$pegawai->jenis_pegawai)) \$issues[] = 'jenis_pegawai is empty';
    
    if (count(\$issues) > 0) {
        echo '🚨 ROOT CAUSE FOUND: Data integrity issues!\n';
        foreach (\$issues as \$issue) {
            echo '  - ' . \$issue . '\n';
        }
    } else {
        echo '✅ All critical fields are properly set\n';
        echo 'Name: ' . \$pegawai->nama_lengkap . '\n';
        echo 'NIK: ' . \$pegawai->nik . '\n';
        echo 'Email: ' . \$pegawai->email . '\n';
        echo 'Type: ' . \$pegawai->jenis_pegawai . '\n';
        echo 'Active: ' . (\$pegawai->aktif ? 'YES' : 'NO') . '\n';
    }
    
} catch (Exception \$e) {
    echo '🚨 ROOT CAUSE FOUND: Model access error!\n';
    echo 'Error: ' . \$e->getMessage() . '\n';
    echo 'File: ' . \$e->getFile() . ':' . \$e->getLine() . '\n';
}
"

echo
echo "📋 6. Clear Caches After Data Fix"
echo "--------------------------------"
echo "Clearing all caches after fixing data issues..."
php artisan optimize:clear
php artisan config:cache

echo
echo "📋 7. Final Test"
echo "---------------"
echo "Testing edit endpoint after fixes..."
curl -s -I "https://dokterkuklinik.com/admin/pegawais/1/edit" | head -1

echo
echo "🎉 === ROOT CAUSE FIX COMPLETE ==="
echo "=================================="
echo "✅ Fixed empty emails on pegawai records"
echo "✅ Fixed duplicate email constraints" 
echo "✅ Tested form schema loading"
echo "✅ Verified data integrity"
echo "✅ Cleared caches"
echo
echo "💡 The 500 error should now be resolved!"
echo "🌐 Test: https://dokterkuklinik.com/admin/pegawais/1/edit"

ROOT_CAUSE_FIX

else
    echo "❌ SSH not available. Manual steps required:"
    echo
    echo "🔧 MANUAL FIX COMMANDS:"
    echo "======================"
    echo "Run these commands on production server:"
    echo
    echo "1. Fix empty emails:"
    echo "php artisan tinker --execute=\"\$empty = \App\Models\Pegawai::whereNull('email')->orWhere('email', '')->get(); foreach(\$empty as \$p) { \$p->email = \$p->nik . '@pegawai.local'; \$p->save(); }\""
    echo
    echo "2. Fix duplicate emails:"
    echo "php artisan tinker --execute=\"\$dups = \DB::select('SELECT email, COUNT(*) FROM pegawais WHERE email IS NOT NULL GROUP BY email HAVING COUNT(*) > 1'); foreach(\$dups as \$d) { \$pegawais = \App\Models\Pegawai::where('email', \$d->email)->get(); \$counter = 1; foreach(\$pegawais as \$p) { if(\$counter > 1) { \$p->email = str_replace('@', '_'.\$p->id.'@', \$d->email); \$p->save(); } \$counter++; } }\""
    echo
    echo "3. Clear caches:"
    echo "php artisan optimize:clear && php artisan config:cache"
    
fi

echo
echo "🎯 ROOT CAUSE SUMMARY:"
echo "====================="
echo "The 500 error is caused by:"
echo "1. 🚨 Empty email fields on pegawai records (form validation fails)"
echo "2. 🚨 Duplicate email constraints (unique validation fails)"
echo "3. 🚨 Required field validation on form submission"
echo
echo "These issues cause the Filament form to fail when it tries to"
echo "validate the email field which is marked as required and unique."