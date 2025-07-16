# Database Migration Analysis Report

Generated on: 2025-07-15

## Migration Summary

Total migration files: 94

## 1. Table Creation Migrations

### Core System Tables
- **0001_01_01_000000_create_users_table.php**
  - Tables: users, password_reset_tokens, sessions
  - Type: Initial Laravel setup
  
- **0001_01_01_000001_create_cache_table.php**
  - Tables: cache, cache_locks
  - Type: Laravel cache system
  
- **0001_01_01_000002_create_jobs_table.php**
  - Tables: jobs, job_batches, failed_jobs
  - Type: Laravel queue system

### Authentication & Authorization
- **2025_07_11_092652_create_roles_table.php**
  - Table: roles
  - Dependencies: None
  
- **2025_07_11_101747_add_spatie_permission_support.php**
  - Tables: permissions, model_has_permissions, model_has_roles, role_has_permissions
  - Type: Spatie Laravel Permission package

### Medical Records
- **2025_07_11_092652_create_pasien_table.php**
  - Table: pasien
  - Dependencies: None
  
- **2025_07_11_092652_create_jenis_tindakan_table.php**
  - Table: jenis_tindakan
  - Dependencies: None
  
- **2025_07_11_092652_create_tindakan_table.php**
  - Table: tindakan
  - Dependencies: pasien, jenis_tindakan, users, shifts

### Financial Management
- **2025_07_11_092700_create_pendapatan_table.php**
  - Table: pendapatan
  - Dependencies: tindakan, users
  
- **2025_07_11_092700_create_pengeluaran_table.php**
  - Table: pengeluaran
  - Dependencies: users
  
- **2025_07_11_155338_create_pendapatan_harians_table.php**
  - Table: pendapatan_harians
  - Dependencies: pendapatan
  
- **2025_07_12_023721_create_pengeluaran_harians_table.php**
  - Table: pengeluaran_harians
  - Dependencies: pengeluaran

### Staff Management
- **2025_07_11_230305_create_pegawais_table.php**
  - Table: pegawais
  - Dependencies: None
  
- **2025_07_12_072713_create_dokters_table.php**
  - Table: dokters
  - Dependencies: users
  
- **2025_07_11_092652_create_shifts_table.php**
  - Table: shifts
  - Dependencies: None

### Attendance System
- **2025_07_11_163901_create_attendances_table.php**
  - Table: attendances
  - Dependencies: users
  
- **2025_07_14_230032_create_non_paramedis_attendances_table.php**
  - Table: non_paramedis_attendances
  - Dependencies: pegawais, work_locations

### Work Location & GPS
- **2025_07_11_171316_create_work_locations_table.php**
  - Table: work_locations
  - Dependencies: None
  
- **2025_07_11_225513_create_gps_spoofing_detections_table.php**
  - Table: gps_spoofing_detections
  - Dependencies: users
  
- **2025_07_12_001635_create_location_validations_table.php**
  - Table: location_validations (validasi_lokasi)
  - Dependencies: attendances

### Leave Management
- **2025_07_12_063016_create_cuti_pegawais_table.php**
  - Table: cuti_pegawais
  - Dependencies: pegawais
  
- **2025_07_13_002306_create_leave_types_table.php**
  - Table: leave_types
  - Dependencies: None
  
- **2025_07_12_105900_create_permohonan_cutis_table.php**
  - Table: permohonan_cutis
  - Dependencies: users

### System Features
- **2025_07_15_094706_create_system_settings_table.php**
  - Table: system_settings
  - Dependencies: None
  
- **2025_07_15_094706_create_feature_flags_table.php**
  - Table: feature_flags
  - Dependencies: None

## 2. Column Addition/Modification Migrations

### Users Table Modifications (MERGE CANDIDATES)
1. **2025_07_11_092700_add_role_id_to_users_table.php**
   - Adds: role_id, nip, no_telepon, tanggal_bergabung, is_active, soft deletes
   - Indexes: role_id, nip, is_active

2. **2025_07_12_225550_add_username_to_users_table.php**
   - Adds: username (unique)

3. **2025_07_15_070054_add_profile_settings_to_users_table.php**
   - Adds: phone, address, bio, date_of_birth, gender, emergency contacts, profile photo
   - Adds: work settings, notification settings, privacy settings, app settings
   - Foreign key: default_work_location_id

4. **2025_07_15_095251_make_role_id_nullable_in_users_table.php**
   - Modifies: role_id to nullable

5. **2025_07_15_231720_add_pegawai_id_to_users_table.php**
   - Adds: pegawai_id with foreign key

### Tindakan Table Modifications (MERGE CANDIDATES)
1. **2025_07_11_123000_add_input_by_to_tindakan_table.php**
   - Adds: input_by foreign key

2. **2025_07_13_100339_add_validation_fields_to_tindakan_table.php**
   - Adds: status_validasi, validated_by, validated_at, komentar_validasi
   - Index: status_validasi

3. **2025_07_13_100412_fix_foreign_keys_in_tindakan_table.php**
   - Fixes foreign key constraints

4. **2025_07_13_100434_make_dokter_id_nullable_in_tindakan_table.php**
   - Modifies: dokter_id to nullable

### Pendapatan Table Modifications (MERGE CANDIDATES)
1. **2025_07_11_125444_add_new_fields_to_pendapatan_table.php**
   - Adds: kode_pendapatan, nama_pendapatan, sumber_pendapatan

2. **2025_07_11_125722_update_pendapatan_table_nullable_fields.php**
   - Updates nullable constraints

