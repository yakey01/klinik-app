#!/bin/bash

# Capture Live 500 Error - Real-time Error Analysis
echo "🚨 CAPTURING LIVE 500 ERROR"
echo "==========================="

echo "💡 This script will capture the exact error as it happens"
echo "We'll trigger the error while monitoring logs in real-time"

# Create a simple expect script for live monitoring
cat > /tmp/live_error_capture.exp << 'EOF'
#!/usr/bin/expect -f

set timeout 60
set host "srv556.hstgr.io"
set user "u196138154"

send_user "🔐 SSH Password: "
stty -echo
expect_user -re "(.*)\n"
set password $expect_out(1,string)
stty echo
send_user "\n"

# Connect to server
spawn ssh -o StrictHostKeyChecking=no $user@$host
expect "password:"
send "$password\r"
expect "$ "

send "cd /home/u196138154/domains/dokterkuklinik.com/public_html\r"
expect "$ "

send_user "🔍 Starting live error monitoring...\n"

# Start monitoring Laravel log in background
send "tail -f storage/logs/laravel.log &\r"
expect "$ "

send_user "📡 Triggering the error by accessing the problematic URL...\n"

# Trigger the error by accessing the URL
send "curl -s 'https://dokterkuklinik.com/admin/pegawais/1/edit' > /dev/null 2>&1 &\r"
expect "$ "

send_user "⏰ Waiting 5 seconds for error to appear in logs...\n"
sleep 5

# Stop the tail process
send "pkill tail\r"
expect "$ "

send_user "📋 Capturing the most recent error entries...\n"

# Get the last errors
send "echo '=== LATEST ERRORS ==='\r"
expect "$ "

send "tail -50 storage/logs/laravel.log | grep -A 10 -B 5 -E '(ERROR|Exception|Fatal)' | tail -30\r"
expect "$ "

send_user "🔍 Checking PHP error logs...\n"
send "find . -name 'error*log' -type f | head -3 | xargs tail -10\r"
expect "$ "

send_user "✅ Live error capture completed!\n"
send "exit\r"
expect eof
EOF

chmod +x /tmp/live_error_capture.exp

# Try to run the live capture
if command -v expect >/dev/null 2>&1; then
    echo "🚀 Running live error capture..."
    /tmp/live_error_capture.exp
else
    echo "❌ Expect not available. Using alternative approach..."
    
    # Alternative approach - create a monitoring script
    cat > monitor_error.sh << 'MONITOR_SCRIPT'
#!/bin/bash

echo "🔍 ALTERNATIVE ERROR MONITORING"
echo "==============================="

echo "💡 Manual steps to capture the live error:"
echo
echo "1. 🖥️  SSH into production server:"
echo "   ssh u196138154@srv556.hstgr.io"
echo
echo "2. 📁 Navigate to app directory:"
echo "   cd /home/u196138154/domains/dokterkuklinik.com/public_html"
echo
echo "3. 📊 Monitor logs in real-time:"
echo "   tail -f storage/logs/laravel.log"
echo
echo "4. 🌐 In another terminal/tab, trigger the error:"
echo "   curl -v 'https://dokterkuklinik.com/admin/pegawais/1/edit'"
echo
echo "5. 👀 Watch the log output for the exact error message"
echo
echo "6. 🔍 Additional checks to run:"
echo "   # Check recent errors"
echo "   tail -100 storage/logs/laravel.log | grep -E '(ERROR|Exception)'"
echo "   "
echo "   # Check PHP errors"
echo "   find . -name '*error*log' | xargs tail -20"
echo "   "
echo "   # Test database access"
echo "   php artisan tinker --execute=\"echo \App\Models\Pegawai::find(1)->nama_lengkap;\""
echo "   "
echo "   # Test Filament resource"
echo "   php artisan tinker --execute=\"\$r = new \App\Filament\Resources\PegawaiResource; echo 'OK';\""

