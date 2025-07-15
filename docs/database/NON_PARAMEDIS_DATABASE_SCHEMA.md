# NonParamedis Database Schema Documentation

## Overview

This document provides comprehensive documentation for the NonParamedis (Non-Medical Administrative Staff) database schema, including table structures, relationships, indexes, and data patterns used in the Dokterku Medical Clinic Management System.

## Table of Contents
1. [Core Tables](#core-tables)
2. [Relationships](#relationships)
3. [Indexes and Performance](#indexes-and-performance)
4. [Data Types and Constraints](#data-types-and-constraints)
5. [Sample Data Patterns](#sample-data-patterns)
6. [Migration Scripts](#migration-scripts)

## Core Tables

### 1. non_paramedis_attendances

Primary table for storing attendance records of non-medical administrative staff.

#### Table Structure
```sql
CREATE TABLE non_paramedis_attendances (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    
    -- User and Location References
    user_id BIGINT UNSIGNED NOT NULL,
    work_location_id BIGINT UNSIGNED NULL,
    
    -- Check-in Data
    check_in_time TIMESTAMP NULL,
    check_in_latitude DECIMAL(10,8) NULL,
    check_in_longitude DECIMAL(11,8) NULL,
    check_in_accuracy DECIMAL(8,2) NULL,
    check_in_address VARCHAR(255) NULL,
    check_in_distance DECIMAL(8,2) NULL,
    check_in_valid_location BOOLEAN DEFAULT FALSE,
    
    -- Check-out Data
    check_out_time TIMESTAMP NULL,
    check_out_latitude DECIMAL(10,8) NULL,
    check_out_longitude DECIMAL(11,8) NULL,
    check_out_accuracy DECIMAL(8,2) NULL,
    check_out_address VARCHAR(255) NULL,
    check_out_distance DECIMAL(8,2) NULL,
    check_out_valid_location BOOLEAN DEFAULT FALSE,
    
    -- Work Duration and Status
    total_work_minutes INTEGER NULL,
    attendance_date DATE NOT NULL,
    status ENUM('checked_in', 'checked_out', 'incomplete') DEFAULT 'incomplete',
    notes TEXT NULL,
    
    -- Device and Security Information
    device_info JSON NULL,
    browser_info VARCHAR(255) NULL,
    ip_address VARCHAR(45) NULL,
    gps_metadata JSON NULL,
    suspected_spoofing BOOLEAN DEFAULT FALSE,
    
    -- Approval Workflow
    approval_status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    approved_by BIGINT UNSIGNED NULL,
    approved_at TIMESTAMP NULL,
    approval_notes TEXT NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Foreign Key Constraints
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (work_location_id) REFERENCES work_locations(id) ON DELETE SET NULL,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
    
    -- Indexes
    INDEX idx_user_date (user_id, attendance_date),
    INDEX idx_location_date (work_location_id, attendance_date),
    INDEX idx_status_date (status, attendance_date),
    INDEX idx_approval_status (approval_status)
);
```

#### Field Descriptions

| Field | Type | Description | Constraints |
|-------|------|-------------|-------------|
| `id` | BIGINT UNSIGNED | Primary key, auto-increment | NOT NULL, AUTO_INCREMENT |
| `user_id` | BIGINT UNSIGNED | Reference to users table | NOT NULL, FOREIGN KEY |
| `work_location_id` | BIGINT UNSIGNED | Reference to work_locations table | NULLABLE, FOREIGN KEY |
| `check_in_time` | TIMESTAMP | Time when user checked in | NULLABLE |
| `check_in_latitude` | DECIMAL(10,8) | GPS latitude for check-in | NULLABLE, ±90 degrees |
| `check_in_longitude` | DECIMAL(11,8) | GPS longitude for check-in | NULLABLE, ±180 degrees |
| `check_in_accuracy` | DECIMAL(8,2) | GPS accuracy in meters | NULLABLE, ≥0 |
| `check_in_address` | VARCHAR(255) | Reverse geocoded address | NULLABLE |
| `check_in_distance` | DECIMAL(8,2) | Distance from work location in meters | NULLABLE, ≥0 |
| `check_in_valid_location` | BOOLEAN | Whether check-in location is valid | DEFAULT FALSE |
| `check_out_time` | TIMESTAMP | Time when user checked out | NULLABLE |
| `check_out_latitude` | DECIMAL(10,8) | GPS latitude for check-out | NULLABLE, ±90 degrees |
| `check_out_longitude` | DECIMAL(11,8) | GPS longitude for check-out | NULLABLE, ±180 degrees |
| `check_out_accuracy` | DECIMAL(8,2) | GPS accuracy in meters | NULLABLE, ≥0 |
| `check_out_address` | VARCHAR(255) | Reverse geocoded address | NULLABLE |
| `check_out_distance` | DECIMAL(8,2) | Distance from work location in meters | NULLABLE, ≥0 |
| `check_out_valid_location` | BOOLEAN | Whether check-out location is valid | DEFAULT FALSE |
| `total_work_minutes` | INTEGER | Total work duration in minutes | NULLABLE, ≥0 |
| `attendance_date` | DATE | Date of attendance | NOT NULL |
| `status` | ENUM | Current attendance status | 'checked_in', 'checked_out', 'incomplete' |
| `notes` | TEXT | Additional notes or comments | NULLABLE |
| `device_info` | JSON | Device information (user agent, platform) | NULLABLE |
| `browser_info` | VARCHAR(255) | Browser information | NULLABLE |
| `ip_address` | VARCHAR(45) | IP address of request | NULLABLE |
| `gps_metadata` | JSON | GPS metadata and validation info | NULLABLE |
| `suspected_spoofing` | BOOLEAN | GPS spoofing detection flag | DEFAULT FALSE |
| `approval_status` | ENUM | Approval workflow status | 'pending', 'approved', 'rejected' |
| `approved_by` | BIGINT UNSIGNED | User ID who approved/rejected | NULLABLE, FOREIGN KEY |
| `approved_at` | TIMESTAMP | Timestamp of approval/rejection | NULLABLE |
| `approval_notes` | TEXT | Notes from approver | NULLABLE |

#### JSON Field Structures

##### device_info JSON Structure
```json
{
  "user_agent": "Mozilla/5.0 (iPhone; CPU iPhone OS 15_0 like Mac OS X)",
  "ip_address": "192.168.1.100",
  "platform": "mobile_web",
  "screen_resolution": "375x667",
  "timezone": "Asia/Jakarta"
}
```

##### gps_metadata JSON Structure
```json
{
  "accuracy": 10.5,
  "provider": "gps",
  "timestamp": "2025-07-15T08:15:30.000Z",
  "speed": 0,
  "bearing": 45,
  "altitude": 100.5,
  "validation_result": {
    "is_valid": true,
    "distance": 45.2,
    "max_distance": 100,
    "quality": "good"
  }
}
```

### 2. Related Tables (Reference)

#### users Table (Partial)
```sql
-- Users table structure (relevant fields)
CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    username VARCHAR(255) UNIQUE NULL,
    password VARCHAR(255) NOT NULL,
    nip VARCHAR(50) NULL,
    no_telepon VARCHAR(20) NULL,
    tanggal_bergabung DATE NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

#### work_locations Table (Partial)
```sql
-- Work locations table structure (relevant fields)
CREATE TABLE work_locations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    address TEXT NOT NULL,
    latitude DECIMAL(10,8) NOT NULL,
    longitude DECIMAL(11,8) NOT NULL,
    radius_meters DECIMAL(8,2) DEFAULT 100.00,
    location_type ENUM('main_office', 'branch', 'mobile', 'other') DEFAULT 'main_office',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

#### roles Table (Partial)
```sql
-- Roles table structure (relevant fields)
CREATE TABLE roles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) UNIQUE NOT NULL,
    display_name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

## Relationships

### Entity Relationship Diagram

```
┌─────────────────┐       ┌──────────────────────────┐       ┌─────────────────┐
│     roles       │       │                          │       │ work_locations  │
├─────────────────┤   ┌───┤ non_paramedis_attendances├───┐   ├─────────────────┤
│ id (PK)         │   │   │                          │   │   │ id (PK)         │
│ name            │   │   ├──────────────────────────┤   │   │ name            │
│ display_name    │   │   │ id (PK)                  │   │   │ address         │
│ description     │   │   │ user_id (FK)             │   │   │ latitude        │
└─────────────────┘   │   │ work_location_id (FK)    │   │   │ longitude       │
                      │   │ check_in_time           │   │   │ radius_meters   │
┌─────────────────┐   │   │ check_in_latitude       │   │   │ location_type   │
│     users       │   │   │ check_in_longitude      │   │   │ is_active       │
├─────────────────┤   │   │ check_in_accuracy       │   │   └─────────────────┘
│ id (PK)         ├───┘   │ check_in_distance       │   │            │
│ role_id (FK)    │       │ check_in_valid_location │   └────────────┘
│ name            │   ┌───┤ check_out_time          │
│ email           │   │   │ check_out_latitude      │
│ username        │   │   │ check_out_longitude     │
│ nip             │   │   │ check_out_accuracy      │
│ no_telepon      │   │   │ check_out_distance      │
│ is_active       │   │   │ check_out_valid_location│
└─────────────────┘   │   │ total_work_minutes      │
                      │   │ attendance_date         │
                      │   │ status                  │
                      │   │ approval_status         │
                      └───┤ approved_by (FK)        │
                          │ approved_at             │
                          │ device_info (JSON)      │
                          │ gps_metadata (JSON)     │
                          └──────────────────────────┘
```

### Relationship Details

1. **User to Attendance (1:N)**
   - One user can have multiple attendance records
   - Relationship: `users.id` → `non_paramedis_attendances.user_id`
   - Constraint: CASCADE DELETE (if user deleted, all attendance records deleted)

2. **Work Location to Attendance (1:N)**
   - One work location can have multiple attendance records
   - Relationship: `work_locations.id` → `non_paramedis_attendances.work_location_id`
   - Constraint: SET NULL (if work location deleted, attendance records remain with null location)

3. **User to Approval (1:N)**
   - One user (supervisor/admin) can approve multiple attendance records
   - Relationship: `users.id` → `non_paramedis_attendances.approved_by`
   - Constraint: SET NULL (if approver deleted, approval record remains)

4. **Role to User (1:N)**
   - One role can be assigned to multiple users
   - Relationship: `roles.id` → `users.role_id`
   - Constraint: Typically RESTRICT (cannot delete role if users exist)

## Indexes and Performance

### Primary Indexes

1. **Primary Key Index**: `id` (AUTO_INCREMENT)
2. **User-Date Composite Index**: `(user_id, attendance_date)`
   - Optimizes queries for user's attendance history
   - Supports date range queries efficiently

3. **Location-Date Composite Index**: `(work_location_id, attendance_date)`
   - Optimizes location-based attendance reports
   - Supports analytics by location

4. **Status-Date Composite Index**: `(status, attendance_date)`
   - Optimizes status-based filtering
   - Supports dashboard queries

5. **Approval Status Index**: `approval_status`
   - Optimizes approval workflow queries
   - Supports admin review interfaces

### Query Performance Considerations

#### Optimized Query Patterns

1. **User Daily Attendance**
```sql
-- Optimized with idx_user_date
SELECT * FROM non_paramedis_attendances 
WHERE user_id = 123 AND attendance_date = '2025-07-15';
```

2. **User Monthly Statistics**
```sql
-- Optimized with idx_user_date
SELECT COUNT(*), SUM(total_work_minutes) 
FROM non_paramedis_attendances 
WHERE user_id = 123 
  AND attendance_date BETWEEN '2025-07-01' AND '2025-07-31';
```

3. **Pending Approvals**
```sql
-- Optimized with idx_approval_status
SELECT * FROM non_paramedis_attendances 
WHERE approval_status = 'pending' 
ORDER BY created_at DESC;
```

4. **Location Analytics**
```sql
-- Optimized with idx_location_date
SELECT work_location_id, COUNT(*), AVG(total_work_minutes)
FROM non_paramedis_attendances 
WHERE attendance_date BETWEEN '2025-07-01' AND '2025-07-31'
GROUP BY work_location_id;
```

### Performance Tips

1. **Use Composite Indexes**: Always include date filters when querying by user or location
2. **Limit Result Sets**: Use LIMIT when displaying paginated results
3. **Avoid SELECT ***: Only select needed columns, especially avoiding JSON fields when not needed
4. **Use Covering Indexes**: For frequently used queries, consider covering indexes

## Data Types and Constraints

### Precision Considerations

#### GPS Coordinates
- **Latitude**: `DECIMAL(10,8)` provides ~1.1cm precision at equator
- **Longitude**: `DECIMAL(11,8)` provides ~1.1cm precision at equator
- **Accuracy**: `DECIMAL(8,2)` supports up to 999,999.99 meters with 2 decimal places

#### Distance Measurements
- **Distance**: `DECIMAL(8,2)` supports up to 999,999.99 meters (999.99km)
- Sufficient for clinic geofencing (typically < 1000m radius)

#### Time Storage
- **TIMESTAMP**: Automatically handles timezone conversion
- **DATE**: For attendance_date, no time component needed
- **INTEGER**: For total_work_minutes, max ~45 days in minutes

### Validation Rules

#### Application-Level Constraints
```php
// GPS coordinate validation
$latitude >= -90 && $latitude <= 90
$longitude >= -180 && $longitude <= 180
$accuracy >= 0

// Distance validation
$distance >= 0

// Work minutes validation
$total_work_minutes >= 0 && $total_work_minutes <= 1440 // Max 24 hours

// Status transitions
$validTransitions = [
    'incomplete' => ['checked_in'],
    'checked_in' => ['checked_out'],
    'checked_out' => [] // Final state
];
```

## Sample Data Patterns

### 1. Normal Work Day Pattern
```sql
INSERT INTO non_paramedis_attendances (
    user_id,
    work_location_id,
    attendance_date,
    check_in_time,
    check_in_latitude,
    check_in_longitude,
    check_in_accuracy,
    check_in_distance,
    check_in_valid_location,
    check_out_time,
    check_out_latitude,
    check_out_longitude,
    check_out_accuracy,
    check_out_distance,
    check_out_valid_location,
    total_work_minutes,
    status,
    approval_status,
    device_info,
    gps_metadata
) VALUES (
    1, -- user_id
    1, -- work_location_id
    '2025-07-15',
    '2025-07-15 08:15:00',
    -6.20880000,
    106.84560000,
    10.50,
    45.20,
    TRUE,
    '2025-07-15 17:30:00',
    -6.20885000,
    106.84565000,
    8.00,
    35.80,
    TRUE,
    555, -- 9 hours 15 minutes
    'checked_out',
    'approved',
    '{"user_agent": "Mozilla/5.0 (iPhone; CPU iPhone OS 15_0)", "platform": "mobile_web"}',
    '{"accuracy": 10.5, "provider": "gps", "validation_result": {"is_valid": true, "distance": 45.2}}'
);
```

### 2. Late Arrival Pattern
```sql
INSERT INTO non_paramedis_attendances (
    user_id,
    work_location_id,
    attendance_date,
    check_in_time,
    check_in_latitude,
    check_in_longitude,
    check_in_accuracy,
    check_in_distance,
    check_in_valid_location,
    check_out_time,
    check_out_latitude,
    check_out_longitude,
    check_out_accuracy,
    check_out_distance,
    check_out_valid_location,
    total_work_minutes,
    status,
    approval_status,
    notes
) VALUES (
    2, -- user_id
    1, -- work_location_id
    '2025-07-15',
    '2025-07-15 08:25:00', -- 25 minutes late
    -6.20880000,
    106.84560000,
    12.00,
    38.50,
    TRUE,
    '2025-07-15 17:45:00', -- Staying late to compensate
    -6.20885000,
    106.84565000,
    9.50,
    42.10,
    TRUE,
    560, -- 9 hours 20 minutes
    'checked_out',
    'pending',
    'Terlambat karena kemacetan, kompensasi dengan pulang lebih lama'
);
```

### 3. Incomplete Day Pattern (Only Check-in)
```sql
INSERT INTO non_paramedis_attendances (
    user_id,
    work_location_id,
    attendance_date,
    check_in_time,
    check_in_latitude,
    check_in_longitude,
    check_in_accuracy,
    check_in_distance,
    check_in_valid_location,
    status,
    approval_status
) VALUES (
    3, -- user_id
    1, -- work_location_id
    '2025-07-15',
    '2025-07-15 08:10:00',
    -6.20880000,
    106.84560000,
    15.20,
    52.30,
    TRUE,
    'checked_in',
    'pending'
);
```

## Migration Scripts

### Create Table Migration
```php
<?php
// database/migrations/2025_07_14_230032_create_non_paramedis_attendances_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('non_paramedis_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('work_location_id')->nullable()->constrained()->nullOnDelete();
            
            // Check-in data
            $table->timestamp('check_in_time')->nullable();
            $table->decimal('check_in_latitude', 10, 8)->nullable();
            $table->decimal('check_in_longitude', 11, 8)->nullable();
            $table->decimal('check_in_accuracy', 8, 2)->nullable();
            $table->string('check_in_address')->nullable();
            $table->decimal('check_in_distance', 8, 2)->nullable();
            $table->boolean('check_in_valid_location')->default(false);
            
            // Check-out data
            $table->timestamp('check_out_time')->nullable();
            $table->decimal('check_out_latitude', 10, 8)->nullable();
            $table->decimal('check_out_longitude', 11, 8)->nullable();
            $table->decimal('check_out_accuracy', 8, 2)->nullable();
            $table->string('check_out_address')->nullable();
            $table->decimal('check_out_distance', 8, 2)->nullable();
            $table->boolean('check_out_valid_location')->default(false);
            
            // Work duration
            $table->integer('total_work_minutes')->nullable();
            $table->date('attendance_date');
            
            // Status and validation
            $table->enum('status', ['checked_in', 'checked_out', 'incomplete'])->default('incomplete');
            $table->text('notes')->nullable();
            
            // Device information
            $table->json('device_info')->nullable();
            $table->string('browser_info')->nullable();
            $table->string('ip_address')->nullable();
            
            // GPS spoofing detection
            $table->json('gps_metadata')->nullable();
            $table->boolean('suspected_spoofing')->default(false);
            
            // Approval workflow
            $table->enum('approval_status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_notes')->nullable();
            
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['user_id', 'attendance_date']);
            $table->index(['work_location_id', 'attendance_date']);
            $table->index(['status', 'attendance_date']);
            $table->index('approval_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('non_paramedis_attendances');
    }
};
```

### Add Indexes Migration (Optional Performance Enhancement)
```php
<?php
// database/migrations/2025_07_15_120000_add_performance_indexes_to_non_paramedis_attendances.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('non_paramedis_attendances', function (Blueprint $table) {
            // Composite index for date range queries
            $table->index(['attendance_date', 'status', 'approval_status'], 'idx_date_status_approval');
            
            // Index for GPS validation queries
            $table->index(['check_in_valid_location', 'check_out_valid_location'], 'idx_location_valid');
            
            // Index for work duration analytics
            $table->index(['total_work_minutes', 'attendance_date'], 'idx_work_duration_date');
        });
    }

    public function down(): void
    {
        Schema::table('non_paramedis_attendances', function (Blueprint $table) {
            $table->dropIndex('idx_date_status_approval');
            $table->dropIndex('idx_location_valid');
            $table->dropIndex('idx_work_duration_date');
        });
    }
};
```

## Data Integrity and Maintenance

### Automated Cleanup Scripts

#### Remove Old Records (Retention Policy)
```sql
-- Delete records older than 2 years (adjust as needed)
DELETE FROM non_paramedis_attendances 
WHERE attendance_date < DATE_SUB(CURDATE(), INTERVAL 2 YEAR);
```

#### Archive Old Approved Records
```sql
-- Create archive table
CREATE TABLE non_paramedis_attendances_archive LIKE non_paramedis_attendances;

