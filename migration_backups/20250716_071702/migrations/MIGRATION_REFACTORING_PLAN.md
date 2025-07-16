# Migration Refactoring Plan

## Executive Summary
This document outlines a comprehensive plan to merge and consolidate database migrations for the Dokterku application. The goal is to reduce migration count, improve performance, and maintain database integrity.

## 1. Safe Merge Candidates

### 1.1 Users Table Modifications (HIGH PRIORITY)
**Files to Merge:**
- `2025_07_11_092700_add_role_id_to_users_table.php`
- `2025_07_12_225550_add_username_to_users_table.php`
- `2025_07_15_070054_add_profile_settings_to_users_table.php`
- `2025_07_15_095251_make_role_id_nullable_in_users_table.php`
- `2025_07_15_231720_add_pegawai_id_to_users_table.php`

**New Consolidated Migration:**
```php
// 2025_07_11_092700_enhance_users_table_complete.php
Schema::table('users', function (Blueprint $table) {
    // Core fields
    $table->foreignId('role_id')->nullable()->after('id')->constrained('roles')->onDelete('cascade');
    $table->string('username')->unique()->nullable()->after('email');
    $table->string('nip')->unique()->nullable()->after('username');
    $table->string('no_telepon')->nullable()->after('nip');
    $table->date('tanggal_bergabung')->nullable()->after('no_telepon');
    $table->boolean('is_active')->default(true)->after('tanggal_bergabung');
    
    // Profile fields
    $table->string('phone')->nullable()->after('email');
    $table->text('address')->nullable();
    $table->text('bio')->nullable();
    $table->date('date_of_birth')->nullable();
    $table->enum('gender', ['male', 'female'])->nullable();
    $table->string('emergency_contact_name')->nullable();
    $table->string('emergency_contact_phone')->nullable();
    $table->string('profile_photo_path')->nullable();
    
    // Work settings
    $table->unsignedBigInteger('default_work_location_id')->nullable();
    $table->boolean('auto_check_out')->default(false);
    $table->boolean('overtime_alerts')->default(true);
    
    // Notification settings
    $table->boolean('email_notifications')->default(true);
    $table->boolean('push_notifications')->default(true);
    $table->boolean('attendance_reminders')->default(true);
    $table->boolean('schedule_updates')->default(true);
    
    // Privacy settings
    $table->enum('profile_visibility', ['public', 'colleagues', 'private'])->default('colleagues');
    $table->boolean('location_sharing')->default(true);
    $table->boolean('activity_status')->default(true);
    
    // App settings
    $table->string('language', 5)->default('id');
    $table->string('timezone')->default('Asia/Jakarta');
    $table->enum('theme', ['light', 'dark', 'auto'])->default('light');
    
    // Additional relations
    $table->foreignId('pegawai_id')->nullable()->constrained('pegawais')->onDelete('cascade');
    
    // Timestamps
    $table->softDeletes();
    
    // Indexes
    $table->index('role_id');
    $table->index('nip');
    $table->index('is_active');
    $table->index('pegawai_id');
    
    // Foreign keys
    $table->foreign('default_work_location_id')->references('id')->on('work_locations')->onDelete('set null');
});
```

**Risk Assessment:** MEDIUM
- Dependencies: Requires roles, pegawais, and work_locations tables to exist
- Rollback: Complex due to multiple fields, but manageable

### 1.2 Pendapatan Table Modifications
**Files to Merge:**
- `2025_07_11_125444_add_new_fields_to_pendapatan_table.php`
- `2025_07_11_125722_update_pendapatan_table_nullable_fields.php`
- `2025_07_11_160519_add_is_aktif_to_pendapatan_table.php`

**New Consolidated Migration:**
```php
// 2025_07_11_125444_enhance_pendapatan_table_complete.php
Schema::table('pendapatan', function (Blueprint $table) {
    // New fields
    $table->date('tanggal_tindakan')->nullable()->after('jumlah');
    $table->string('nama_pasien')->nullable()->after('tanggal_tindakan');
    $table->decimal('biaya_tindakan', 15, 2)->nullable()->after('nama_pasien');
    $table->text('catatan')->nullable()->after('biaya_tindakan');
    $table->enum('status', ['draft', 'confirmed', 'cancelled'])->default('confirmed')->after('catatan');
    $table->boolean('is_aktif')->default(true);
    
    // Update existing fields to nullable
    $table->unsignedBigInteger('tindakan_id')->nullable()->change();
    $table->unsignedBigInteger('dokter_id')->nullable()->change();
    $table->decimal('biaya_jasa', 15, 2)->nullable()->change();
    
    // Indexes
    $table->index('tanggal_tindakan');
    $table->index('status');
    $table->index('is_aktif');
});
```

