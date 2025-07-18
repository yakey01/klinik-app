#!/bin/bash

echo "🔍 Checking Deployment Status"
echo "============================="

# Check if GitHub Actions workflow is running
echo "1. Checking GitHub Actions status..."
echo "   Go to: https://github.com/yakey01/klinik-app/actions"
echo "   Look for the latest workflow run"

# Check website status
echo "2. Testing website access..."
curl -s -o /dev/null -w "%{http_code}" https://dokterkuklinik.com
echo " - HTTP Status Code"

# Check if SSH key exists
echo "3. Checking SSH key..."
if [ -f ~/.ssh/dokterku_deploy ]; then
    echo "   ✅ SSH key exists: ~/.ssh/dokterku_deploy"
else
    echo "   ❌ SSH key missing: ~/.ssh/dokterku_deploy"
fi

# Check GitHub secrets status
echo "4. GitHub Secrets Status:"
echo "   Required secrets:"
echo "   - HOST: dokterkuklinik.com"
echo "   - REMOTE_USER: u454362045"
echo "   - SSH_PRIVATE_KEY: [Your private key]"
echo ""
echo "   Check at: https://github.com/yakey01/klinik-app/settings/secrets/actions"

# Check local git status
echo "5. Local Git Status:"
git status --porcelain | head -5

echo ""
echo "🎯 Next Steps:"
echo "1. Verify GitHub secrets are configured"
echo "2. Check GitHub Actions tab for workflow status"
echo "3. If workflow failed, check the error logs"
echo "4. If website shows 403, SSH into server and run fix-server.sh" 