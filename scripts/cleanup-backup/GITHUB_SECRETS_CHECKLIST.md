# 🔐 GitHub Secrets Configuration Checklist

## Required GitHub Repository Secrets

Copy these secrets to your GitHub repository: **Settings → Secrets and variables → Actions → New repository secret**

### 🧪 Staging Environment

```bash
# Server Connection
STAGING_HOST=your-staging-server.com
STAGING_USERNAME=dokterku-deploy
STAGING_SSH_KEY=-----BEGIN OPENSSH PRIVATE KEY-----
...your-staging-private-key...
-----END OPENSSH PRIVATE KEY-----
STAGING_SSH_PORT=22

# Paths & URLs  
STAGING_PATH=/var/www/dokterku-staging
STAGING_URL=https://staging.dokterku.com

# Database Credentials
STAGING_DB_HOST=127.0.0.1
STAGING_DB_PORT=3306
STAGING_DB_DATABASE=dokterku_staging
STAGING_DB_USERNAME=dokterku_staging_user
STAGING_DB_PASSWORD=secure_staging_db_password
STAGING_DB_ROOT_PASSWORD=secure_staging_root_password
```

### 🏥 Production Environment

```bash
# Server Connection
PRODUCTION_HOST=dokterku.com
PRODUCTION_USERNAME=dokterku-deploy
PRODUCTION_SSH_KEY=-----BEGIN OPENSSH PRIVATE KEY-----
...your-production-private-key...
-----END OPENSSH PRIVATE KEY-----
PRODUCTION_SSH_PORT=22

# Paths & URLs
PRODUCTION_PATH=/var/www/dokterku-production
PRODUCTION_URL=https://dokterku.com

# Database Credentials
PRODUCTION_DB_HOST=127.0.0.1
PRODUCTION_DB_PORT=3306
PRODUCTION_DB_DATABASE=dokterku_production
PRODUCTION_DB_USERNAME=dokterku_prod_user
PRODUCTION_DB_PASSWORD=ultra_secure_production_db_password
PRODUCTION_DB_ROOT_PASSWORD=ultra_secure_production_root_password
```

## 🔑 SSH Key Generation

Generate deployment-specific SSH keys:

```bash
# Generate SSH key pair for deployment
ssh-keygen -t ed25519 -C "dokterku-deployment" -f ~/.ssh/dokterku_deploy_key

# Copy public key to servers
ssh-copy-id -i ~/.ssh/dokterku_deploy_key.pub dokterku-deploy@your-staging-server.com
ssh-copy-id -i ~/.ssh/dokterku_deploy_key.pub dokterku-deploy@your-production-server.com

# Use private key content for STAGING_SSH_KEY and PRODUCTION_SSH_KEY secrets
cat ~/.ssh/dokterku_deploy_key
```

## 🌍 GitHub Environment Configuration

### Create Staging Environment
1. Go to **Settings → Environments → New environment**
2. Name: `staging`
3. Protection rules:
   - ✅ Required reviewers: 1 reviewer
   - ✅ Wait timer: 0 minutes  
   - ✅ Deployment branches: `develop` only

### Create Production Environment
1. Go to **Settings → Environments → New environment** 
2. Name: `production`
3. Protection rules:
   - ✅ Required reviewers: 2 reviewers (healthcare compliance)
   - ✅ Wait timer: 10 minutes (cooling period)
   - ✅ Deployment branches: `main` only

## 📋 Pre-Deployment Server Setup

### 1. Create Deployment User

```bash
# On each server (staging & production)
sudo adduser dokterku-deploy
sudo usermod -aG docker dokterku-deploy
sudo usermod -aG www-data dokterku-deploy

# Setup sudo without password for docker commands
echo "dokterku-deploy ALL=(ALL) NOPASSWD: /usr/bin/docker, /usr/local/bin/docker-compose" | sudo tee /etc/sudoers.d/dokterku-deploy
```

### 2. Create Directory Structure

```bash
# Staging directories
sudo mkdir -p /var/www/dokterku-staging/{backups,storage,bootstrap/cache}
sudo chown -R dokterku-deploy:www-data /var/www/dokterku-staging
sudo chmod -R 755 /var/www/dokterku-staging

# Production directories  
sudo mkdir -p /var/www/dokterku-production/{backups,storage,bootstrap/cache}
sudo chown -R dokterku-deploy:www-data /var/www/dokterku-production
sudo chmod -R 755 /var/www/dokterku-production
```

### 3. Install Dependencies

```bash
# Install Docker
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh
sudo usermod -aG docker dokterku-deploy

# Install Docker Compose
sudo curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose

# Install required utilities
sudo apt update
sudo apt install -y git curl wget nc
```

## 🧪 Testing the Pipeline

### 1. Test Staging Deployment

```bash
# Push to develop branch to trigger staging deployment
git checkout develop
git push origin develop

# Or manually trigger via GitHub Actions
# Actions → Modern CI/CD Pipeline → Run workflow
# Environment: staging
```

### 2. Test Production Deployment

```bash
# Push to main branch to trigger production deployment  
git checkout main
git merge develop
git push origin main

# Or manually trigger via GitHub Actions
# Actions → Modern CI/CD Pipeline → Run workflow
# Environment: production
```

## 🚨 Emergency Procedures

### Skip Tests (Emergency Only)
```bash
# Manual workflow dispatch with:
environment: production
skip_tests: true
force_deploy: true
```

### Manual Rollback
```bash
# SSH to server
ssh dokterku-deploy@your-server.com
cd /var/www/dokterku-production

# View available backups
ls -la backups/

# The workflow has automatic rollback on failure
# Or manually restore from backup if needed
```

## ✅ Deployment Checklist

Before first deployment, ensure:

- [ ] All GitHub secrets configured
- [ ] SSH keys generated and deployed to servers
- [ ] GitHub environments created with protection rules
- [ ] Server users and directories created
- [ ] Docker and Docker Compose installed on servers
- [ ] DNS records pointing to servers
- [ ] SSL certificates configured (for production)
- [ ] Database users and databases created
- [ ] Environment files (.env.staging, .env.production) updated
- [ ] Health endpoints (/health, /api/health) implemented
- [ ] Backup strategies tested

## 🔍 Monitoring & Health Checks

The pipeline includes comprehensive health checks:

### Application Health
- Database connectivity
- Redis connectivity  
- All 6 healthcare panels accessibility
- API endpoints functionality
- Response time performance

### Infrastructure Health
- Disk space usage
- Memory usage
- SSL certificate expiration (production)
- Application logs for errors

### Healthcare Panels Tested
- `/admin` - Admin Panel
- `/manajer` - Manager Panel  
- `/bendahara` - Finance Panel
- `/petugas` - Staff Panel
- `/paramedis` - Paramedic Panel
- `/dokter` - Doctor Panel

## 🆘 Support Contacts

- **DevOps Lead**: [your-devops-email]
- **Database Admin**: [your-dba-email]  
- **Security Team**: [your-security-email]
- **Healthcare IT**: [your-healthcare-it-email]

---

**Next Step**: Configure your GitHub secrets and run your first staging deployment!