**Risk Assessment:** LOW
- Simple column additions and modifications
- No complex dependencies

### 1.3 Tindakan Table Modifications
**Files to Merge:**
- `2025_07_11_123000_add_input_by_to_tindakan_table.php`
- `2025_07_13_100339_add_validation_fields_to_tindakan_table.php`
- `2025_07_13_100412_fix_foreign_keys_in_tindakan_table.php`
- `2025_07_13_100434_make_dokter_id_nullable_in_tindakan_table.php`

**New Consolidated Migration:**
```php
// 2025_07_11_123000_enhance_tindakan_table_complete.php
Schema::table('tindakan', function (Blueprint $table) {
    // Drop existing foreign keys first
    $table->dropForeign(['pasien_id']);
    $table->dropForeign(['dokter_id']);
    
    // Add input_by field
    $table->foreignId('input_by')->nullable()->after('dokter_id')->constrained('users');
    
    // Add validation fields
    $table->enum('status_validasi', ['pending', 'validated', 'rejected'])->default('pending');
    $table->unsignedBigInteger('validated_by')->nullable();
    $table->timestamp('validated_at')->nullable();
    $table->text('validation_notes')->nullable();
    $table->enum('validation_method', ['manual', 'auto', 'system'])->default('manual');
    $table->json('validation_data')->nullable();
    $table->string('validation_hash')->nullable();
    $table->boolean('requires_review')->default(false);
    $table->unsignedInteger('review_priority')->default(0);
    $table->timestamp('review_deadline')->nullable();
    
    // Make dokter_id nullable
    $table->unsignedBigInteger('dokter_id')->nullable()->change();
    
    // Re-add foreign keys with proper constraints
    $table->foreign('pasien_id')->references('id')->on('pasien')->onDelete('restrict');
    $table->foreign('dokter_id')->references('id')->on('dokters')->onDelete('restrict');
    $table->foreign('validated_by')->references('id')->on('users')->onDelete('set null');
    
    // Add indexes
    $table->index('status_validasi');
    $table->index('validated_by');
    $table->index('validated_at');
    $table->index('requires_review');
    $table->index('review_priority');
});
```

**Risk Assessment:** MEDIUM
- Foreign key modifications require careful handling
- Order of operations is critical

### 1.4 Pegawais Table Modifications
**Files to Merge:**
- `2025_07_11_233203_update_pegawais_table_make_nik_required.php`
- `2025_07_13_000205_add_user_id_to_pegawais_table.php`
- `2025_07_13_075245_add_login_fields_to_pegawais_table.php`

**New Consolidated Migration:**
```php
// 2025_07_11_233203_enhance_pegawais_table_complete.php
Schema::table('pegawais', function (Blueprint $table) {
    // Make NIK required
    $table->string('nik')->nullable(false)->change();
    
    // Add user relationship
    $table->foreignId('user_id')->nullable()->after('id');
    $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
    
    // Add login fields
    $table->string('username')->unique()->nullable();
    $table->string('password')->nullable();
    $table->string('pin', 6)->nullable();
    $table->boolean('is_active')->default(true);
    $table->timestamp('last_login_at')->nullable();
    $table->string('last_login_ip')->nullable();
    $table->integer('failed_login_attempts')->default(0);
    $table->timestamp('locked_until')->nullable();
    $table->rememberToken();
    
    // Add indexes
    $table->unique('nik');
    $table->index('user_id');
    $table->index('username');
    $table->index('is_active');
});
```

**Risk Assessment:** LOW
- Straightforward column additions
- No complex dependencies

### 1.5 GPS Spoofing System Tables
**Files to Merge:**
- `2025_07_11_225513_create_gps_spoofing_detections_table.php`
- `2025_07_11_230950_create_gps_spoofing_settings_table.php`
- `2025_07_12_005224_create_gps_spoofing_configs_table.php`
- `2025_07_12_013248_add_device_limit_settings_to_gps_spoofing_configs_table.php`

**New Consolidated Migration:**
```php
// 2025_07_11_225513_create_gps_spoofing_system_tables.php
// Create all GPS spoofing related tables in one migration
Schema::create('gps_spoofing_detections', function (Blueprint $table) {
    // Detection table schema
});

Schema::create('gps_spoofing_settings', function (Blueprint $table) {
    // Settings table schema
});

Schema::create('gps_spoofing_configs', function (Blueprint $table) {
    // Configs table schema with device limit settings included
});
```

**Risk Assessment:** LOW
- These are new table creations that can be safely merged
- No alterations to existing tables

### 1.6 Attendances Table Modifications
**Files to Merge:**
- `2025_07_11_165455_add_device_fields_to_attendances_table.php`
- `2025_07_14_010934_add_gps_fields_to_attendances_table.php`

