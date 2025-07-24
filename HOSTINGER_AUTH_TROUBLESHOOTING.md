# Hostinger Production Authentication Troubleshooting Guide

## Masalah Yang Dihadapi

Setelah reset password admin di database Laravel ke `admin123` dengan hash bcrypt yang valid, sistem tetap tidak bisa login dengan error authentication failed.

## Root Cause Analysis

### 1. Masalah Utama: Password Hash Tidak Valid
- **Penyebab**: Password hash yang di-generate secara manual di database tidak sesuai dengan algoritma bcrypt Laravel
- **Gejala**: 
  - Database menunjukkan password hash yang benar formatnya (`$2y$12$...`)
  - Password verifikasi dengan `Hash::check()` tetap mengembalikan `false`
  - Authentication selalu gagal meski data user valid

### 2. Masalah Sekunder: Environment Configuration
- **Session driver**: Menggunakan `file` yang kurang optimal untuk shared hosting
- **Cache configuration**: Tidak optimal untuk production environment
- **Security settings**: Belum sesuai untuk HTTPS production

## Solusi yang Telah Diterapkan

### 1. Fix Password Hash
```php
// Script: fix-admin-password.php
$newPassword = 'admin123';
$newHash = Hash::make($newPassword); // Menggunakan Laravel Hash facade
DB::table('users')->where('id', $adminUser->id)->update(['password' => $newHash]);
```

**Hasil**: Password hash yang benar dan compatible dengan Laravel authentication system.

### 2. Environment Optimization untuk Hostinger
```env
# Recommended .env settings for Hostinger production
APP_ENV=production
APP_DEBUG=false
SESSION_DRIVER=database
SESSION_LIFETIME=1440
SESSION_ENCRYPT=false
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
CACHE_DRIVER=database
QUEUE_CONNECTION=database
```

### 3. Cache Management
```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# For production
php artisan config:cache
php artisan route:cache
```

## Sistem Authentication Laravel Analysis

### Custom Authentication Provider
System menggunakan `CustomEloquentUserProvider` dengan fitur:
- Support login dengan email atau username
- Debugging logs untuk troubleshooting
- Virtual user support untuk pegawai

### User Model Features
- Multi-role support (legacy role_id + Spatie Permission)
- Filament panel access control
- Enhanced permission checking
- Custom authentication methods

### Session Configuration
- Driver: Database (optimal untuk shared hosting)
- Lifetime: 24 jam
- Security: HTTP-only cookies, HTTPS enforcement

## Scripts untuk Debugging

### 1. Complete Authentication Debug
```bash
php debug-hostinger-auth.php
```
**Fungsi**: Comprehensive check untuk semua aspek authentication system

### 2. Password Fix
```bash
php fix-admin-password.php
```
**Fungsi**: Reset password admin dengan hash yang benar

### 3. Production Environment Fix
```bash
php hostinger-production-fix.php
```
**Fungsi**: Complete setup untuk production environment

### 4. Quick Production Test
```bash
php test-production-login.php
```
**Fungsi**: Quick test untuk memverifikasi authentication

## Langkah Debugging Sistematis

### Step 1: Database Verification
```sql
SELECT id, email, username, password, role_id, is_active, email_verified_at 
FROM users WHERE email = 'admin@dokterkuklinik.com';
```

### Step 2: Password Hash Test
```php
$user = User::where('email', 'admin@dokterkuklinik.com')->first();
$isValid = Hash::check('admin123', $user->password);
echo $isValid ? 'Valid' : 'Invalid';
```

### Step 3: Authentication Test
```php
$attempt = Auth::attempt(['email' => 'admin@dokterkuklinik.com', 'password' => 'admin123']);
echo $attempt ? 'Success' : 'Failed';
```

### Step 4: Session Check
```php
// Check session table exists and is writable
$hasTable = DB::getSchemaBuilder()->hasTable('sessions');
$sessionCount = DB::table('sessions')->count();
```

## Kemungkinan Penyebab Lain (untuk referensi)

### 1. Session Issues
- Sessions table tidak ada atau tidak writable
- Session driver tidak sesuai dengan environment
- Cookie domain/path configuration salah

### 2. Custom Provider Issues
- Bug dalam `retrieveByCredentials()` method
- Bug dalam `validateCredentials()` method
- Virtual user conflict

### 3. Middleware Issues
- Authentication middleware tidak properly configured
- Role middleware blocking access
- CSRF token issues

### 4. Environment Issues
- APP_KEY tidak set atau berbeda
- Database connection issues
- File permission problems

### 5. Cache Issues
- Stale configuration cache
- Route cache conflicts
- View cache problems

## Best Practices untuk Hostinger Production

### 1. Environment Settings
- Always use `APP_ENV=production`
- Set `APP_DEBUG=false`
- Use `SESSION_DRIVER=database` for shared hosting
- Enable HTTPS security settings

### 2. Performance Optimization
- Cache config and routes in production
- Use database driver for cache and sessions
- Optimize database queries
- Regular cache clearing

### 3. Security Measures
- Strong APP_KEY generation
- HTTPS enforcement
- Secure cookie settings
- Regular security updates
- Monitor error logs

### 4. Monitoring
- Regular log file checks
- Database performance monitoring
- Session cleanup automation
- Error tracking

## Login Credentials (Post-Fix)

**Email**: `admin@dokterkuklinik.com`  
**Password**: `admin123`

## File Locations

- **Debug Script**: `/debug-hostinger-auth.php`
- **Password Fix**: `/fix-admin-password.php`
- **Production Fix**: `/hostinger-production-fix.php`
- **Production Test**: `/test-production-login.php`
- **This Guide**: `/HOSTINGER_AUTH_TROUBLESHOOTING.md`

## Contact & Support

Jika masih mengalami issues setelah mengikuti guide ini:
1. Check error logs di `storage/logs/laravel.log`
2. Run debugging scripts untuk analisis mendalam
3. Verify environment variables dan configuration
4. Check file permissions dan database connectivity

---

*Last Updated: July 24, 2025*  
*Status: Authentication Fixed & Tested*