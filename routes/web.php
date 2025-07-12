<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Manager\ManagerDashboardController;
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
Route::post('/login', [UnifiedAuthController::class, 'store'])->name('unified.login');
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
    Route::get('/manager/dashboard', [ManagerDashboardController::class, 'index'])->name('manager.dashboard');
    Route::get('/treasurer/dashboard', [TreasurerDashboardController::class, 'index'])->name('treasurer.dashboard');
    Route::get('/staff/dashboard', [StaffDashboardController::class, 'index'])->name('staff.dashboard');
    Route::get('/doctor/dashboard', function () {
        return view('doctor.dashboard');
    })->middleware('role:dokter')->name('doctor.dashboard');
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

// require __DIR__.'/auth.php'; // Using unified auth instead
