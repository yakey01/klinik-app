# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a Laravel 11 application called "Dokterku" - a clinic financial management system using:
- **PHP 8.2+** with Laravel 11 framework
- **Pest** for testing (instead of PHPUnit)
- **Vite** for frontend asset compilation
- **Tailwind CSS 4.0** for styling
- **SQLite** database for local development

## Development Commands

### Starting Development Environment
```bash
# Start all development services (server, queue, logs, vite)
composer dev
```
This command runs:
- PHP development server (`php artisan serve`)
- Queue worker (`php artisan queue:listen --tries=1`) 
- Log monitoring (`php artisan pail --timeout=0`)
- Vite development server (`npm run dev`)

### Individual Commands
```bash
# Start Laravel development server
php artisan serve

# Start Vite development server
npm run dev

# Build frontend assets for production
npm run build

# Run queue worker
php artisan queue:work

# Monitor logs
php artisan pail
```

### Testing
```bash
# Run all tests using Pest
composer test
# OR
php artisan test

# Run specific test file
php artisan test tests/Feature/ExampleTest.php

# Run specific test by name
php artisan test --filter="example test name"
```

### Code Quality
```bash
# Format code using Laravel Pint
./vendor/bin/pint

# Run code analysis
./vendor/bin/pint --test
```

### Database
```bash
# Run migrations
php artisan migrate

# Fresh migration with seeding
php artisan migrate:fresh --seed

# Create new migration
php artisan make:migration create_table_name

# Create new seeder
php artisan make:seeder TableSeeder
```

## Architecture

### Directory Structure
- `app/` - Core application code
  - `Http/Controllers/` - HTTP controllers
  - `Models/` - Eloquent models
  - `Providers/` - Service providers
- `database/` - Database migrations, factories, seeders
- `resources/` - Views, CSS, JS, and other assets
- `routes/` - Route definitions (web.php, api.php, console.php)
- `tests/` - Test files organized by Feature and Unit

### Key Files
- `composer.json` - PHP dependencies and custom scripts
- `package.json` - Node.js dependencies and build scripts
- `vite.config.js` - Vite configuration with Laravel plugin
- `phpunit.xml` - PHPUnit/Pest configuration
- `database/database.sqlite` - SQLite database file

### Clinic Financial System Models

#### Core Models
- **Role** - User roles (admin, manajer, bendahara, petugas, paramedis, dokter, non_paramedis)
- **User** - System users with role-based permissions
- **Shift** - Work shifts (pagi, siang, malam)
- **Pasien** - Patient records with medical record numbers
- **JenisTindakan** - Medical procedure types with tariffs and fee structures
- **Tindakan** - Medical procedures performed on patients
- **Jaspel** - Service fees for medical staff
- **UangDuduk** - Sitting allowance for staff
- **Pendapatan** - Income transactions
- **Pengeluaran** - Expense transactions

#### Key Relationships
- Users belong to roles and can perform multiple types of medical procedures
- Tindakan (procedures) link patients to medical staff and generate service fees
- Jaspel records are automatically created from tindakan based on fee structures
- All financial transactions require validation workflow (pending → approved/rejected)

#### Database Design Features
- **Soft deletes** on critical tables (users, patients, procedures)
- **Comprehensive indexing** for performance on date ranges, user lookups, and status filtering
- **Audit trails** with input_by and validation_by fields
- **Role-based permissions** stored as JSON arrays
- **Financial precision** using decimal(15,2) for all monetary values

### Testing Architecture
- Uses **Pest** testing framework instead of PHPUnit
- Test configuration in `tests/Pest.php`
- Feature tests in `tests/Feature/`
- Unit tests in `tests/Unit/`
- SQLite in-memory database for testing
- **Factories** available for Pasien and Tindakan models

### Frontend Architecture
- **Vite** for asset compilation and hot-reloading
- **Tailwind CSS 4.0** for styling
- Assets in `resources/css/` and `resources/js/`
- Compiled assets served from `public/build/`

## Development Workflow

1. **Setup**: Ensure `.env` file exists (auto-created from `.env.example`)
2. **Dependencies**: Run `composer install` and `npm install`
3. **Database**: SQLite database is auto-created at `database/database.sqlite`
4. **Seeding**: Run `php artisan db:seed` to populate initial data
5. **Development**: Use `composer dev` to start all services
6. **Testing**: Use `composer test` or `php artisan test`

## Default User Accounts

After seeding, these accounts are available:
- **Admin**: admin@dokterku.com / admin123
- **Manajer**: manajer@dokterku.com / manajer123  
- **Bendahara**: bendahara@dokterku.com / bendahara123
- **Petugas**: petugas@dokterku.com / petugas123
- **Dokter**: dokter@dokterku.com / dokter123
- **Paramedis**: perawat@dokterku.com / perawat123
- **Non-Paramedis**: asisten@dokterku.com / asisten123

## Important Notes

- This project uses **Pest** for testing, not PHPUnit directly
- The `composer dev` script provides a full development environment
- Database uses SQLite by default (no separate database server needed)
- Frontend uses Vite with Laravel integration for hot-reloading
- Laravel Pint is configured for code formatting
- **Financial validation workflow** is central to the system design
- **Role-based access control** should be implemented in controllers and middleware

## FilamentPHP Multi-Panel Architecture

This project uses **FilamentPHP v3.3** with multiple panels:

### Admin Panel (`/admin`)
- **Provider**: `app/Providers/Filament/AdminPanelProvider.php`
- **Widgets**: `app/Filament/Widgets/`
- **Resources**: `app/Filament/Resources/`
- **Colors**: Primary Blue (`Color::Blue`)