3. **2025_07_11_160519_add_is_aktif_to_pendapatan_table.php**
   - Adds: is_aktif field

### Pegawais Table Modifications (MERGE CANDIDATES)
1. **2025_07_13_000205_add_user_id_to_pegawais_table.php**
   - Adds: user_id foreign key

2. **2025_07_13_075245_add_login_fields_to_pegawais_table.php**
   - Adds: login-related fields

### Attendances Table Modifications (MERGE CANDIDATES)
1. **2025_07_11_165455_add_device_fields_to_attendances_table.php**
   - Adds: device-related fields

2. **2025_07_14_010934_add_gps_fields_to_attendances_table.php**
   - Adds: GPS location fields

## 3. Index/Constraint Migrations

- **2025_07_15_165850_add_database_indexes_for_performance.php**
  - Comprehensive index addition for all major tables
  - Includes composite indexes and full-text search indexes
  - Tables affected: pasien, tindakan, pendapatan, pengeluaran, dokters, users, jenis_tindakan, jaspel, audit_logs

## 4. Merge Recommendations

### High Priority Merges

#### 1. Users Table Migrations (5 files → 1 file)
Merge these migrations into a single comprehensive users table modification:
- 2025_07_11_092700_add_role_id_to_users_table.php
- 2025_07_12_225550_add_username_to_users_table.php
- 2025_07_15_070054_add_profile_settings_to_users_table.php
- 2025_07_15_095251_make_role_id_nullable_in_users_table.php
- 2025_07_15_231720_add_pegawai_id_to_users_table.php

**New file**: `2025_07_11_092700_add_comprehensive_fields_to_users_table.php`

#### 2. Tindakan Table Migrations (4 files → 1 file)
Merge these migrations:
- 2025_07_11_123000_add_input_by_to_tindakan_table.php
- 2025_07_13_100339_add_validation_fields_to_tindakan_table.php
- 2025_07_13_100412_fix_foreign_keys_in_tindakan_table.php
- 2025_07_13_100434_make_dokter_id_nullable_in_tindakan_table.php

**New file**: `2025_07_11_123000_add_validation_and_tracking_to_tindakan_table.php`

#### 3. Pendapatan Table Migrations (3 files → 1 file)
Merge these migrations:
- 2025_07_11_125444_add_new_fields_to_pendapatan_table.php
- 2025_07_11_125722_update_pendapatan_table_nullable_fields.php
- 2025_07_11_160519_add_is_aktif_to_pendapatan_table.php

**New file**: `2025_07_11_125444_add_comprehensive_fields_to_pendapatan_table.php`

#### 4. Pegawais Table Migrations (2 files → 1 file)
Merge these migrations:
- 2025_07_13_000205_add_user_id_to_pegawais_table.php
- 2025_07_13_075245_add_login_fields_to_pegawais_table.php

**New file**: `2025_07_13_000205_add_user_and_login_fields_to_pegawais_table.php`

#### 5. Attendances Table Migrations (2 files → 1 file)
Merge these migrations:
- 2025_07_11_165455_add_device_fields_to_attendances_table.php
- 2025_07_14_010934_add_gps_fields_to_attendances_table.php

**New file**: `2025_07_11_165455_add_device_and_gps_fields_to_attendances_table.php`

### Medium Priority Merges

#### 6. GPS/Location Related Tables
Consider consolidating GPS-related tables:
- gps_spoofing_detections
- gps_spoofing_settings
- gps_spoofing_configs
- location_validations

These could potentially be merged into a unified location security system.

#### 7. Financial Validation Tables
Consider merging:
- pendapatan_harians
- pengeluaran_harians
- validasi_jumlah_pasiens (jumlah_pasien_harians)

Into a unified daily validation system.

## 5. Dependencies Graph

```
users
├── roles (foreign key: role_id)
├── pegawais (foreign key: pegawai_id)
├── work_locations (foreign key: default_work_location_id)
└── Used by: tindakan, pendapatan, pengeluaran, dokters, attendances, etc.

pasien
└── Used by: tindakan

jenis_tindakan
└── Used by: tindakan

tindakan
├── pasien (foreign key)
├── jenis_tindakan (foreign key)
├── users (foreign keys: dokter_id, paramedis_id, non_paramedis_id, input_by, validated_by)
├── shifts (foreign key)
└── Used by: pendapatan, jaspel

pendapatan
├── tindakan (foreign key)
├── users (foreign keys: input_by, validasi_by)
└── Used by: pendapatan_harians

pengeluaran
├── users (foreign keys: input_by, validasi_by)
└── Used by: pengeluaran_harians
```

## 6. Potential Issues

1. **Circular Dependencies**: Some tables have potential circular dependencies through user relationships
2. **Missing Tables**: Several indexes reference tables that don't exist (error_logs, security_logs, performance_logs)
3. **Naming Inconsistencies**: Mix of English and Indonesian table/column names
4. **Duplicate Functionality**: Multiple validation and tracking systems that could be unified

## 7. Recommendations

1. **Execute merge operations** to reduce migration count from 94 to approximately 75-80
2. **Standardize naming conventions** - choose either English or Indonesian consistently
3. **Create missing tables** referenced in the index migration
4. **Consider database normalization** for repeated validation patterns
5. **Add database documentation** for complex relationships
6. **Implement migration versioning** for better tracking

## 8. Migration Execution Order

When running fresh migrations, ensure this order:
1. Laravel core tables (users, cache, jobs)
2. Independent tables (roles, pasien, jenis_tindakan, etc.)
3. Dependent tables (tindakan, pendapatan, etc.)
4. Modification migrations
5. Index migrations (should be last)