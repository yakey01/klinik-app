# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**Dokterku** is a healthcare clinic management system built with Laravel 11 and Filament 3. The system manages medical procedures, financial transactions, staff schedules, and includes a comprehensive Jaspel (medical service fee) calculation system with multi-role access panels.

## Development Commands

### Primary Development Environment
```bash
# Start complete development environment
composer dev

# Individual services
php artisan serve           # Laravel server (port 8000)
npm run dev                # Vite dev server
php artisan queue:work     # Queue processing
php artisan pail           # Log monitoring
```

### Frontend Build Process
```bash
# Development
npm run dev                # Vite dev server
npm run react-dev          # React dev server for mobile dashboards

# Production
npm run build              # Standard build
npm run react-build        # React build for mobile apps
```

### Testing
```bash
# Run all tests (uses Pest PHP, not PHPUnit)
composer test              # Clears config + runs tests
php artisan test          # Direct test execution

# Database: Uses SQLite in-memory for testing
# Test Structure: Feature/ (integration), Unit/ (services & models)
```

### Database Operations
```bash
php artisan migrate --seed
php artisan migrate:fresh --seed  # Reset with seed data
```

## Architecture Overview

### Multi-Panel Filament Structure
The system uses 7 separate Filament panels with role-based access:
- **Admin Panel** (`/admin`) - System administration
- **Paramedis Panel** (`/paramedis`) - Medical staff
- **Dokter Panel** (`/dokter`) - Doctors  
- **Petugas Panel** (`/petugas`) - General staff
- **Bendahara Panel** (`/bendahara`) - Financial management
- **Manajer Panel** (`/manajer`) - Management
- **Verifikator Panel** (`/verifikator`) - Data verification

Each panel has its own PanelProvider in `app/Providers/Filament/` with specific middleware and resources.

### Key Service Layer Components
Rich service architecture with 30+ services in `app/Services/`:
- **JaspelCalculationService** - Medical fee calculations with complex business logic
- **AttendanceValidationService** - GPS-based attendance with anti-spoofing
- **SmartWorkLocationAssignmentService** - AI-powered location assignment
- **TelegramService** - System notifications
- **SecurityService** - Authentication & authorization
- **GpsSpoofingDetectionService** - Location security validation

### Database Schema Patterns
- **100+ migrations** with proper dependency ordering
- **Performance indexes** (see 2025_07_15_165850_add_database_indexes_for_performance.php)
- **Audit logging** for all major operations via AuditLog model
- **Soft deletes** implemented across critical models

### API Architecture
- **Dual versioning**: `/api/v1/*` (mobile), `/api/v2/*` (dashboards)
- **Sanctum authentication** with device binding
- **Role-specific endpoints** with middleware protection
- **React mobile dashboards** with offline sync capabilities

## Key Models & Relationships

### Core Medical Models
```php
User -> hasOne(Pegawai) -> belongsTo(Role)
Pasien -> hasMany(Tindakan)
Tindakan -> belongsTo(JenisTindakan, Dokter, Paramedis)
Jaspel -> belongsTo(User, Tindakan)  // Medical service fees
```

### Attendance & Scheduling
```php
JadwalJaga -> belongsTo(User, ShiftTemplate)
Attendance -> belongsTo(User, WorkLocation)
PermohonanCuti -> belongsTo(User, LeaveType)
```

### Financial Management
```php
Pendapatan -> belongsTo(User) // Revenue with validation workflow
Pengeluaran -> belongsTo(User) // Expenses with approval
PendapatanHarian/PengeluaranHarian // Daily aggregations
```

## Custom Artisan Commands

Notable system management commands:
```bash
php artisan admin:create          # Create admin users
php artisan jaspel:calculate      # Calculate medical service fees
php artisan attendance:sync       # Sync attendance data
php artisan telegram:setup        # Configure notifications
php artisan location:migrate      # Migrate work locations
```

## Development Patterns

### Filament Resources
- **Resource classes** in `app/Filament/Resources/` organized by panel
- **Form schemas** with complex validation and live interactions
- **Table actions** with bulk operations and custom filters
- **Smart assignment systems** using AI-like algorithms

### Policy-Based Authorization
Comprehensive policy system in `app/Policies/`:
- JaspelPolicy, PasienPolicy, TindakanPolicy
- Role-based access with Spatie Permission integration
- Device-specific permissions for mobile access

### Event-Driven Architecture
```php
// Events in app/Events/
PatientCreated, JaspelSelesai, WorkLocationUpdated

// Listeners in app/Listeners/
SendTelegramNotification, HitungJaspelJob
```

### Repository Pattern
Complex data operations abstracted through repositories:
- JaspelRepository, PasienRepository, TindakanRepository
- Used for business logic separation from Eloquent models

## Security Features

### Authentication & Location Security
- **Multi-guard auth** (web + sanctum)
- **GPS validation** for attendance with anti-spoofing detection
- **Device binding** for mobile apps
- **Face recognition** integration for identity verification

### Data Protection
- **Form Requests** for input validation
- **CSRF protection** with extended token lifecycle
- **Audit logging** via AuditLog model for sensitive operations
- **Role-based access control** with granular permissions

## Environment Configuration

### Development Setup
```bash
cp .env.example .env
php artisan key:generate
composer install && npm install
php artisan migrate --seed
composer dev
```

### Database Configuration
- **MySQL** for production/staging
- **SQLite** for development/testing
- **Redis** for caching and queues (optional)

### Mobile App Integration
- **React dashboards** in `/resources/js/react/`
- **Mobile-specific API endpoints** with offline capabilities
- **GPS-based features** requiring location permissions

## Testing Strategy

### Test Organization
- **Feature tests** for API endpoints and user flows
- **Unit tests** for services and business logic
- **Database testing** with SQLite in-memory
- **Pest PHP** framework instead of PHPUnit

### Key Testing Areas
- Jaspel calculation accuracy
- GPS spoofing prevention
- Role-based access control
- Financial transaction validation
- Attendance system integrity

## Performance Considerations

### Database Optimization
- **Indexed queries** for heavy operations
- **Lazy loading** for relationships
- **Query optimization** in services layer

### Caching Strategy
- **Service layer caching** for expensive calculations
- **Filament resource caching** for dropdown options
- **API response caching** for mobile apps

### Queue Processing
- **Heavy operations** (Jaspel calculations, reports) queued
- **Telegram notifications** processed asynchronously
- **File uploads** and image processing queued