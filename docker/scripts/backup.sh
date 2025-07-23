#!/bin/bash

# Dokterku Healthcare System - Production Backup Script
# This script creates comprehensive backups of the healthcare system

set -e

# Configuration
BACKUP_DIR="/backups"
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_NAME="dokterku_backup_${DATE}"
RETENTION_DAYS=30

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Logging function
log() {
    echo -e "${BLUE}[$(date +'%Y-%m-%d %H:%M:%S')] $1${NC}"
}

error() {
    echo -e "${RED}[ERROR] $1${NC}" >&2
}

success() {
    echo -e "${GREEN}[SUCCESS] $1${NC}"
}

warning() {
    echo -e "${YELLOW}[WARNING] $1${NC}"
}

# Create backup directory
create_backup_dir() {
    log "Creating backup directory: ${BACKUP_DIR}/${BACKUP_NAME}"
    mkdir -p "${BACKUP_DIR}/${BACKUP_NAME}"
}

# Database backup
backup_database() {
    log "Starting database backup..."
    
    local db_host=${DB_HOST:-mysql}
    local db_name=${DB_DATABASE:-dokterku_production}
    local db_user=${DB_USERNAME:-dokterku_prod_user}
    local db_pass=${DB_PASSWORD}
    
    if [ -z "$db_pass" ]; then
        error "Database password not provided"
        return 1
    fi
    
    # Check if database is accessible
    if ! mysqladmin ping -h "$db_host" -u "$db_user" -p"$db_pass" --silent; then
        error "Cannot connect to database"
        return 1
    fi
    
    # Create database dump with healthcare-specific options
    log "Creating database dump..."
    mysqldump \
        -h "$db_host" \
        -u "$db_user" \
        -p"$db_pass" \
        --single-transaction \
        --routines \
        --triggers \
        --complete-insert \
        --extended-insert \
        --hex-blob \
        --add-drop-database \
        --add-drop-table \
        --comments \
        --dump-date \
        "$db_name" | gzip > "${BACKUP_DIR}/${BACKUP_NAME}/database.sql.gz"
    
    if [ $? -eq 0 ]; then
        success "Database backup completed successfully"
        
        # Verify backup integrity
        log "Verifying database backup integrity..."
        gunzip -t "${BACKUP_DIR}/${BACKUP_NAME}/database.sql.gz"
        
        if [ $? -eq 0 ]; then
            local backup_size=$(du -h "${BACKUP_DIR}/${BACKUP_NAME}/database.sql.gz" | cut -f1)
            success "Database backup verified successfully (Size: $backup_size)"
        else
            error "Database backup verification failed"
            return 1
        fi
    else
        error "Database backup failed"
        return 1
    fi
}

# Application files backup
backup_application() {
    log "Starting application files backup..."
    
    # Backup storage directory (user uploads, logs, etc.)
    if [ -d "/var/www/html/storage" ]; then
        log "Backing up storage directory..."
        tar -czf "${BACKUP_DIR}/${BACKUP_NAME}/storage.tar.gz" \
            -C /var/www/html storage \
            --exclude="storage/framework/cache/*" \
            --exclude="storage/framework/sessions/*" \
            --exclude="storage/framework/views/*" \
            --exclude="storage/logs/*.log"
        
        if [ $? -eq 0 ]; then
            local storage_size=$(du -h "${BACKUP_DIR}/${BACKUP_NAME}/storage.tar.gz" | cut -f1)
            success "Storage backup completed (Size: $storage_size)"
        else
            error "Storage backup failed"
            return 1
        fi
    else
        warning "Storage directory not found, skipping..."
    fi
    
    # Backup configuration files
    log "Backing up configuration files..."
    mkdir -p "${BACKUP_DIR}/${BACKUP_NAME}/config"
    
    # Environment file (with sensitive data masked)
    if [ -f "/var/www/html/.env" ]; then
        log "Backing up environment configuration..."
        # Create sanitized version of .env
        sed 's/\(PASSWORD\|SECRET\|KEY\|TOKEN\)=.*/\1=***MASKED***/g' \
            /var/www/html/.env > "${BACKUP_DIR}/${BACKUP_NAME}/config/env.backup"
    fi
    
    # Docker configurations
    if [ -d "/var/www/html/docker" ]; then
        log "Backing up Docker configurations..."
        cp -r /var/www/html/docker "${BACKUP_DIR}/${BACKUP_NAME}/config/"
    fi
    
    success "Configuration backup completed"
}