-- Move old approved records to archive
INSERT INTO non_paramedis_attendances_archive 
SELECT * FROM non_paramedis_attendances 
WHERE approval_status = 'approved' 
  AND attendance_date < DATE_SUB(CURDATE(), INTERVAL 1 YEAR);

-- Delete archived records from main table
DELETE FROM non_paramedis_attendances 
WHERE approval_status = 'approved' 
  AND attendance_date < DATE_SUB(CURDATE(), INTERVAL 1 YEAR);
```

### Data Validation Queries

#### Check for Inconsistent Data
```sql
-- Check for check-out without check-in
SELECT id, user_id, attendance_date 
FROM non_paramedis_attendances 
WHERE check_out_time IS NOT NULL 
  AND check_in_time IS NULL;

-- Check for negative work duration
SELECT id, user_id, attendance_date, total_work_minutes
FROM non_paramedis_attendances 
WHERE total_work_minutes < 0;

-- Check for implausible work duration (>16 hours)
SELECT id, user_id, attendance_date, total_work_minutes
FROM non_paramedis_attendances 
WHERE total_work_minutes > 960; -- 16 hours

-- Check for duplicate attendance on same date
SELECT user_id, attendance_date, COUNT(*) 
FROM non_paramedis_attendances 
GROUP BY user_id, attendance_date 
HAVING COUNT(*) > 1;
```

---

*Generated on 2025-07-15 | Database Schema Version: 1.0 | Dokterku Medical Clinic Management System*