#!/bin/bash

echo "🔍 Testing Laravel Deployment Status"
echo "====================================="

# Test SSH connection using domain
echo "1. Testing SSH connection via domain..."
ssh -i ~/.ssh/dokterku_deploy u454362045@dokterkuklinik.com "echo 'SSH connection successful'"

# Check Laravel application status
echo "2. Checking Laravel application status..."
ssh -i ~/.ssh/dokterku_deploy u454362045@dokterkuklinik.com "cd domains/dokterkuklinik.com/public_html && php artisan --version"

# Check if .env exists
echo "3. Checking .env file..."
ssh -i ~/.ssh/dokterku_deploy u454362045@dokterkuklinik.com "cd domains/dokterkuklinik.com/public_html && ls -la .env*"

# Check storage permissions
echo "4. Checking storage permissions..."
ssh -i ~/.ssh/dokterku_deploy u454362045@dokterkuklinik.com "cd domains/dokterkuklinik.com/public_html && ls -la storage/ bootstrap/cache/"

# Test web access
echo "5. Testing web access..."
curl -I https://dokterkuklinik.com

echo "✅ Deployment test completed!" 