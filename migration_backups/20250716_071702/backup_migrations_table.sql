-- Backup current migrations table
-- Run this in your database before making changes

-- Create backup table
CREATE TABLE IF NOT EXISTS migrations_backup_20250716_071702 AS SELECT * FROM migrations;

-- To restore:
-- DROP TABLE IF EXISTS migrations;
-- CREATE TABLE migrations AS SELECT * FROM migrations_backup_20250716_071702;