### Petugas Panel (`/petugas`)
- **Provider**: `app/Providers/Filament/PetugasPanelProvider.php`
- **Widgets**: `app/Filament/Petugas/Widgets/`
- **Resources**: `app/Filament/Petugas/Resources/`
- **Colors**: Primary Blue (`Color::Blue`)

### Paramedis Panel (`/paramedis`)
- **Provider**: `app/Providers/Filament/ParamedisPanelProvider.php`
- **Widgets**: `app/Filament/Paramedis/Widgets/`
- **Resources**: `app/Filament/Paramedis/Resources/`
- **Colors**: Primary Green (`Color::Green`)
- **Features**: Attendance tracking, Jaspel management, Location detection

### Bendahara Panel (`/bendahara`)
- **Provider**: `app/Providers/Filament/BendaharaPanelProvider.php`
- **Widgets**: `app/Filament/Bendahara/Widgets/`
- **Resources**: `app/Filament/Bendahara/Resources/`
- **Features**: Financial validation, Revenue tracking

### Filament Development Best Practices

#### ✅ DO's:
1. **Copy structure from working panels** - Use admin panel as template
2. **Use standard Filament components** - `StatsOverviewWidget`, `ChartWidget`, etc.
3. **Follow exact namespace patterns** - Match admin panel structure
4. **Use standard Heroicons** - `heroicon-m-*` classes for consistent sizing
5. **Clear caches after changes**:
   ```bash
   php artisan config:clear
   php artisan view:clear  
   php artisan filament:clear-cached-components
   ```

#### ❌ DON'Ts:
1. **No custom CSS overrides** - Avoid custom theme files that override Filament styles
2. **No custom view templates** - Use default Filament dashboard views
3. **No transform/scale CSS** - Causes icon sizing issues
4. **No custom asset compilation** - Stick to standard Vite configuration

### Troubleshooting Large Icons/Layout Issues

**Root Cause**: Custom CSS overrides in theme files, especially:
```css
.fi-stats-overview-stat:hover {
    @apply transform scale-105;  /* ← CAUSES LARGE ICONS */
}
```

**Solution Process**:
1. Remove any custom CSS files (e.g., `resources/css/petugas.css`)
2. Update `vite.config.js` to remove custom CSS references
3. Clear compiled assets: `rm -f public/build/assets/petugas-*.css`
4. Rebuild assets: `npm run build`
5. Clear Filament caches
6. Use identical panel provider structure as working admin panel

### Successful Petugas Dashboard Implementation

**Files Created**:
- `app/Filament/Petugas/Widgets/PetugasStatsWidget.php` - Stats overview with daily metrics
- `app/Providers/Filament/PetugasPanelProvider.php` - Panel configuration

**Key Features**:
- ✅ Dynamic greeting based on time of day
- ✅ 4 stats cards: Pasien, Pendapatan, Pengeluaran, Tindakan
- ✅ Dummy data fallback for demonstration
- ✅ Consistent styling with admin panel
- ✅ Dark mode support
- ✅ Auto-refresh every 15 seconds

**Access**: `http://localhost:8000/petugas` (Login: petugas@dokterku.com / petugas123)

## Attendance & Location System

### Filament Plugins Integration
- **diogogpinto/filament-geolocate-me** (v0.1.1) - Geolocation actions for Filament
- **dotswan/filament-map-picker** (v1.8) - Interactive maps with OpenStreetMap
- **bezhansalleh/filament-shield** (v3.3) - Role-based permissions
- **spatie/laravel-permission** (v6.20) - Permission management backend

### Location Detection Architecture
The system uses a multi-widget approach for location-based attendance:

#### LocationDetectionWidget
- **File**: `app/Filament/Paramedis/Widgets/LocationDetectionWidget.php`
- **Purpose**: Detects user location and validates distance to clinic
- **Technology**: Livewire + JavaScript Geolocation API
- **Features**: Device info detection, distance calculation, attendance radius validation

#### ClinicMapWidget  
- **File**: `app/Filament/Paramedis/Widgets/ClinicMapWidget.php`
- **Purpose**: Interactive map showing clinic location and attendance radius
- **Technology**: dotswan/filament-map-picker with OpenStreetMap
- **Features**: Clinic marker, user location button, 100m attendance radius

#### AttendanceButtonWidget
- **File**: `app/Filament/Paramedis/Widgets/AttendanceButtonWidget.php`
- **Purpose**: Real-time clock and check-in/out functionality
- **Technology**: Server-side time management with Jakarta timezone
- **Features**: Live clock, face recognition integration, attendance validation

### Key Principles for Location Widgets
1. **Use existing Filament plugins** - Avoid custom Alpine.js implementations
2. **Server-side validation** - Distance calculation and attendance rules on backend  
3. **No JavaScript polling** - Set `pollingInterval = null` to prevent clock destruction
4. **Livewire event communication** - Use `dispatch()` for JS-to-PHP communication
5. **Proper error handling** - Filament notifications for permission denied, timeout, etc.

### User Model Panel Access
The `User` model implements `FilamentUser` interface with `canAccessPanel()` method:
```php
public function canAccessPanel(Panel $panel): bool
{
    if ($panel->getId() === 'admin') return $this->hasRole('admin');
    if ($panel->getId() === 'petugas') return $this->hasRole('petugas');
    if ($panel->getId() === 'paramedis') return $this->hasRole('paramedis');
    if ($panel->getId() === 'bendahara') return $this->hasRole('bendahara');
    return false;
}
```