MONITOR_SCRIPT

    chmod +x monitor_error.sh
    ./monitor_error.sh
fi

echo
echo "🎯 ALTERNATIVE DIAGNOSTIC APPROACH"
echo "=================================="

echo "Since the error persists, let's try a different strategy:"

echo
echo "📋 1. SIMPLIFIED ERROR TESTING"
echo "------------------------------"

echo "💡 Test these URLs individually to isolate the issue:"
echo
echo "🧪 Test 1 - Admin panel general:"
echo "curl -I 'https://dokterkuklinik.com/admin'"
curl -I 'https://dokterkuklinik.com/admin' 2>/dev/null | head -1

echo
echo "🧪 Test 2 - Pegawai index:"
echo "curl -I 'https://dokterkuklinik.com/admin/pegawais'"
curl -I 'https://dokterkuklinik.com/admin/pegawais' 2>/dev/null | head -1

echo
echo "🧪 Test 3 - Different pegawai edit:"
echo "curl -I 'https://dokterkuklinik.com/admin/pegawais/2/edit'"
curl -I 'https://dokterkuklinik.com/admin/pegawais/2/edit' 2>/dev/null | head -1

echo
echo "🧪 Test 4 - Create page:"
echo "curl -I 'https://dokterkuklinik.com/admin/pegawais/create'"
curl -I 'https://dokterkuklinik.com/admin/pegawais/create' 2>/dev/null | head -1

echo
echo "📋 2. ERROR PATTERN ANALYSIS"
echo "----------------------------"

if curl -s -I 'https://dokterkuklinik.com/admin/pegawais/1/edit' | grep -q "500"; then
    echo "✅ Confirmed: ID 1 edit returns 500"
    
    # Test other IDs to see if it's specific to ID 1
    echo "🔍 Testing other pegawai IDs..."
    for id in 2 3 4 5; do
        status=$(curl -s -o /dev/null -w "%{http_code}" "https://dokterkuklinik.com/admin/pegawais/$id/edit" 2>/dev/null)
        echo "   ID $id: $status"
    done
    
else
    echo "🤔 Interesting: The error might be intermittent or resolved"
fi

echo
echo "📋 3. PROBABLE ROOT CAUSES"
echo "-------------------------"

echo "🔍 Given the persistent 500 error, most likely causes:"
echo
echo "1. 🗃️  SPECIFIC DATA ISSUE with Pegawai ID 1:"
echo "   - Corrupted record data"
echo "   - Invalid relationships"
echo "   - Missing foreign key references"
echo
echo "2. 🎭 FILAMENT EDIT PAGE SPECIFIC ISSUE:"
echo "   - Edit form component failure"
echo "   - Edit page middleware issue"
echo "   - Resource edit method exception"
echo
echo "3. 🔧 ENVIRONMENT SPECIFIC ISSUE:"
echo "   - Production-only configuration problem"
echo "   - File permission issue on edit routes"
echo "   - Memory limit on edit page rendering"
echo
echo "4. 🧠 SESSION/AUTH ISSUE:"
echo "   - Authentication failure on edit action"
echo "   - CSRF token issues"
echo "   - Session storage problems"

echo
echo "📋 4. IMMEDIATE NEXT STEPS"
echo "-------------------------"

echo "🚀 PRIORITY ACTIONS:"
echo
echo "1. 📊 Get the exact error message from Laravel logs"
echo "2. 🧪 Test if other pegawai IDs work (isolate if it's ID 1 specific)"
echo "3. 🗃️  Check pegawai ID 1 data integrity"
echo "4. 🎭 Test Filament resource directly"
echo
echo "💡 The key is getting the actual error message from the logs"
echo "   which will point us to the exact failing line of code."

# Cleanup
rm -f /tmp/live_error_capture.exp

echo
echo "🏁 Error capture setup completed!"
echo "Next: Access production server and monitor logs while triggering the error"