**New Consolidated Migration:**
```php
// 2025_07_11_165455_enhance_attendances_table_complete.php
Schema::table('attendances', function (Blueprint $table) {
    // Device fields
    $table->foreignId('user_device_id')->nullable()->after('user_id')->constrained('user_devices');
    $table->string('device_name')->nullable();
    $table->string('device_id')->nullable();
    $table->string('ip_address')->nullable();
    $table->text('user_agent')->nullable();
    $table->string('platform')->nullable();
    $table->string('browser')->nullable();
    
    // GPS fields
    $table->decimal('check_in_latitude', 10, 8)->nullable();
    $table->decimal('check_in_longitude', 11, 8)->nullable();
    $table->decimal('check_out_latitude', 10, 8)->nullable();
    $table->decimal('check_out_longitude', 11, 8)->nullable();
    $table->integer('check_in_accuracy')->nullable();
    $table->integer('check_out_accuracy')->nullable();
    $table->string('check_in_address')->nullable();
    $table->string('check_out_address')->nullable();
    $table->json('check_in_gps_metadata')->nullable();
    $table->json('check_out_gps_metadata')->nullable();
    
    // Indexes
    $table->index('user_device_id');
    $table->index('device_id');
    $table->index(['check_in_latitude', 'check_in_longitude']);
    $table->index(['check_out_latitude', 'check_out_longitude']);
});
```

**Risk Assessment:** LOW
- Simple column additions
- No complex dependencies

## 2. Naming Conventions for Merged Files

### Pattern: `{date}_{operation}_{table}_table_{scope}.php`

Examples:
- `2025_07_11_enhance_users_table_complete.php`
- `2025_07_11_enhance_pendapatan_table_complete.php`
- `2025_07_11_create_gps_spoofing_system_tables.php`

### Rules:
1. Use earliest date from merged migrations
2. Use "enhance" for modifications, "create" for new tables
3. Add "complete" suffix for comprehensive modifications
4. Group related tables with "system" suffix

## 3. Backup Strategy

### 3.1 Pre-Refactoring Backup
```bash
# Create backup directory
mkdir -p database/migrations/backup_$(date +%Y%m%d_%H%M%S)

# Copy all current migrations
cp database/migrations/*.php database/migrations/backup_$(date +%Y%m%d_%H%M%S)/

# Create manifest file
ls -la database/migrations/*.php > database/migrations/backup_$(date +%Y%m%d_%H%M%S)/manifest.txt
```

### 3.2 Migration History Backup
```sql
-- Backup current migration history
CREATE TABLE migrations_backup_20250715 AS SELECT * FROM migrations;
```

### 3.3 Rollback Strategy
1. Keep original migrations in `storage/old-migrations/` directory
2. Document merge mapping in `MIGRATION_MERGE_MAP.json`
3. Create rollback script to restore original state if needed

## 4. Implementation Steps

### Phase 1: Preparation
1. Run full database backup
2. Export current migration history
3. Create backup directory structure
4. Document current database state

### Phase 2: Merge Creation
1. Create new merged migration files
2. Test each merged migration individually
3. Verify up() and down() methods work correctly
4. Check foreign key dependencies

### Phase 3: Migration Update
1. Update migrations table to reflect new structure
2. Remove old migration entries
3. Add new merged migration entries with same batch numbers

### Phase 4: Validation
1. Run `php artisan migrate:status`
2. Test rollback of merged migrations
3. Verify database structure matches original
4. Run application tests

### Phase 5: Cleanup
1. Move old migrations to backup directory
2. Update documentation
3. Commit changes with detailed message

## 5. Risk Mitigation

### High-Risk Operations
1. Foreign key modifications - Always drop before recreating
2. Unique constraints - Check for duplicates before adding
3. Not null constraints - Ensure data exists before enforcing

### Validation Checklist
- [ ] All table dependencies are maintained
- [ ] Foreign key relationships are preserved
- [ ] Indexes are properly recreated
- [ ] Default values are maintained
- [ ] Nullable fields remain nullable
- [ ] Data types are unchanged

### Rollback Plan
If issues arise:
1. Restore original migrations from backup
2. Restore migrations table from backup
3. Run `php artisan migrate:refresh` if needed
4. Verify application functionality

## 6. Benefits of Refactoring

1. **Performance**: Fewer migration files to process
2. **Clarity**: Related changes grouped together
3. **Maintenance**: Easier to understand table evolution
4. **Deployment**: Faster migration execution
5. **Testing**: Simpler migration testing process

## 7. Post-Refactoring Tasks

1. Update deployment documentation
2. Update developer onboarding guide
3. Create migration style guide
4. Set up CI/CD migration validation
5. Document merge mapping for future reference