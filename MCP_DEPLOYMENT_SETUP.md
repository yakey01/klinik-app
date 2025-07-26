# MCP Deployment Setup for Laravel Project

This guide will help you set up MCP (Model Context Protocol) servers for easy deployment of your Laravel Dokterku project.

## Prerequisites

1. Claude Desktop installed
2. Node.js and npm installed
3. GitHub account with personal access token
4. SSH access to your hosting server

## Step 1: Configure Claude Desktop MCP Servers

Create or update your Claude Desktop configuration file:

**Location:** `~/.claude/claude_desktop_config.json`

```json
{
  "mcpServers": {
    "github": {
      "command": "npx",
      "args": ["-y", "@modelcontextprotocol/server-github"],
      "env": {
        "GITHUB_PERSONAL_ACCESS_TOKEN": "YOUR_GITHUB_TOKEN_HERE"
      }
    },
    "ssh": {
      "command": "npx",
      "args": ["-y", "@modelcontextprotocol/server-ssh"],
      "env": {
        "SSH_HOST": "YOUR_HOSTINGER_HOST",
        "SSH_USER": "YOUR_SSH_USERNAME",
        "SSH_KEY_PATH": "~/.ssh/id_rsa"
      }
    },
    "filesystem": {
      "command": "npx",
      "args": ["-y", "@modelcontextprotocol/server-filesystem", "/Users/kym/Herd/Dokterku"]
    }
  }
}
```

### Getting Your Tokens:

1. **GitHub Personal Access Token:**
   - Go to GitHub → Settings → Developer settings → Personal access tokens
   - Generate new token (classic)
   - Select scopes: `repo`, `workflow`, `read:org`
   - Copy the token

2. **SSH Configuration:**
   - Your Hostinger host (e.g., `srv123.hostinger.com`)
   - Your SSH username
   - Path to your SSH private key

## Step 2: Install MCP Servers

After updating the config, restart Claude Desktop. The MCP servers will be automatically installed via npx.

## Step 3: GitHub Actions Workflow

Create `.github/workflows/deploy.yml`:

```yaml
name: Deploy to Production
on:
  push:
    branches: [main]
  workflow_dispatch:

env:
  PHP_VERSION: '8.2'

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ env.PHP_VERSION }}
          extensions: dom, curl, libxml, mbstring, zip, pcntl, pdo, sqlite, pdo_sqlite, gd
          coverage: none
      
      - name: Install dependencies
        run: composer install --prefer-dist --no-progress
      
      - name: Run tests
        run: php artisan test

  deploy:
    needs: test
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      
      - name: Setup Node.js
        uses: actions/setup-node@v4
        with:
          node-version: '20'
          
      - name: Build assets
        run: |
          npm ci
          npm run build
          
      - name: Deploy to server
        uses: appleboy/ssh-action@v1.0.0
        with:
          host: ${{ secrets.HOST }}
          username: ${{ secrets.USERNAME }}
          key: ${{ secrets.SSH_KEY }}
          script: |
            cd ~/public_html
            
            # Backup current version
            cp .env .env.backup
            
            # Pull latest changes
            git pull origin main
            
            # Install/update dependencies
            composer install --no-dev --optimize-autoloader
            
            # Run migrations
            php artisan migrate --force
            
            # Clear and optimize
            php artisan config:cache
            php artisan route:cache
            php artisan view:cache
            php artisan queue:restart
            
            # Set permissions
            chmod -R 755 storage bootstrap/cache
            chown -R www-data:www-data storage bootstrap/cache
```

## Step 4: GitHub Secrets Setup

Add these secrets to your GitHub repository:
- Go to Settings → Secrets and variables → Actions
- Add:
  - `HOST`: Your Hostinger server hostname
  - `USERNAME`: SSH username
  - `SSH_KEY`: Your private SSH key content

## Step 5: Deployment Scripts

Create `deploy/production.sh`:

```bash
#!/bin/bash

# Production Deployment Script
set -e

echo "🚀 Starting production deployment..."

# Maintenance mode
php artisan down --message="Upgrading system. Please check back in a few minutes." --retry=60

# Backup database
php artisan backup:run --only-db

# Git pull
git pull origin main

# Install dependencies
composer install --no-dev --optimize-autoloader

# Build assets
npm ci
npm run build

# Database
php artisan migrate --force

# Clear caches
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Rebuild caches
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Queue restart
php artisan queue:restart

# Maintenance mode off
php artisan up

echo "✅ Deployment completed successfully!"
```

Create `deploy/rollback.sh`:

```bash
#!/bin/bash

# Rollback Script
set -e

echo "⏮️ Starting rollback..."

# Restore database backup
php artisan backup:restore --latest

# Git reset to previous commit
git reset --hard HEAD~1

# Restore composer dependencies
composer install --no-dev --optimize-autoloader

# Clear and rebuild caches
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "✅ Rollback completed!"
```

## Step 6: Laravel Deployment Commands

With MCP configured, you can now use these commands in Claude:

### Basic Deployment Commands:

```
"Deploy my Laravel app to production"
"Run database migrations on the server"
"Clear all Laravel caches on production"
"Check deployment status"
"Rollback the last deployment"
```

### Advanced Commands:

```
"Create a new GitHub release and deploy"
"Run composer update on production"
"Check Laravel logs on the server"
"Backup the production database"
"Update environment variables on production"
```

### Monitoring Commands:

```
"Show me the latest deployment logs"
"Check if the production site is up"
"Show Laravel error logs from today"
"Check queue status on production"
```

## Step 7: Quick Deployment Setup

Run this command to set up everything at once:

```bash
# Create all necessary files
mkdir -p .github/workflows deploy

# Create deployment workflow
cat > .github/workflows/deploy.yml << 'EOF'
# [Workflow content from Step 3]
EOF

# Create deployment script
cat > deploy/production.sh << 'EOF'
# [Script content from Step 5]
EOF

# Make scripts executable
chmod +x deploy/*.sh

# Create .env.production template
cat > .env.production << 'EOF'
APP_NAME=Dokterku
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password

# Add other production settings
EOF
```

## Usage Examples

Once everything is set up, you can tell Claude:

1. **"Deploy the latest changes to production"**
   - Claude will trigger the GitHub Action workflow

2. **"Check if deployment succeeded"**
   - Claude will check workflow status and server logs

3. **"Fix the failed deployment"**
   - Claude will analyze logs and suggest fixes

4. **"Update production database"**
   - Claude will run migrations safely

5. **"Emergency rollback production"**
   - Claude will execute rollback procedures

## Troubleshooting

### Common Issues:

1. **MCP Server not connecting:**
   - Restart Claude Desktop
   - Check token validity
   - Verify network connection

2. **Deployment fails:**
   - Check GitHub Actions logs
   - Verify SSH credentials
   - Check server disk space

3. **Permission errors:**
   - Ensure proper file permissions
   - Check SSH key permissions (600)

## Security Best Practices

1. **Never commit sensitive data**
   - Use GitHub Secrets
   - Keep .env files out of version control

2. **Limit token permissions**
   - Only grant necessary scopes
   - Rotate tokens regularly

3. **Monitor deployments**
   - Set up notifications
   - Review deployment logs

## Next Steps

1. Test deployment with a small change
2. Set up monitoring and alerts
3. Configure backup automation
4. Add staging environment

---

Need help? Ask Claude: "Help me troubleshoot my deployment setup"