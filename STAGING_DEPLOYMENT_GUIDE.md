# 🧪 Dokterku Healthcare System - Staging Deployment Guide

## Overview
This guide provides comprehensive instructions for setting up and deploying the Dokterku Healthcare System to a staging environment for testing and validation before production deployment.

## 🔧 Prerequisites

### Server Requirements
- **OS**: Ubuntu 20.04 LTS or newer
- **RAM**: Minimum 4GB, Recommended 8GB
- **Storage**: Minimum 50GB SSD
- **CPU**: 2+ cores
- **Network**: Stable internet connection

### Software Dependencies
```bash
# Update system packages
sudo apt update && sudo apt upgrade -y

# Install required packages
sudo apt install -y git curl wget unzip software-properties-common apt-transport-https ca-certificates gnupg lsb-release

# Install Docker
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh
sudo usermod -aG docker $USER

# Install Docker Compose
sudo curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose

# Verify installations
docker --version
docker-compose --version
```

## 🌐 Domain & DNS Setup

### Staging Domain Configuration
1. **Domain**: `staging.dokterku.com`
2. **DNS Records**:
   ```
   A     staging.dokterku.com    → Your_Staging_Server_IP
   CNAME www.staging.dokterku.com → staging.dokterku.com
   ```

### SSL Certificate Setup
```bash
# Install Certbot for Let's Encrypt
sudo apt install -y certbot python3-certbot-nginx

# Generate SSL certificate
sudo certbot certonly --standalone -d staging.dokterku.com

# Verify certificate
sudo certbot certificates
```

## 🏗️ Staging Environment Setup

### 1. Create Deployment User
```bash
# Create dedicated deployment user
sudo adduser dokterku-deploy
sudo usermod -aG docker dokterku-deploy
sudo usermod -aG sudo dokterku-deploy

# Switch to deployment user
sudo su - dokterku-deploy
```

### 2. Setup Project Directory
```bash
# Create project directory
mkdir -p /home/dokterku-deploy/dokterku-staging
cd /home/dokterku-deploy/dokterku-staging

# Clone repository (staging branch)
git clone -b develop https://github.com/your-username/dokterku.git .

# Set proper permissions
sudo chown -R dokterku-deploy:dokterku-deploy /home/dokterku-deploy/dokterku-staging
chmod +x deploy.sh
chmod +x docker/scripts/*.sh
```

### 3. Environment Configuration
```bash
# Copy staging environment file
cp .env.staging .env

# Configure environment variables
nano .env
```

**Key staging environment variables:**
```env
APP_NAME="Dokterku Healthcare (Staging)"
APP_ENV=staging
APP_DEBUG=true
APP_URL=https://staging.dokterku.com

# Database
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=dokterku_staging
DB_USERNAME=dokterku_staging_user
DB_PASSWORD=secure_staging_password

# Cache & Session
CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# Redis
REDIS_HOST=redis
REDIS_PORT=6379

# Mail (Staging - use Mailtrap or similar)
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_mailtrap_username
MAIL_PASSWORD=your_mailtrap_password
```

## 🐳 Docker Configuration

### 1. Docker Compose Staging Setup
The staging environment uses `docker-compose.staging.yml` with:
- **Nginx**: Reverse proxy with staging-specific configuration
- **App**: Laravel application container
- **MySQL**: Database with staging optimizations
- **Redis**: Cache and session storage
- **Queue Worker**: Background job processing
- **Scheduler**: Cron job handling

### 2. Build and Deploy
```bash
# Build staging environment
docker-compose -f docker-compose.staging.yml build

# Start services
docker-compose -f docker-compose.staging.yml up -d

# Check service status
docker-compose -f docker-compose.staging.yml ps
```

### 3. Initialize Application
```bash
# Run migrations and seeders
docker-compose -f docker-compose.staging.yml exec app php artisan migrate:fresh --seed

# Generate application key
docker-compose -f docker-compose.staging.yml exec app php artisan key:generate

# Cache configuration
docker-compose -f docker-compose.staging.yml exec app php artisan config:cache
docker-compose -f docker-compose.staging.yml exec app php artisan route:cache
docker-compose -f docker-compose.staging.yml exec app php artisan view:cache

# Create storage link
docker-compose -f docker-compose.staging.yml exec app php artisan storage:link

# Install and build frontend assets
docker-compose -f docker-compose.staging.yml exec app npm install
docker-compose -f docker-compose.staging.yml exec app npm run build
```

## 🏥 Healthcare System Validation

### 1. Panel Accessibility Test
```bash
# Test all healthcare panels
curl -I https://staging.dokterku.com/admin/login
curl -I https://staging.dokterku.com/manajer/login
curl -I https://staging.dokterku.com/bendahara/login
curl -I https://staging.dokterku.com/petugas/login
curl -I https://staging.dokterku.com/paramedis/login
curl -I https://staging.dokterku.com/dokter/login
```

### 2. Health Check Validation
```bash
# Application health check
curl https://staging.dokterku.com/health

# API health check
curl https://staging.dokterku.com/api/health

# Database connectivity test
docker-compose -f docker-compose.staging.yml exec app php artisan tinker --execute="DB::connection()->getPdo(); echo 'Database connected successfully';"
```

### 3. Create Test Users
```bash
# Create admin user
docker-compose -f docker-compose.staging.yml exec app php artisan tinker --execute="
\$user = \App\Models\User::create([
    'name' => 'Staging Admin',
    'email' => 'admin@staging.dokterku.com',
    'password' => bcrypt('staging123'),
    'email_verified_at' => now()
]);
\$adminRole = \App\Models\Role::where('name', 'admin')->first();
\$user->roles()->attach(\$adminRole->id);
echo 'Admin user created successfully';
"

# Create test users for each panel
docker-compose -f docker-compose.staging.yml exec app php artisan db:seed --class=StagingTestUsersSeeder
```

