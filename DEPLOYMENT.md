# 🚀 Safe Production Deployment Guide

Complete guide for safely deploying Dokterku to production with backup and rollback capabilities.

## 🔧 Setup Process

### 1. Configure SSH Key Authentication

```bash
# Generate SSH keys and configure connection
./scripts/setup-ssh-keys.sh
```

**Manual Steps:**
1. Copy the generated public key
2. Log into Hostinger control panel
3. Go to: **Advanced → SSH Access → Manage SSH Keys**
4. Add the public key
5. Test connection: `ssh dokterku-hostinger`

### 2. Configure Production Environment

```bash
# Copy and edit production environment
cp .env.production.example .env.production
nano .env.production
```

**Required Configuration:**
- Database credentials
- Admin user details
- Mail settings
- Security settings

### 3. Test Connection

```bash
# Test SSH connection
ssh dokterku-hostinger

# Test database access
ssh dokterku-hostinger "mysql -u u454362045_u45436245_kli -p'LaTahzan@01' u454362045_u45436245_kli -e 'SHOW TABLES;'"
```

## 📦 Deployment Options

### Full System Deployment

```bash
# Complete deployment with backup
./scripts/deploy-to-production.sh

# Skip backup (faster)
./scripts/deploy-to-production.sh --no-backup

# Force deployment without prompts
./scripts/deploy-to-production.sh --force
```

### Admin-Only Deployment

```bash
# Deploy only admin data (safer for user data)
./scripts/deploy-to-production.sh --admin-only

# Quick admin replacement
./scripts/deploy-to-production.sh --admin-only --no-backup --force
```

### Dry Run (Test Without Changes)

```bash
# See what would be deployed without executing
./scripts/deploy-to-production.sh --dry-run
```

## 🛡️ Safety Features

### 1. Automatic Backup System

**Local Backup:**
```bash
# Manual backup
./scripts/backup-production.sh
```

**Backup Location:** `storage/backups/`
**Backup Format:** `dokterku_backup_YYYYMMDD_HHMMSS.sql`

### 2. Laravel Artisan Commands

**Safe Production Commands:**
```bash
# Deploy with backup and verification
php artisan deploy:production --backup

# Admin-only deployment
php artisan deploy:production --admin-only

# Force deployment (skip prompts)
php artisan deploy:production --force
```

### 3. Pre-deployment Checks

✅ **Database connection**  
✅ **Required tables exist**  
✅ **File permissions**  
✅ **SSH connectivity**  
✅ **Environment configuration**

### 4. Post-deployment Verification

✅ **Admin user exists**  
✅ **Database integrity**  
✅ **Site accessibility**  
✅ **Admin panel access**

## 📊 Admin & User Data

### Current Seeders Available

- **ProductionAdminReplacementSeeder** - Creates admin users
- **ParamedisRealDataSeeder** - Paramedis staff data
- **RolePermissionSeeder** - User roles and permissions
- **SystemSettingsSeeder** - System configuration

### Admin Credentials

**Default Production Admin:**
- **Email:** admin@dokterku.com
- **Password:** dokterku_admin_2024
- **Username:** admin

*Configure in `.env.production`:*
```env
PRODUCTION_ADMIN_EMAIL=admin@dokterku.com
PRODUCTION_ADMIN_PASSWORD=your_secure_password
PRODUCTION_ADMIN_NAME=Administrator
PRODUCTION_ADMIN_USERNAME=admin
```

## 🔄 Rollback Procedures

### 1. Database Rollback

```bash
# List available backups
ls -la storage/backups/

# Restore from backup
ssh dokterku-hostinger
mysql -u u454362045_u45436245_kli -p'LaTahzan@01' u454362045_u45436245_kli < backup_file.sql
```

### 2. Code Rollback

```bash
# Rollback to previous Git commit
git log --oneline -10
git checkout [previous-commit-hash]
./scripts/deploy-to-production.sh --force
```

## 🚨 Emergency Procedures

### 1. Site Down

```bash
# Quick diagnostics
curl -I https://dokterkuklinik.com
ssh dokterku-hostinger "tail -f /domains/dokterkuklinik.com/public_html/storage/logs/laravel.log"
```

### 2. Admin Access Lost

```bash
# Recreate admin user
ssh dokterku-hostinger
cd /domains/dokterkuklinik.com/public_html
php artisan db:seed --class=ProductionAdminReplacementSeeder --force
```

### 3. Database Corruption

```bash
# Restore from latest backup
ssh dokterku-hostinger
cd /domains/dokterkuklinik.com/public_html
mysql -u u454362045_u45436245_kli -p'LaTahzan@01' u454362045_u45436245_kli < /path/to/backup.sql
php artisan migrate --force
```

## 📞 Support Contacts

**Hostinger Details:**
- **Host:** 153.92.8.132:65002
- **User:** u454362045
- **Database:** u454362045_u45436245_kli
- **Site:** https://dokterkuklinik.com

**Key Files:**
- **SSH Config:** ~/.ssh/config (dokterku-hostinger)
- **Production Env:** .env.production
- **Backup Scripts:** scripts/
- **Seeders:** database/seeders/

## ⚡ Quick Commands Reference

```bash
# Setup SSH keys
./scripts/setup-ssh-keys.sh

# Create backup
./scripts/backup-production.sh

# Deploy admin only
./scripts/deploy-to-production.sh --admin-only

# Full deployment
./scripts/deploy-to-production.sh

# Test deployment
./scripts/deploy-to-production.sh --dry-run

# Emergency admin recreation
ssh dokterku-hostinger "cd /domains/dokterkuklinik.com/public_html && php artisan db:seed --class=ProductionAdminReplacementSeeder --force"
```

---

**⚠️ Always backup before deployment!**  
**📧 Test admin access after deployment!**  
**🔍 Monitor logs for the first 24 hours!**