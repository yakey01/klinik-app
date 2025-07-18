# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a Laravel 11 healthcare management system called "Dokterku" that uses Filament v3 for admin panels. The system manages medical clinic operations including staff management, patient records, financial tracking, and role-based dashboards.

## Environment Configuration

### Database Setup
- **Local Development**: MySQL (as configured in .env)
- **Production Hosting**: SQLite (due to shared hosting limitations)
- Use `.env.production` for hosting deployment with SQLite configuration

### Deployment Notes
- Hosting uses Composer 1 (requires vendor upload from local)
- Database credentials may differ - use SQLite for reliability
- Run `scripts/deploy-hosting.sh` for automated deployment

### GitHub Workflows
- **Enhanced Deploy** (`.github/workflows/enhanced-deploy.yml`): Full production deployment with testing, backup, and rollback
- **Feature Preview** (`.github/workflows/feature-branch-preview.yml`): Automated builds for feature/design/content branches
- **Development Tools** (`.github/workflows/development-tools.yml`): Manual utilities (backup, restore, cache clear, asset rebuild)

### Workflow Usage
- `git push origin main` - Triggers full deployment to production
- `git push origin feature/branch-name` - Triggers preview build and validation
- Manual workflows available in GitHub Actions for database backup, cache clearing, vendor sync

## Development Commands

### Essential Commands
- `composer dev` - Start development environment (runs server, queue, logs, and Vite concurrently)
- `composer test` - Run tests with config clearing
- `npm run dev` - Start Vite development server
- `npm run build` - Build production assets
- `php artisan serve` - Start Laravel development server
- `php artisan queue:listen --tries=1` - Start queue worker
- `php artisan pail --timeout=0` - Start log monitoring

### Filament-Specific Commands
- `php artisan filament:clear-cached-components` - Clear Filament component cache (run after adding new widgets/resources)
- `php artisan filament:upgrade` - Upgrade Filament (runs automatically after composer updates)

### Asset Management
- `npm run react-dev` - Run React development server with separate config
- `npm run react-build` - Build React components for production
- `npm run watch-react` - Watch React files for changes

### Database Management
- `php artisan migrate` - Run database migrations
- Touch `database/database.sqlite` if using SQLite (default setup)

## Architecture Overview

### Multi-Panel Filament System
The application uses **5 separate Filament panels** with distinct themes and access controls:

1. **Admin Panel** (`/admin`) - `AdminPanelProvider.php`
   - Full system administration
   - User management, system settings, security
   - Theme: `resources/css/filament/admin/theme.css`

2. **Manajer Panel** (`/manajer`) - `ManajerPanelProvider.php`
   - Executive dashboard with KPI widgets
   - Strategic planning, performance analytics
   - Theme: `resources/css/filament/manajer/theme.css`

3. **Bendahara Panel** (`/bendahara`) - `BendaharaPanelProvider.php`
   - Financial management and validation
   - Budget tracking, financial alerts
   - Theme: `resources/css/filament/bendahara/theme.css`

4. **Petugas Panel** (`/petugas`) - `PetugasPanelProvider.php`
   - Staff operations (patient entry, procedures)
   - Daily data entry and management
   - Theme: `resources/css/filament/petugas/theme.css`

5. **Paramedis Panel** (`/paramedis`) - `ParamedisPanelProvider.php`
   - Mobile-optimized for paramedic staff
   - Attendance, procedures, schedules
   - Theme: `resources/css/filament/paramedis-mobile.css`

### Key Models and Relationships

**Core Models:**
- `User` - Authentication with role-based access (uses Spatie Permission)
- `Pegawai` - Employee management (has `jenis_pegawai`: Paramedis/Non-Paramedis)
- `Dokter` - Doctor management (has `jabatan`: dokter_umum/dokter_gigi/dokter_spesialis)
- `Pasien` - Patient records
- `Tindakan` - Medical procedures (links dokter_id, paramedis_id, non_paramedis_id)
- `Pendapatan` - Revenue tracking
- `Pengeluaran` - Expense tracking

**Important Relationships:**
- `Tindakan` has separate foreign keys for different staff types (dokter_id, paramedis_id, non_paramedis_id)
- Revenue attribution is done through `jasa_dokter`, `jasa_paramedis`, `jasa_non_paramedis` fields
- Role-based filtering throughout the system uses `jenis_pegawai` field for staff categorization

### Panel-Specific Architecture

**Widget System:**
- Each panel has dedicated widgets in `app/Filament/{Panel}/Widgets/`
- Widgets must be registered in both the PanelProvider and specific dashboard pages
- New widgets require `php artisan filament:clear-cached-components` to be recognized

**Resource Organization:**
- Resources are panel-specific: `app/Filament/{Panel}/Resources/`
- Shared resources in `app/Filament/Resources/` for admin panel
- Each resource has its own Pages subdirectory

**Authentication Flow:**
- Unified auth controller handles login for all panels
- Role-based redirection in `DashboardController`
- Panel-specific access control in each PanelProvider

## Development Guidelines

### Adding New Widgets
1. Create widget in appropriate panel directory
2. Register in PanelProvider `->widgets()` array
3. Add to dashboard page `getWidgets()` method if needed
4. Run `php artisan filament:clear-cached-components`
5. Build assets with `npm run build`

### Role-Based Development
- Use `jenis_pegawai` field for Paramedis/Non-Paramedis distinction
- Use `jabatan` field for doctor specialization
- Filter `Tindakan` queries by appropriate foreign key (dokter_id, paramedis_id, non_paramedis_id)
- Revenue calculations should use role-specific fields (jasa_dokter, jasa_paramedis, jasa_non_paramedis)

### Database Queries
- Always use proper relationships defined in models
- Use `whereNotNull()` for role-specific tindakan filtering
- Calculate efficiency metrics using real data, not dummy values
- Include proper date filtering for monthly/yearly comparisons

### Theme Management
- Each panel has its own CSS theme file
- Themes must be registered in `vite.config.js` input array
- Use `data-filament-panel-id` attribute for panel-specific styling
- Run `npm run build` after theme changes

## Testing
- Uses Pest PHP testing framework
- Tests are in `tests/` directory
- Run with `composer test` (includes config clearing)

## Important Notes

### Panel Isolation
- Each panel operates independently with its own resources and widgets
- No cross-panel resource sharing (except admin panel accessing all)
- Panel-specific navigation and theming

### Performance Considerations
- Use model relationships instead of raw queries
- Implement caching for frequently accessed data
- Use proper indexing for date-based queries
- Consider widget lazy loading for dashboard performance

### Security
- All panels use role-based access control
- GPS spoofing detection for attendance
- Audit logging for sensitive operations
- Device management for mobile users

### Mobile Integration
- Paramedis panel is mobile-optimized
- React components for advanced mobile features
- Separate mobile app routes for doctors
- GPS integration for attendance tracking