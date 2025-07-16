#!/bin/bash

# Migration Restore Script
# Created: 20250716_071702

set -e

echo "=== Migration Restore Script ==="
echo "This will restore migrations from backup: 20250716_071702"
echo ""
read -p "Are you sure you want to restore? (y/N) " -n 1 -r
echo ""

if [[ $REPLY =~ ^[Yy]$ ]]; then
    echo "Restoring migration files..."
    
    # Backup current migrations first
    if [ -d "../../database/migrations" ]; then
        mv "../../database/migrations" "../../database/migrations.before_restore"
    fi
    
    # Restore from backup
    cp -R "migrations" "../../database/migrations"
    
    echo "✓ Migration files restored"
    echo ""
    echo "To restore the database migrations table, run:"
    echo "mysql -u your_user -p your_database < backup_migrations_table.sql"
    echo ""
    echo "Then run: php artisan migrate:status"
else
    echo "Restore cancelled"
fi
