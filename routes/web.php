<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Treasurer\TreasurerDashboardController;
use App\Http\Controllers\Staff\StaffDashboardController;
use App\Http\Controllers\Auth\UnifiedAuthController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

Route::get('/', function () {
    if (Auth::check()) {
        return redirect('/dashboard');
    }
    return redirect('/login');
});

// Unified authentication routes
Route::get('/login', [UnifiedAuthController::class, 'create'])->name('login');
Route::post('/login', [UnifiedAuthController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('unified.login');
Route::post('/logout', [UnifiedAuthController::class, 'destroy'])->name('logout');

// Email verification routes
Route::get('/email/verify', function () {
    return view('auth.verify-email');
})->middleware('auth')->name('verification.notice');

Route::get('/email/verify/{id}/{hash}', function (\Illuminate\Foundation\Auth\EmailVerificationRequest $request) {
    $request->fulfill();
    return redirect('/dashboard');
})->middleware(['auth', 'signed'])->name('verification.verify');

Route::post('/email/verification-notification', function (\Illuminate\Http\Request $request) {
    $request->user()->sendEmailVerificationNotification();
    return back()->with('message', 'Verification link sent!');
})->middleware(['auth', 'throttle:6,1'])->name('verification.send');

// Main dashboard route that redirects based on role
Route::get('/dashboard', DashboardController::class)->middleware(['auth'])->name('dashboard');

// Role-specific dashboard routes
Route::middleware(['auth'])->group(function () {
    Route::get('/legacy-admin/dashboard', [AdminDashboardController::class, 'index'])->name('legacy-admin.dashboard');
    Route::get('/manager/dashboard', function () {
        return redirect('/manajer');
    })->name('manager.dashboard');
    Route::get('/treasurer/dashboard', [TreasurerDashboardController::class, 'index'])->name('treasurer.dashboard');
    Route::get('/staff/dashboard', [StaffDashboardController::class, 'index'])->name('staff.dashboard');
    // Keep legacy route for backward compatibility
    Route::get('/doctor/dashboard', function () {
        return redirect('/dokter');
    })->middleware('role:dokter');
    Route::get('/non-paramedic/dashboard', function () {
        return view('non-paramedic.dashboard');
    })->middleware('role:non_paramedis')->name('non-paramedic.dashboard');
});

// Legacy Admin routes (moved from /admin to /legacy-admin)
Route::middleware(['auth', 'role:admin'])->prefix('legacy-admin')->name('legacy-admin.')->group(function () {
    // User management routes
    Route::resource('users', \App\Http\Controllers\Admin\UserController::class);
    Route::resource('roles', \App\Http\Controllers\Admin\RoleController::class);
});

// Settings routes (Admin only)
Route::middleware(['auth', 'role:admin'])->prefix('settings')->name('settings.')->group(function () {
    // User Management
    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Settings\UserManagementController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\Settings\UserManagementController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\Settings\UserManagementController::class, 'store'])->name('store');
        Route::get('/{user}/edit', [\App\Http\Controllers\Settings\UserManagementController::class, 'edit'])->name('edit');
        Route::put('/{user}', [\App\Http\Controllers\Settings\UserManagementController::class, 'update'])->name('update');
        Route::post('/{user}/reset-password', [\App\Http\Controllers\Settings\UserManagementController::class, 'resetPassword'])->name('reset-password');
        Route::post('/{user}/toggle-status', [\App\Http\Controllers\Settings\UserManagementController::class, 'toggleStatus'])->name('toggle-status');
    });
    
    // System Configuration
    Route::prefix('config')->name('config.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Settings\SystemConfigController::class, 'index'])->name('index');
        Route::post('/', [\App\Http\Controllers\Settings\SystemConfigController::class, 'update'])->name('update');
        Route::post('/security', [\App\Http\Controllers\Settings\SystemConfigController::class, 'updateSecurity'])->name('update-security');
    });
    
    // Backup & Export
    Route::prefix('backup')->name('backup.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Settings\BackupController::class, 'index'])->name('index');
        Route::get('/export-excel', [\App\Http\Controllers\Settings\BackupController::class, 'exportMasterData'])->name('export-excel');
        Route::get('/export-json', [\App\Http\Controllers\Settings\BackupController::class, 'exportJson'])->name('export-json');
        Route::post('/import-json', [\App\Http\Controllers\Settings\BackupController::class, 'importJson'])->name('import-json');
    });
    
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// GPS Test Route
Route::get('/test-gps', function () {
    return view('components.gps-detector');
})->name('test.gps');

// Debug GPS Route
Route::get('/debug-gps', function () {
    return response()->file(public_path('debug-gps.html'));
})->name('debug.gps');

// Employee Card Download Route
Route::middleware(['auth'])->group(function () {
    Route::get('/employee-card/{card}/download', function (\App\Models\EmployeeCard $card) {
        if (!$card->pdf_path || !\Illuminate\Support\Facades\Storage::disk('public')->exists($card->pdf_path)) {
            abort(404, 'File kartu tidak ditemukan');
        }
        
        $filePath = storage_path('app/public/' . $card->pdf_path);
        $fileName = 'Kartu_' . $card->employee_name . '_' . $card->card_number . '.pdf';
        
        return response()->download($filePath, $fileName);
    })->name('employee-card.download');
});


// Dokter Dashboard Routes - DISABLED (Using Filament Panel instead at /dokter)
// Route::middleware(['auth', 'role:dokter'])->prefix('dokter')->name('dokter.')->group(function () {
//     Route::get('/dashboard', [\App\Http\Controllers\Dokter\DashboardController::class, 'index'])->name('dashboard');
//     
//     // Presensi Routes
//     Route::get('/presensi', [\App\Http\Controllers\Dokter\PresensiController::class, 'index'])->name('presensi.index');
//     Route::post('/presensi/masuk', [\App\Http\Controllers\Dokter\PresensiController::class, 'masuk'])->name('presensi.masuk');
//     Route::post('/presensi/pulang', [\App\Http\Controllers\Dokter\PresensiController::class, 'pulang'])->name('presensi.pulang');
//     
//     // Jaspel Routes
//     Route::get('/jaspel', [\App\Http\Controllers\Dokter\JaspelController::class, 'index'])->name('jaspel.index');
//     Route::get('/jaspel/export', [\App\Http\Controllers\Dokter\JaspelController::class, 'export'])->name('jaspel.export');
// });

// Redirect legacy dokter routes to Filament panel
Route::middleware(['auth'])->group(function () {
    Route::get('/dokter/dashboard', function () {
        return redirect('/dokter');
    });
    Route::get('/dokter/presensi', function () {
        return redirect('/dokter/dokter-presensis');
    });
    Route::get('/dokter/jaspel', function () {
        return redirect('/dokter/jaspel-dokters');
    });
});

// Dokter Gigi Dashboard Routes (Isolated from Filament)
Route::middleware(['auth', 'role:dokter_gigi'])->prefix('dokter-gigi')->name('dokter-gigi.')->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\DokterGigi\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/jaspel', [\App\Http\Controllers\DokterGigi\DashboardController::class, 'jaspel'])->name('jaspel');
});


// require __DIR__.'/auth.php'; // Using unified auth instead
