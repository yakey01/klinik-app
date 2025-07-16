<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Bendahara\TreasurerDashboardController;
use App\Http\Controllers\Petugas\StaffDashboardController;
use App\Http\Controllers\NonParamedis\DashboardController as NonParamedisDashboardController;
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

// Test manajer login
Route::get('/test-manajer-login', function () {
    $user = \App\Models\User::where('email', 'tina@manajer.com')->first();
    if ($user && \Illuminate\Support\Facades\Hash::check('password', $user->password)) {
        \Illuminate\Support\Facades\Auth::login($user);
        return response()->json([
            'success' => true,
            'user' => $user->name,
            'role' => $user->role->name,
            'can_access_manajer' => $user->hasRole('manajer'),
            'redirect_url' => '/manajer'
        ]);
    }
    return response()->json(['success' => false, 'message' => 'Login failed']);
})->name('test.manajer.login');

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
    
    // Filament notification action routes
    Route::post('/filament/jadwal-jaga/create-missing-users', function () {
        $missingUsers = session()->get('missing_users_data', []);
        
        if (empty($missingUsers)) {
            return redirect()->back()->with('error', 'Data pegawai yang hilang tidak ditemukan.');
        }
        
        // Create instance of the page to call the method
        $page = new \App\Filament\Resources\JadwalJagaResource\Pages\ListJadwalJagas();
        $page->createMissingUserAccounts($missingUsers);
        
        // Clear session data
        session()->forget('missing_users_data');
        
        return redirect()->back()->with('success', 'Proses pembuatan akun telah dimulai.');
    })->name('filament.jadwal-jaga.create-missing-users');
    Route::get('/treasurer/dashboard', function () {
        return redirect('/bendahara');
    })->name('treasurer.dashboard');
    Route::get('/staff/dashboard', [StaffDashboardController::class, 'index'])->name('staff.dashboard');
    // Redirect DOKTER to Flutter mobile app
    Route::get('/doctor/dashboard', function () {
        return redirect('/dokter/mobile-app');
    })->middleware('role:dokter');
    
    // DOKTER Mobile App Routes (Replaces Filament dashboard)
    Route::middleware(['auth', 'role:dokter'])->prefix('dokter')->name('dokter.')->group(function () {
        // Base dokter route - redirect to mobile app
        Route::get('/', function () {
            return redirect()->route('dokter.mobile-app');
        })->name('index');
        
        Route::get('/mobile-app', function () {
            $user = auth()->user();
            $token = $user->createToken('mobile-app-dokter-' . now()->timestamp)->plainTextToken;
            return view('mobile.dokter.app', compact('token'));
        })->name('mobile-app');
        
        // Legacy routes - redirect to new mobile app
        Route::get('/dashboard', function () {
            return redirect()->route('dokter.mobile-app');
        })->name('dashboard');
        Route::get('/presensi', function () {
            return redirect()->route('dokter.mobile-app');
        })->name('presensi');
        Route::get('/jaspel', function () {
            return redirect()->route('dokter.mobile-app');
        })->name('jaspel');
        Route::get('/tindakan', function () {
            return redirect()->route('dokter.mobile-app');
        })->name('tindakan');
        
        // Any other dokter routes should redirect to mobile app
        Route::fallback(function () {
            return redirect()->route('dokter.mobile-app');
        });
    });
    // Non-Paramedis Mobile App Routes (Replaces old dashboard)
    Route::middleware(['auth', 'role:non_paramedis'])->prefix('nonparamedis')->name('nonparamedis.')->group(function () {
        Route::get('/app', function () {
            $user = auth()->user();
            $token = $user->createToken('mobile-app-' . now()->timestamp)->plainTextToken;
            return view('mobile.nonparamedis.app', compact('token'));
        })->name('app');
        
        // Legacy routes - redirect to new mobile app
        Route::get('/dashboard', function () {
            return redirect()->route('nonparamedis.app');
        })->name('dashboard');
        Route::get('/presensi', function () {
            return redirect()->route('nonparamedis.app');
        })->name('presensi');
        Route::get('/jadwal', function () {
            return redirect()->route('nonparamedis.app');
        })->name('jadwal');
    });
    
    // React Dashboard Test Route (untuk semua role)
    Route::get('/react-dashboard-demo', function () {
        // Debug current user
        $user = auth()->user();
        if (!$user) {
            return redirect('/login')->with('error', 'Please login first');
        }
        
        \Log::info('Paramedis React Dashboard Access', [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'user_role' => $user->role?->name ?? 'no_role',
            'has_role' => $user->role ? 'YES' : 'NO'
        ]);
        
        // Allow all roles for demo
        // if ($user->role?->name !== 'paramedis') {
        //     return response()->json([
        //         'error' => 'Access denied',
        //         'user_role' => $user->role?->name ?? 'no_role',
        //         'required_role' => 'paramedis'
        //     ], 403);
        // }
        
        return view('react-dashboard-standalone');
    })->middleware('auth')->name('paramedis.react.dashboard');
    
    // Debug route to check users and roles
    Route::get('/debug-users', function () {
        $users = \App\Models\User::with('role')->get(['id', 'name', 'email', 'role_id']);
        return response()->json([
            'current_user' => auth()->user() ? [
                'id' => auth()->user()->id,
                'name' => auth()->user()->name,
                'email' => auth()->user()->email,
                'role' => auth()->user()->role?->name ?? 'no_role'
            ] : 'not_logged_in',
            'all_users' => $users->map(function($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role?->name ?? 'no_role'
                ];
            })
        ]);
    })->middleware('auth');
    
    // Simple API test route
    Route::get('/api-test', function () {
        return response()->json([
            'status' => 'success',
            'message' => 'API working',
            'data' => [
                'jaspel_monthly' => 15200000,
                'paramedis_name' => 'Test User'
            ]
        ]);
    });
    
    // Standalone React Dashboard (tanpa Filament)
    Route::get('/react-dashboard', function () {
        $user = auth()->user();
        if (!$user) {
            return redirect('/login')->with('error', 'Please login first');
        }
        
        return view('react-dashboard-standalone');
    })->middleware('auth')->name('react.dashboard');
    
    // New Jaspel Dashboard (Premium Design)
    Route::get('/jaspel-dashboard', function () {
        $user = auth()->user();
        if (!$user) {
            return redirect('/login')->with('error', 'Please login first');
        }
        
        return view('paramedis-jaspel-dashboard');
    })->middleware('auth')->name('jaspel.dashboard');
    
    // Premium React Native Dashboard - Redirect to Filament Panel
    Route::get('/premium-dashboard', function () {
        $user = auth()->user();
        if (!$user) {
            return redirect('/login')->with('error', 'Please login first');
        }
        
        // Redirect paramedis users to their proper panel
        if ($user->hasRole('paramedis')) {
            return redirect('/paramedis');
        }
        
        return view('premium-paramedis-dashboard-simple');
    })->middleware('auth')->name('premium.dashboard');
    
    // Direct test route for premium dashboard
    Route::get('/premium-test', function () {
        return view('premium-paramedis-dashboard-simple');
    })->middleware('auth');
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
    
    // Telegram Settings
    Route::prefix('telegram')->name('telegram.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Settings\TelegramController::class, 'index'])->name('index');
        Route::post('/', [\App\Http\Controllers\Settings\TelegramController::class, 'update'])->name('update');
        Route::post('/test', [\App\Http\Controllers\Settings\TelegramController::class, 'testNotification'])->name('test');
        Route::get('/bot-info', [\App\Http\Controllers\Settings\TelegramController::class, 'getBotInfo'])->name('bot-info');
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

// DOKTER DEBUG ROUTE - TEMPORARY
Route::get('/debug-dokter', function () {
    $user = auth()->user();
    if (!$user) {
        return response()->json([
            'error' => 'Not authenticated',
            'message' => 'Please login first',
            'redirect' => route('login')
        ]);
    }
    
    return response()->json([
        'user' => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role?->name ?? 'no_role',
            'role_id' => $user->role_id,
            'has_dokter_role' => $user->hasRole('dokter'),
            'all_roles' => $user->roles->pluck('name')->toArray(),
        ],
        'routes' => [
            'dokter_index' => route('dokter.index'),
            'dokter_mobile_app' => route('dokter.mobile-app'),
            'dokter_dashboard' => route('dokter.dashboard'),
        ],
        'auth_check' => auth()->check(),
        'middleware_test' => 'If you see this, basic auth is working'
    ]);
})->middleware(['auth']);

// DOKTER MOBILE APP TEST - TEMPORARY (NO AUTH)
Route::get('/debug-dokter-mobile', function () {
    try {
        $user = auth()->user();
        $token = $user ? $user->createToken('mobile-app-dokter-debug-' . now()->timestamp)->plainTextToken : 'no-token';
        
        return view('mobile.dokter.app', compact('token'));
    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Failed to load mobile app',
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ], 500);
    }
});

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


// OLD DOKTER DASHBOARD ROUTES COMPLETELY REMOVED
// System now uses Filament Panel at /dokter route exclusively

// Dokter Gigi Dashboard Routes (Isolated from Filament)
Route::middleware(['auth', 'role:dokter_gigi'])->prefix('dokter-gigi')->name('dokter-gigi.')->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\Dokter\DokterGigiDashboardController::class, 'index'])->name('dashboard');
    Route::get('/jaspel', [\App\Http\Controllers\Dokter\DokterGigiDashboardController::class, 'jaspel'])->name('jaspel');
});


// require __DIR__.'/auth.php'; // Using unified auth instead
