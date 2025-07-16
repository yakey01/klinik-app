#!/bin/bash

# Laravel Migration Backup Script
# Purpose: Safely backup all migration files before refactoring
# Usage: ./backup_migrations.sh

set -e

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Configuration
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="migration_backups/${TIMESTAMP}"
MIGRATION_DIR="database/migrations"
PROJECT_ROOT=$(pwd)

echo -e "${GREEN}=== Laravel Migration Backup Script ===${NC}"
echo -e "Timestamp: ${TIMESTAMP}"
echo ""

# Create backup directory
echo -e "${YELLOW}Creating backup directory...${NC}"
mkdir -p "${BACKUP_DIR}"

# Backup all migration files
echo -e "${YELLOW}Backing up migration files...${NC}"
cp -R "${MIGRATION_DIR}" "${BACKUP_DIR}/"
MIGRATION_COUNT=$(find "${BACKUP_DIR}/migrations" -name "*.php" | wc -l)
echo -e "${GREEN}✓ Backed up ${MIGRATION_COUNT} migration files${NC}"

# Create migration table backup SQL
echo -e "${YELLOW}Generating migration table backup SQL...${NC}"
cat > "${BACKUP_DIR}/backup_migrations_table.sql" << 'EOF'
-- Backup current migrations table
-- Run this in your database before making changes

-- Create backup table
CREATE TABLE IF NOT EXISTS migrations_backup_TIMESTAMP AS SELECT * FROM migrations;

-- To restore:
-- DROP TABLE IF EXISTS migrations;
-- CREATE TABLE migrations AS SELECT * FROM migrations_backup_TIMESTAMP;
EOF

# Replace TIMESTAMP in SQL
sed -i.bak "s/TIMESTAMP/${TIMESTAMP}/g" "${BACKUP_DIR}/backup_migrations_table.sql"
rm "${BACKUP_DIR}/backup_migrations_table.sql.bak"

# Create restore script
echo -e "${YELLOW}Creating restore script...${NC}"
cat > "${BACKUP_DIR}/restore_migrations.sh" << EOF
#!/bin/bash

# Migration Restore Script
# Created: ${TIMESTAMP}

set -e

echo "=== Migration Restore Script ==="
echo "This will restore migrations from backup: ${TIMESTAMP}"
echo ""
read -p "Are you sure you want to restore? (y/N) " -n 1 -r
echo ""

if [[ \$REPLY =~ ^[Yy]$ ]]; then
    echo "Restoring migration files..."
    
    # Backup current migrations first
    if [ -d "../../${MIGRATION_DIR}" ]; then
        mv "../../${MIGRATION_DIR}" "../../${MIGRATION_DIR}.before_restore"
    fi
    
    # Restore from backup
    cp -R "migrations" "../../${MIGRATION_DIR}"
    
    echo "✓ Migration files restored"
    echo ""
    echo "To restore the database migrations table, run:"
    echo "mysql -u your_user -p your_database < backup_migrations_table.sql"
    echo ""
    echo "Then run: php artisan migrate:status"
else
    echo "Restore cancelled"
fi
EOF

chmod +x "${BACKUP_DIR}/restore_migrations.sh"

# Create documentation
echo -e "${YELLOW}Creating backup documentation...${NC}"
cat > "${BACKUP_DIR}/README.md" << EOF
# Migration Backup - ${TIMESTAMP}

## Contents
- **migrations/**: Complete copy of all migration files
- **backup_migrations_table.sql**: SQL to backup migrations table
- **restore_migrations.sh**: Script to restore migrations
- **migration_list.txt**: List of all backed up migrations

## Backup Stats
- Total migrations: ${MIGRATION_COUNT}
- Backup created: $(date)
- Laravel project: ${PROJECT_ROOT}

## How to Restore

### 1. Restore Migration Files
\`\`\`bash
cd ${BACKUP_DIR}
./restore_migrations.sh
\`\`\`

### 2. Restore Database Migrations Table
\`\`\`bash
mysql -u your_user -p your_database < backup_migrations_table.sql
\`\`\`

### 3. Verify
\`\`\`bash
php artisan migrate:status
\`\`\`

## Important Notes
- Always test restore process in development first
- The backup includes ALL files in the migrations directory
- Database backup SQL needs to be run manually
EOF

# Create migration list
echo -e "${YELLOW}Creating migration list...${NC}"
find "${BACKUP_DIR}/migrations" -name "*.php" -type f | sort > "${BACKUP_DIR}/migration_list.txt"

# Summary
echo ""
echo -e "${GREEN}=== Backup Complete ===${NC}"
echo -e "Location: ${BACKUP_DIR}"
echo -e "Files backed up: ${MIGRATION_COUNT}"
echo ""
echo -e "${YELLOW}Next steps:${NC}"
echo "1. Run the SQL backup: cat ${BACKUP_DIR}/backup_migrations_table.sql"
echo "2. Test restore process: cd ${BACKUP_DIR} && ./restore_migrations.sh"
echo "3. Keep this backup until refactoring is complete and tested"
echo ""
echo -e "${GREEN}Backup successful!${NC}"