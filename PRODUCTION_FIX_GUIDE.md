# 🚀 PRODUCTION FIX GUIDE - Dr. Yaya Welcome Message

## 🎯 Issue
- **Problem**: Welcome message shows "Selamat Siang, yaya" instead of "Selamat Siang, Dr. Yaya Mulyana, M.Kes"
- **Root Cause**: Production database has wrong `nama_lengkap` OR deployed code is outdated/cached

## 🔧 Complete Fix Process

### Step 1: Check Production Database
```bash
ssh to_hostinger_server
cd /path/to/dokterku
php artisan tinker
```

In tinker, run:
```php
// Check current data
$d = App\Models\Dokter::where('username', 'yaya')->first();
echo "Username: " . $d->username;
echo "Nama Lengkap: " . $d->nama_lengkap;
echo "User Name: " . $d->user->name;
exit
```

### Step 2: Fix Database (if needed)
If `nama_lengkap` is not "Dr. Yaya Mulyana, M.Kes", run:

```php
php artisan tinker

// Fix nama_lengkap while preserving username for login
$d = App\Models\Dokter::where('username', 'yaya')->first();
if ($d) {
    // Update dokter nama_lengkap
    $d->nama_lengkap = 'Dr. Yaya Mulyana, M.Kes';
    $d->save();
    
    // Also update user name for consistency
    if ($d->user) {
        $d->user->name = 'Dr. Yaya Mulyana, M.Kes';
        $d->user->save();
    }
    
    echo "✅ Fixed! Username: " . $d->username . ", Nama: " . $d->nama_lengkap;
} else {
    echo "❌ Dokter not found!";
}
exit
```

### Step 3: Check Routes File
Verify that `routes/web.php` has the updated code around line 177:

```php
Route::get('/mobile-app', function () {
    $user = auth()->user();
    $token = $user->createToken('mobile-app-dokter-' . now()->timestamp)->plainTextToken;
    
    $hour = now()->hour;
    $greeting = $hour < 12 ? 'Selamat Pagi' : ($hour < 17 ? 'Selamat Siang' : 'Selamat Malam');
    
    // Get dokter data for more accurate name
    $dokter = \App\Models\Dokter::where('user_id', $user->id)->first();
    $displayName = $dokter ? $dokter->nama_lengkap : $user->name;
    
    $userData = [
        'name' => $displayName,
        'email' => $user->email,
        'greeting' => $greeting,
        'initials' => strtoupper(substr($displayName ?? 'DA', 0, 2))
    ];
    
    return view('mobile.dokter.app', compact('token', 'userData'));
})->name('mobile-app')->middleware('throttle:1000,1');
```

### Step 4: Clear All Caches
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan optimize:clear
```

### Step 5: Test the Fix
1. Go to https://dokterkuklinik.com/dokter/mobile-app
2. Login with username: `yaya`
3. Check if welcome shows: "Selamat Siang, Dr. Yaya Mulyana, M.Kes"

### Step 6: Debug API (if still not working)
In browser console (F12), run:
```javascript
// Test if API is returning correct data
fetch('/api/v2/dashboards/dokter/', {
    headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
    },
    credentials: 'include'
}).then(r => r.json()).then(data => {
    console.log('API Response:', data);
    console.log('Dokter nama:', data.data?.dokter?.nama_lengkap);
    console.log('User name:', data.data?.user?.name);
});
```

## 🚨 Emergency Quick Fix
If all else fails, hardcode the fix temporarily in `routes/web.php`:

```php
// Emergency hardcode fix
$userData = [
    'name' => 'Dr. Yaya Mulyana, M.Kes', // Hardcoded
    'email' => $user->email,
    'greeting' => $greeting,
    'initials' => 'DR'
];
```

## ✅ Expected Results
After fixing:
- Welcome message: "Selamat Siang, Dr. Yaya Mulyana, M.Kes" ✅
- Login still works with username: `yaya` ✅
- Attendance data appears ✅
- Performance metrics show ✅

## 📋 Important Notes
1. **NEVER** change `username` - it's used for login authentication
2. `nama_lengkap` is for display purposes only
3. Always update both `dokters.nama_lengkap` AND `users.name`
4. Clear all caches after any changes
5. Test login functionality after database updates

## 🔍 Verification Commands
```bash
# Check if fix worked
curl -H 'Accept: application/json' https://dokterkuklinik.com/api/v2/dashboards/dokter/

# Run full diagnostic
php hostinger-production-diagnostic.php
```