## 🔄 Automated Deployment

### 1. Using Deployment Script
```bash
# Deploy to staging
./deploy.sh staging

# The script will:
# - Validate environment
# - Pull latest images
# - Run zero-downtime deployment
# - Execute migrations
# - Optimize application
# - Run health checks
# - Validate healthcare panels
```

### 2. GitHub Actions Integration
The repository includes automated staging deployment via GitHub Actions:

**Trigger**: Push to `develop` branch
**Workflow**: `.github/workflows/modern-deployment.yml`

**Manual Trigger**:
1. Go to Actions tab in GitHub
2. Select "Modern CI/CD Pipeline"
3. Click "Run workflow"
4. Select environment: `staging`

## 🧪 Testing Procedures

### 1. Functional Testing Checklist
- [ ] All 6 healthcare panels accessible
- [ ] User authentication working
- [ ] Patient registration functional
- [ ] Medical procedure recording
- [ ] Financial transaction processing
- [ ] Report generation
- [ ] Mobile app connectivity (if applicable)

### 2. Performance Testing
```bash
# Run performance tests
docker-compose -f docker-compose.staging.yml exec app php artisan test --filter="Performance"

# Database query performance
docker-compose -f docker-compose.staging.yml exec app php artisan tinker --execute="
DB::enableQueryLog();
// Perform test operations
\$queries = DB::getQueryLog();
echo 'Query count: ' . count(\$queries);
"
```

### 3. Security Testing
```bash
# Run security tests
docker-compose -f docker-compose.staging.yml exec app php artisan test --filter="Security"

# Check for vulnerable packages
docker-compose -f docker-compose.staging.yml exec app composer audit
```

## 📊 Monitoring & Logging

### 1. Application Logs
```bash
# View application logs
docker-compose -f docker-compose.staging.yml logs -f app

# View specific log files
docker-compose -f docker-compose.staging.yml exec app tail -f storage/logs/laravel.log
```

### 2. Database Monitoring
```bash
# MySQL performance
docker-compose -f docker-compose.staging.yml exec mysql mysql -u root -p -e "SHOW PROCESSLIST;"

# Redis monitoring
docker-compose -f docker-compose.staging.yml exec redis redis-cli monitor
```

### 3. System Resources
```bash
# Container resource usage
docker stats

# Disk usage
df -h

# Memory usage
free -h
```

## 🔧 Maintenance Tasks

### 1. Regular Updates
```bash
# Update codebase
git pull origin develop

# Rebuild and deploy
./deploy.sh staging

# Update dependencies
docker-compose -f docker-compose.staging.yml exec app composer update
docker-compose -f docker-compose.staging.yml exec app npm update
```

### 2. Database Maintenance
```bash
# Backup database
docker-compose -f docker-compose.staging.yml exec mysql mysqldump -u root -p dokterku_staging > backup_$(date +%Y%m%d).sql

# Optimize database
docker-compose -f docker-compose.staging.yml exec mysql mysql -u root -p -e "OPTIMIZE TABLE mysql.user;"
```

### 3. Cache Management
```bash
# Clear all caches
docker-compose -f docker-compose.staging.yml exec app php artisan cache:clear
docker-compose -f docker-compose.staging.yml exec app php artisan view:clear
docker-compose -f docker-compose.staging.yml exec app php artisan route:clear
docker-compose -f docker-compose.staging.yml exec app php artisan config:clear
```

## 🚨 Troubleshooting

### Common Issues

#### 1. Container Won't Start
```bash
# Check container status
docker-compose -f docker-compose.staging.yml ps

# View container logs
docker-compose -f docker-compose.staging.yml logs container_name

# Restart specific service
docker-compose -f docker-compose.staging.yml restart service_name
```

#### 2. Database Connection Issues
```bash
# Check database container
docker-compose -f docker-compose.staging.yml exec mysql mysql -u root -p -e "SELECT 1;"

# Verify environment variables
docker-compose -f docker-compose.staging.yml exec app env | grep DB_
```

#### 3. SSL Certificate Issues
```bash
# Renew certificate
sudo certbot renew

# Check certificate status
sudo certbot certificates

# Restart Nginx
docker-compose -f docker-compose.staging.yml restart nginx
```

#### 4. Performance Issues
```bash
# Check resource usage
docker stats

# Analyze slow queries
docker-compose -f docker-compose.staging.yml exec mysql mysql -u root -p -e "SELECT * FROM mysql.slow_log ORDER BY start_time DESC LIMIT 10;"

# Clear Redis cache
docker-compose -f docker-compose.staging.yml exec redis redis-cli FLUSHALL
```

## 📞 Support Contacts

- **DevOps Team**: devops@dokterku.com
- **Development Team**: dev@dokterku.com
- **System Administrator**: admin@dokterku.com

## 📋 Staging Completion Checklist

- [ ] Server provisioned and configured
- [ ] Domain and SSL certificate setup
- [ ] Docker and Docker Compose installed
- [ ] Application deployed successfully
- [ ] All healthcare panels accessible
- [ ] Database populated with test data
- [ ] Health checks passing
- [ ] Performance tests completed
- [ ] Security tests passed
- [ ] Monitoring and logging configured
- [ ] Backup procedures tested
- [ ] Documentation updated
- [ ] Team notified of staging environment availability

---

**Next Step**: Once staging validation is complete, proceed with production deployment using the Production Deployment Guide.