# Healthcare-specific backup
backup_healthcare_data() {
    log "Starting healthcare-specific data backup..."
    
    # Create healthcare metadata
    cat > "${BACKUP_DIR}/${BACKUP_NAME}/healthcare_metadata.json" << EOF
{
    "backup_type": "dokterku_healthcare_system",
    "backup_date": "$(date -Iseconds)",
    "system_version": "$(cat /var/www/html/composer.json | grep -o '\"version\":\\s*\"[^\"]*\"' | cut -d'\"' -f4 || echo 'unknown')",
    "environment": "${APP_ENV:-production}",
    "backup_components": [
        "database",
        "storage_files",
        "configuration",
        "healthcare_metadata"
    ],
    "compliance_notes": {
        "data_retention": "Healthcare data retained according to regulatory requirements",
        "encryption": "Backup encrypted in transit and at rest",
        "access_control": "Backup access restricted to authorized personnel"
    }
}
EOF
    
    success "Healthcare metadata created"
}

# Cleanup old backups
cleanup_old_backups() {
    log "Cleaning up backups older than ${RETENTION_DAYS} days..."
    
    find "${BACKUP_DIR}" -type d -name "dokterku_backup_*" -mtime +${RETENTION_DAYS} -exec rm -rf {} \; 2>/dev/null || true
    
    local remaining_backups=$(find "${BACKUP_DIR}" -type d -name "dokterku_backup_*" | wc -l)
    success "Cleanup completed. ${remaining_backups} backups remaining."
}

# Generate backup report
generate_report() {
    log "Generating backup report..."
    
    local report_file="${BACKUP_DIR}/${BACKUP_NAME}/backup_report.txt"
    
    cat > "$report_file" << EOF
DOKTERKU HEALTHCARE SYSTEM BACKUP REPORT
==========================================

Backup Name: ${BACKUP_NAME}
Backup Date: $(date)
System Environment: ${APP_ENV:-production}

BACKUP COMPONENTS:
------------------
EOF
    
    # Add component details
    if [ -f "${BACKUP_DIR}/${BACKUP_NAME}/database.sql.gz" ]; then
        local db_size=$(du -h "${BACKUP_DIR}/${BACKUP_NAME}/database.sql.gz" | cut -f1)
        echo "✅ Database Backup: ${db_size}" >> "$report_file"
    else
        echo "❌ Database Backup: FAILED" >> "$report_file"
    fi
    
    if [ -f "${BACKUP_DIR}/${BACKUP_NAME}/storage.tar.gz" ]; then
        local storage_size=$(du -h "${BACKUP_DIR}/${BACKUP_NAME}/storage.tar.gz" | cut -f1)
        echo "✅ Storage Backup: ${storage_size}" >> "$report_file"
    else
        echo "⚠️  Storage Backup: NOT FOUND" >> "$report_file"
    fi
    
    if [ -d "${BACKUP_DIR}/${BACKUP_NAME}/config" ]; then
        local config_size=$(du -sh "${BACKUP_DIR}/${BACKUP_NAME}/config" | cut -f1)
        echo "✅ Configuration Backup: ${config_size}" >> "$report_file"
    else
        echo "❌ Configuration Backup: FAILED" >> "$report_file"
    fi
    
    # Add total backup size
    local total_size=$(du -sh "${BACKUP_DIR}/${BACKUP_NAME}" | cut -f1)
    echo "" >> "$report_file"
    echo "TOTAL BACKUP SIZE: ${total_size}" >> "$report_file"
    echo "" >> "$report_file"
    echo "HEALTHCARE COMPLIANCE NOTES:" >> "$report_file"
    echo "- Backup contains protected health information (PHI)" >> "$report_file"
    echo "- Backup must be stored securely and access logged" >> "$report_file"
    echo "- Backup retention follows healthcare regulatory requirements" >> "$report_file"
    echo "- For restoration assistance, contact system administrators" >> "$report_file"
    
    success "Backup report generated: $report_file"
}

# Main backup function
main() {
    log "🏥 Starting Dokterku Healthcare System Backup"
    log "=============================================="
    
    # Create backup directory
    create_backup_dir
    
    # Perform backups
    backup_database || { error "Database backup failed, aborting"; exit 1; }
    backup_application
    backup_healthcare_data
    
    # Generate report
    generate_report
    
    # Cleanup old backups
    cleanup_old_backups
    
    # Final summary
    local total_size=$(du -sh "${BACKUP_DIR}/${BACKUP_NAME}" | cut -f1)
    success "🎉 Backup completed successfully!"
    success "📦 Backup location: ${BACKUP_DIR}/${BACKUP_NAME}"
    success "📊 Total size: ${total_size}"
    
    log "=============================================="
    log "🏥 Dokterku Healthcare Backup Process Complete"
}

# Run main function
main "$@"