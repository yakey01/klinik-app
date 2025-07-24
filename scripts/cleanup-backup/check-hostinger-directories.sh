#!/bin/bash

echo "🔍 Checking Hostinger Directory Structure"
echo "========================================"

# Set connection timeout
CONNECT_TIMEOUT=30
USER="u454362045"
HOST="srv476.hstgr.io"

echo "📡 Testing SSH connection..."
if timeout $CONNECT_TIMEOUT ssh -o ConnectTimeout=10 -o StrictHostKeyChecking=no "$USER@$HOST" "echo 'SSH connection successful'" 2>/dev/null; then
    echo "✅ SSH connection OK"
    
    echo ""
    echo "📂 Listing domains directory..."
    ssh -o ConnectTimeout=10 "$USER@$HOST" "ls -la /home/$USER/domains/ | grep -E '(dokter|total)'"
    
    echo ""
    echo "🔍 Checking for both possible domain names..."
    
    # Check dokterkuklinik.com (with 'klinik') - CORRECTED
    if ssh -o ConnectTimeout=10 "$USER@$HOST" "[ -d '/home/$USER/domains/dokterkuklinik.com' ]" 2>/dev/null; then
        echo "✅ Found: dokterkuklinik.com (with 'klinik')"
        ssh -o ConnectTimeout=10 "$USER@$HOST" "ls -la /home/$USER/domains/dokterkuklinik.com/"
    else
        echo "❌ Not found: dokterkuklinik.com (with 'klinik')"
    fi
    
    # Check dokterkuklinik.com (with 'klinik')  
    if ssh -o ConnectTimeout=10 "$USER@$HOST" "[ -d '/home/$USER/domains/dokterkuklinik.com' ]" 2>/dev/null; then
        echo "✅ Found: dokterkuklinik.com (with 'klinik')"
        ssh -o ConnectTimeout=10 "$USER@$HOST" "ls -la /home/$USER/domains/dokterkuklinik.com/"
    else
        echo "❌ Not found: dokterkuklinik.com (with 'klinik')"
    fi
    
    echo ""
    echo "🌐 Testing website response..."
    echo "dokterkuklinik.com (with 'k') response:"
    curl -I https://dokterkuklinik.com 2>/dev/null | head -3 || echo "No response"
    
    echo ""
    echo "dokterkuklinik.com (with 'k') response:"
    curl -I https://dokterkuklinik.com 2>/dev/null | head -3 || echo "No response"
    
else
    echo "❌ SSH connection failed - using alternative method"
    echo ""
    echo "🌐 Testing websites directly..."
    
    echo "Testing dokterkuklinik.com (with 'k'):"
    curl -I https://dokterkuklinik.com 2>/dev/null | head -3 || echo "No response from dokterkuklinik.com"
    
    echo ""
    echo "Testing dokterkuklinik.com (with 'k'):"  
    curl -I https://dokterkuklinik.com 2>/dev/null | head -3 || echo "No response from dokterkuklinik.com"
    
    echo ""
    echo "📝 Based on code analysis:"
    echo "- Configuration points to: dokterkuklinik.com (with 'klinik')"
    echo "- User ID: u454362045"
    echo "- Expected path: /home/u454362045/domains/dokterkuklinik.com/public_html/"
fi

echo ""
echo "📋 Summary:"
echo "- Domain in code: dokterkuklinik.com (with 'k')"
echo "- This should be the ACTIVE directory"  
echo "- Any dokterkuklinic.com (with 'c') references have been corrected"