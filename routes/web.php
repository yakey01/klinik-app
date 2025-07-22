<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Bendahara\TreasurerDashboardController;
use App\Http\Controllers\Petugas\StaffDashboardController;
use App\Http\Controllers\NonParamedis\DashboardController as NonParamedisDashboardController;
use App\Http\Controllers\Auth\UnifiedAuthController;
use Illuminate\Support\Facades\Route;

// Include test routes
require __DIR__.'/test.php';
require __DIR__.'/test-models.php';
use Illuminate\Support\Facades\Auth;

// EMERGENCY TEST ROUTE - Top priority
Route::get('/test-emergency', function () {
    return response()->json(['status' => 'Emergency route works', 'timestamp' => now()]);
});

Route::get('/', function () {
    if (Auth::check()) {
        return redirect('/dashboard');
    }
    return redirect('/login');
});


// Unified authentication routes
Route::get('/login', [UnifiedAuthController::class, 'create'])->name('login');
Route::post('/login', [UnifiedAuthController::class, 'store'])
    ->middleware('throttle:20,1')
    ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class])
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
})->middleware(['auth', 'throttle:12,1'])->name('verification.send');

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
    Route::get('/petugas/enhanced-dashboard', [StaffDashboardController::class, 'enhanced'])->name('petugas.enhanced.dashboard');
    
    // Enhanced Petugas Management Routes
    Route::middleware(['auth', 'role:petugas'])->prefix('petugas/enhanced')->name('petugas.enhanced.')->group(function () {
        // Patient Management
        Route::prefix('pasien')->name('pasien.')->group(function () {
            Route::get('/', [App\Http\Controllers\Petugas\Enhanced\PasienController::class, 'index'])->name('index');
            Route::get('/data', [App\Http\Controllers\Petugas\Enhanced\PasienController::class, 'getData'])->name('data');
            Route::get('/create', [App\Http\Controllers\Petugas\Enhanced\PasienController::class, 'create'])->name('create');
            Route::post('/', [App\Http\Controllers\Petugas\Enhanced\PasienController::class, 'store'])->name('store');
            Route::get('/{id}', [App\Http\Controllers\Petugas\Enhanced\PasienController::class, 'show'])->name('show');
            Route::get('/{id}/edit', [App\Http\Controllers\Petugas\Enhanced\PasienController::class, 'edit'])->name('edit');
            Route::put('/{id}', [App\Http\Controllers\Petugas\Enhanced\PasienController::class, 'update'])->name('update');
            Route::delete('/{id}', [App\Http\Controllers\Petugas\Enhanced\PasienController::class, 'destroy'])->name('destroy');
            Route::post('/bulk-delete', [App\Http\Controllers\Petugas\Enhanced\PasienController::class, 'bulkDelete'])->name('bulk-delete');
            Route::post('/export', [App\Http\Controllers\Petugas\Enhanced\PasienController::class, 'export'])->name('export');
            Route::get('/search/autocomplete', [App\Http\Controllers\Petugas\Enhanced\PasienController::class, 'search'])->name('search');
            Route::get('/{id}/timeline', [App\Http\Controllers\Petugas\Enhanced\PasienController::class, 'getTimeline'])->name('timeline');
        });
        
        // Tindakan (Medical Procedure) Management
        Route::prefix('tindakan')->name('tindakan.')->group(function () {
            Route::get('/', [App\Http\Controllers\Petugas\Enhanced\TindakanController::class, 'index'])->name('index');
            Route::get('/data', [App\Http\Controllers\Petugas\Enhanced\TindakanController::class, 'getData'])->name('data');
            Route::get('/create', [App\Http\Controllers\Petugas\Enhanced\TindakanController::class, 'create'])->name('create');
            Route::post('/', [App\Http\Controllers\Petugas\Enhanced\TindakanController::class, 'store'])->name('store');
            Route::get('/{id}', [App\Http\Controllers\Petugas\Enhanced\TindakanController::class, 'show'])->name('show');
            Route::get('/{id}/edit', [App\Http\Controllers\Petugas\Enhanced\TindakanController::class, 'edit'])->name('edit');
            Route::put('/{id}', [App\Http\Controllers\Petugas\Enhanced\TindakanController::class, 'update'])->name('update');
            Route::delete('/{id}', [App\Http\Controllers\Petugas\Enhanced\TindakanController::class, 'destroy'])->name('destroy');
            Route::post('/bulk-update-status', [App\Http\Controllers\Petugas\Enhanced\TindakanController::class, 'bulkUpdateStatus'])->name('bulk-update-status');
            Route::get('/{patientId}/timeline', [App\Http\Controllers\Petugas\Enhanced\TindakanController::class, 'getTimeline'])->name('patient-timeline');
            Route::post('/export', [App\Http\Controllers\Petugas\Enhanced\TindakanController::class, 'export'])->name('export');
            Route::get('/search/autocomplete', [App\Http\Controllers\Petugas\Enhanced\TindakanController::class, 'search'])->name('search');
        });
        
        // Pendapatan (Revenue) Management
        Route::prefix('pendapatan')->name('pendapatan.')->group(function () {
            Route::get('/', [App\Http\Controllers\Petugas\Enhanced\PendapatanController::class, 'index'])->name('index');
            Route::get('/data', [App\Http\Controllers\Petugas\Enhanced\PendapatanController::class, 'getData'])->name('data');
            Route::get('/create', [App\Http\Controllers\Petugas\Enhanced\PendapatanController::class, 'create'])->name('create');
            Route::post('/', [App\Http\Controllers\Petugas\Enhanced\PendapatanController::class, 'store'])->name('store');
            Route::get('/{id}', [App\Http\Controllers\Petugas\Enhanced\PendapatanController::class, 'show'])->name('show');
            Route::get('/{id}/edit', [App\Http\Controllers\Petugas\Enhanced\PendapatanController::class, 'edit'])->name('edit');
            Route::put('/{id}', [App\Http\Controllers\Petugas\Enhanced\PendapatanController::class, 'update'])->name('update');
            Route::delete('/{id}', [App\Http\Controllers\Petugas\Enhanced\PendapatanController::class, 'destroy'])->name('destroy');
            Route::post('/bulk-create-from-tindakan', [App\Http\Controllers\Petugas\Enhanced\PendapatanController::class, 'bulkCreateFromTindakan'])->name('bulk-create-from-tindakan');
            Route::get('/analytics', [App\Http\Controllers\Petugas\Enhanced\PendapatanController::class, 'getAnalytics'])->name('analytics');
            Route::post('/export', [App\Http\Controllers\Petugas\Enhanced\PendapatanController::class, 'export'])->name('export');
            Route::get('/suggestions', [App\Http\Controllers\Petugas\Enhanced\PendapatanController::class, 'getSuggestions'])->name('suggestions');
        });
        
        // Pengeluaran (Expense) Management
        Route::prefix('pengeluaran')->name('pengeluaran.')->group(function () {
            Route::get('/', [App\Http\Controllers\Petugas\Enhanced\PengeluaranController::class, 'index'])->name('index');
            Route::get('/data', [App\Http\Controllers\Petugas\Enhanced\PengeluaranController::class, 'getData'])->name('data');
            Route::get('/create', [App\Http\Controllers\Petugas\Enhanced\PengeluaranController::class, 'create'])->name('create');
            Route::post('/', [App\Http\Controllers\Petugas\Enhanced\PengeluaranController::class, 'store'])->name('store');
            Route::get('/{id}', [App\Http\Controllers\Petugas\Enhanced\PengeluaranController::class, 'show'])->name('show');
            Route::get('/{id}/edit', [App\Http\Controllers\Petugas\Enhanced\PengeluaranController::class, 'edit'])->name('edit');
            Route::put('/{id}', [App\Http\Controllers\Petugas\Enhanced\PengeluaranController::class, 'update'])->name('update');
            Route::delete('/{id}', [App\Http\Controllers\Petugas\Enhanced\PengeluaranController::class, 'destroy'])->name('destroy');
            Route::post('/bulk-update-status', [App\Http\Controllers\Petugas\Enhanced\PengeluaranController::class, 'bulkUpdateStatus'])->name('bulk-update-status');
            Route::get('/budget-analysis', [App\Http\Controllers\Petugas\Enhanced\PengeluaranController::class, 'getBudgetAnalysisData'])->name('budget-analysis');
            Route::post('/export', [App\Http\Controllers\Petugas\Enhanced\PengeluaranController::class, 'export'])->name('export');
            Route::get('/suggestions', [App\Http\Controllers\Petugas\Enhanced\PengeluaranController::class, 'getSuggestions'])->name('suggestions');
        });
        
        // Jumlah Pasien (Patient Reporting) Management
        Route::prefix('jumlah-pasien')->name('jumlah-pasien.')->group(function () {
            Route::get('/', [App\Http\Controllers\Petugas\Enhanced\JumlahPasienController::class, 'index'])->name('index');
            Route::get('/calendar-data', [App\Http\Controllers\Petugas\Enhanced\JumlahPasienController::class, 'getCalendarData'])->name('calendar-data');
            Route::get('/date-stats', [App\Http\Controllers\Petugas\Enhanced\JumlahPasienController::class, 'getDateStats'])->name('date-stats');
            Route::get('/analytics', [App\Http\Controllers\Petugas\Enhanced\JumlahPasienController::class, 'getAnalytics'])->name('analytics');
            Route::post('/export', [App\Http\Controllers\Petugas\Enhanced\JumlahPasienController::class, 'export'])->name('export');
        });
    });
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
            
            // AGGRESSIVE CACHE BUSTING HEADERS
            return response()
                ->view('mobile.dokter.app', compact('token', 'userData'))
                ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
                ->header('Pragma', 'no-cache')
                ->header('Expires', 'Mon, 01 Jan 1990 00:00:00 GMT')
                ->header('Last-Modified', gmdate('D, d M Y H:i:s') . ' GMT')
                ->header('ETag', '"' . md5(time()) . '"');
        })->name('mobile-app')->middleware('throttle:1000,1');
        
        // EMERGENCY BYPASS ROUTE - Force new bundle loading
        Route::get('/mobile-app-v2', function () {
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
            
            // ULTIMATE CACHE BYPASS
            return response()
                ->view('mobile.dokter.app-emergency', compact('token', 'userData'))
                ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
                ->header('Pragma', 'no-cache')
                ->header('Expires', 'Mon, 01 Jan 1990 00:00:00 GMT')
                ->header('Last-Modified', gmdate('D, d M Y H:i:s') . ' GMT')
                ->header('ETag', '"bypass-' . md5(time() . rand()) . '"');
        })->name('mobile-app-v2')->middleware('throttle:1000,1');
        
        // API endpoint for doctor weekly schedules (for dashboard)
        Route::get('/api/weekly-schedules', [App\Http\Controllers\Api\V2\Dashboards\DokterDashboardController::class, 'getWeeklySchedule'])
            ->name('api.weekly-schedules')->middleware('throttle:1000,1');
            
        // API endpoint for unit kerja schedules (for dashboard)
        Route::get('/api/igd-schedules', [App\Http\Controllers\Api\V2\Dashboards\DokterDashboardController::class, 'getIgdSchedules'])
            ->name('api.igd-schedules')->middleware('throttle:1000,1');
        
        // API endpoint for doctor schedules
        Route::get('/api/schedules', function () {
            $user = auth()->user();
            $userId = $user->id;
            
            // Get current and upcoming schedules for this doctor
            $schedules = \App\Models\JadwalJaga::where('pegawai_id', $userId)
                ->where('unit_kerja', 'Dokter Jaga')
                ->where('tanggal_jaga', '>=', now()->subDays(1)) // Include yesterday for overnight shifts
                ->with(['shiftTemplate'])
                ->orderBy('tanggal_jaga')
                ->orderBy('shift_template_id')
                ->take(10)
                ->get()
                ->map(function ($jadwal) {
                    // Determine shift type based on time
                    $jamMasuk = \Carbon\Carbon::parse($jadwal->shiftTemplate->jam_masuk)->hour;
                    $jenis = match(true) {
                        $jamMasuk >= 6 && $jamMasuk < 14 => 'pagi',
                        $jamMasuk >= 14 && $jamMasuk < 22 => 'siang', 
                        default => 'malam'
                    };
                    
                    // Use dynamic location from database or default to unit kerja
                    $lokasi = 'Dokter Jaga'; // Use consistent unit kerja from admin
                    
                    return [
                        'id' => (string) $jadwal->id,
                        'tanggal' => $jadwal->tanggal_jaga->format('Y-m-d'),
                        'waktu' => $jadwal->shiftTemplate->jam_masuk_format . ' - ' . $jadwal->shiftTemplate->jam_pulang_format,
                        'lokasi' => $lokasi,
                        'jenis' => $jenis,
                        'status' => $jadwal->tanggal_jaga->isPast() ? 'completed' : 'scheduled',
                        'shift_nama' => $jadwal->shiftTemplate->nama_shift,
                        'status_jaga' => $jadwal->status_jaga,
                        'keterangan' => $jadwal->keterangan
                    ];
                });
            
            return response()->json($schedules);
        })->name('api.schedules')->middleware('throttle:1000,1');
        
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
    
    // EMERGENCY DOKTER BYPASS ROUTE (Outside middleware group for debugging)
    Route::get('/dokter/mobile-app-emergency', function () {
        $user = auth()->user();
        
        // Check if user exists and is authenticated
        if (!$user) {
            return response()->json(['error' => 'User not authenticated']);
        }
        
        // Check role manually
        $hasRole = $user->roles()->where('name', 'dokter')->exists();
        if (!$hasRole) {
            return response()->json([
                'error' => 'Role check failed',
                'user_id' => $user->id,
                'user_name' => $user->name,
                'roles' => $user->roles()->pluck('name')->toArray()
            ]);
        }
        
        $token = $user->createToken('mobile-app-dokter-emergency-' . now()->timestamp)->plainTextToken;
        
        $hour = now()->hour;
        $greeting = $hour < 12 ? 'Selamat Pagi' : ($hour < 17 ? 'Selamat Siang' : 'Selamat Malam');
        
        // Get dokter data
        $dokter = \App\Models\Dokter::where('user_id', $user->id)->first();
        $displayName = $dokter ? $dokter->nama_lengkap : $user->name;
        
        $userData = [
            'name' => $displayName,
            'email' => $user->email,
            'greeting' => $greeting,
            'initials' => strtoupper(substr($displayName ?? 'DA', 0, 2))
        ];
        
        // ULTIMATE CACHE BYPASS
        return response()
            ->view('mobile.dokter.app-emergency', compact('token', 'userData'))
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', 'Mon, 01 Jan 1990 00:00:00 GMT');
    })->middleware('auth');
    
    // ULTIMATE EMERGENCY BYPASS - Completely independent route
    Route::get('/emergency-dokter-bypass', function () {
        $user = auth()->user();
        
        // Check if user exists and is authenticated
        if (!$user) {
            return response()->json(['error' => 'User not authenticated'], 401);
        }
        
        // Get all user info for debugging
        $userData = [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'user_email' => $user->email,
            'roles' => $user->roles()->pluck('name')->toArray(),
            'role_id' => $user->role_id ?? null,
            'authenticated' => auth()->check(),
        ];
        
        // Check if user has dokter role in any way
        $hasRole = $user->roles()->where('name', 'dokter')->exists();
        $hasRoleId = $user->role_id && \App\Models\Role::find($user->role_id)?->name === 'dokter';
        
        if (!$hasRole && !$hasRoleId) {
            return response()->json([
                'error' => 'Role check failed',
                'debug' => $userData,
                'role_check_spatie' => $hasRole,
                'role_check_direct' => $hasRoleId,
                'message' => 'User does not have dokter role'
            ], 403);
        }
        
        // If user has role, generate token and load app
        $token = $user->createToken('emergency-bypass-' . now()->timestamp)->plainTextToken;
        
        $hour = now()->hour;
        $greeting = $hour < 12 ? 'Selamat Pagi' : ($hour < 17 ? 'Selamat Siang' : 'Selamat Malam');
        
        // Get dokter data
        $dokter = \App\Models\Dokter::where('user_id', $user->id)->first();
        $displayName = $dokter ? $dokter->nama_lengkap : $user->name;
        
        $appUserData = [
            'name' => $displayName,
            'email' => $user->email,
            'greeting' => $greeting,
            'initials' => strtoupper(substr($displayName ?? 'DA', 0, 2))
        ];
        
        // ULTIMATE CACHE BYPASS
        return response()
            ->view('mobile.dokter.app-emergency', ['token' => $token, 'userData' => $appUserData])
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', 'Mon, 01 Jan 1990 00:00:00 GMT');
    })->middleware('auth');
    
    // PARAMEDIS Mobile App Routes (Replaces Filament dashboard)
    Route::middleware(['auth', 'role:paramedis'])->prefix('paramedis')->name('paramedis.')->group(function () {
        // Base paramedis route - redirect to mobile app
        Route::get('/', function () {
            return redirect()->route('paramedis.mobile-app');
        })->name('index');
        
        Route::get('/mobile-app', function () {
            $user = auth()->user();
            $token = $user->createToken('mobile-app-paramedis-' . now()->timestamp)->plainTextToken;
            
            $hour = now()->hour;
            $greeting = $hour < 12 ? 'Selamat Pagi' : ($hour < 17 ? 'Selamat Siang' : 'Selamat Malam');
            
            $userData = [
                'name' => $user->name,
                'email' => $user->email,
                'greeting' => $greeting,
                'initials' => strtoupper(substr($user->name ?? 'PA', 0, 2))
            ];
            
            return view('mobile.paramedis.app', compact('token', 'userData'));
        })->name('mobile-app')->middleware('throttle:1000,1');
        
        // API endpoint for paramedis schedules
        Route::get('/api/schedules', function () {
            try {
                $user = auth()->user();
                
                // SECURITY: Ensure user has paramedis role before proceeding
                if (!$user->hasRole('paramedis')) {
                    \Log::warning('Non-paramedis user attempted to access paramedis schedules API', [
                        'user_id' => $user->id,
                        'user_name' => $user->name,
                        'roles' => $user->getRoleNames()->toArray()
                    ]);
                    return response()->json([], 403);
                }
                
                // Get the actual pegawai_id - ONLY for paramedis users
                $pegawai = \App\Models\Pegawai::where('user_id', $user->id)
                    ->where('jenis_pegawai', 'Paramedis')
                    ->first();
                
                if (!$pegawai) {
                    \Log::warning('Paramedis user has no valid pegawai record', [
                        'user_id' => $user->id,
                        'user_name' => $user->name
                    ]);
                    return response()->json([]);
                }
                
                $pegawaiId = $pegawai->user_id; // Use user_id from pegawai table
                
                // Get current and upcoming schedules for this paramedis ONLY
                $schedules = \App\Models\JadwalJaga::where('pegawai_id', $pegawaiId)
                    ->whereIn('unit_kerja', ['Pendaftaran', 'Pelayanan']) // EXCLUDE Dokter Jaga
                    ->where('tanggal_jaga', '>=', now()->subDays(1)) // Include yesterday for overnight shifts
                    ->with(['shiftTemplate'])
                    ->orderBy('tanggal_jaga')
                    ->orderBy('shift_template_id')
                    ->take(10)
                    ->get()
                    ->map(function ($jadwal) {
                        // Handle missing shiftTemplate with fallbacks
                        if ($jadwal->shiftTemplate) {
                            // Determine shift type based on time
                            $jamMasuk = \Carbon\Carbon::parse($jadwal->shiftTemplate->jam_masuk)->hour;
                            $jenis = match(true) {
                                $jamMasuk >= 6 && $jamMasuk < 14 => 'pagi',
                                $jamMasuk >= 14 && $jamMasuk < 22 => 'siang', 
                                default => 'malam'
                            };
                            $waktu = ($jadwal->shiftTemplate->jam_masuk_format ?? '08:00') . ' - ' . ($jadwal->shiftTemplate->jam_pulang_format ?? '16:00');
                            $shiftNama = $jadwal->shiftTemplate->nama_shift ?? 'Shift';
                        } else {
                            // Fallback when no shiftTemplate
                            $jenis = 'pagi'; // Default fallback
                            $waktu = '08:00 - 16:00'; // Default fallback
                            $shiftNama = 'Shift Regular';
                        }
                        
                        // Use dynamic location from database or default to unit kerja  
                        $lokasi = $jadwal->unit_kerja ?? 'Pelayanan'; // Use unit_kerja from database
                        
                        return [
                            'id' => (string) $jadwal->id,
                            'tanggal' => $jadwal->tanggal_jaga->format('Y-m-d'),
                            'waktu' => $waktu,
                            'lokasi' => $lokasi,
                            'jenis' => $jenis,
                            'status' => $jadwal->tanggal_jaga->isPast() ? 'completed' : 'scheduled',
                            'shift_nama' => $shiftNama,
                            'status_jaga' => $jadwal->status_jaga ?? 'scheduled',
                            'keterangan' => $jadwal->keterangan ?? ''
                        ];
                    });
                
                return response()->json($schedules);
            } catch (\Exception $e) {
                // Return empty array if there's any error
                \Log::error('Paramedis schedules API error: ' . $e->getMessage());
                return response()->json([]);
            }
        })->name('api.schedules')->middleware('throttle:1000,1');
        
        // IGD Schedules API with dynamic unit kerja filtering
        Route::get('/api/igd-schedules', function (Request $request) {
            try {
                $user = auth()->user();
                
                // SECURITY: Ensure user has paramedis role
                if (!$user->hasRole('paramedis')) {
                    \Log::warning('Non-paramedis user attempted to access IGD schedules API', [
                        'user_id' => $user->id,
                        'user_name' => $user->name
                    ]);
                    return response()->json(['success' => false, 'message' => 'Access denied'], 403);
                }
                
                $controller = new \App\Http\Controllers\Api\V2\Dashboards\ParamedisDashboardController();
                return $controller->getIgdSchedules($request);
            } catch (\Exception $e) {
                \Log::error('Paramedis IGD schedules API error: ' . $e->getMessage());
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal memuat jadwal IGD',
                    'data' => [
                        'schedules' => [],
                        'total_count' => 0
                    ]
                ], 500);
            }
        })->name('api.igd.schedules')->middleware('throttle:1000,1');
        
        // Weekly Schedule API with dynamic data
        Route::get('/api/weekly-schedule', function (Request $request) {
            try {
                $user = auth()->user();
                
                // SECURITY: Ensure user has paramedis role
                if (!$user->hasRole('paramedis')) {
                    \Log::warning('Non-paramedis user attempted to access weekly schedules API', [
                        'user_id' => $user->id,
                        'user_name' => $user->name
                    ]);
                    return response()->json(['success' => false, 'message' => 'Access denied'], 403);
                }
                
                $controller = new \App\Http\Controllers\Api\V2\Dashboards\ParamedisDashboardController();
                return $controller->getWeeklySchedule($request);
            } catch (\Exception $e) {
                \Log::error('Paramedis weekly schedules API error: ' . $e->getMessage());
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal memuat jadwal minggu ini',
                    'data' => [
                        'schedules' => [],
                        'total_count' => 0
                    ]
                ], 500);
            }
        })->name('api.weekly.schedules')->middleware('throttle:1000,1');
        
        // Legacy routes - redirect to new mobile app
        Route::get('/dashboard', function () {
            return redirect()->route('paramedis.mobile-app');
        })->name('dashboard');
        Route::get('/presensi', function () {
            return redirect()->route('paramedis.mobile-app');
        })->name('presensi');
        Route::get('/jaspel', function () {
            return redirect()->route('paramedis.mobile-app');
        })->name('jaspel');
        Route::get('/tindakan', function () {
            return redirect()->route('paramedis.mobile-app');
        })->name('tindakan');
        Route::get('/jadwal-jaga', function () {
            return redirect()->route('paramedis.mobile-app');
        })->name('jadwal-jaga');
        
        // Removed fallback route to prevent infinite redirects
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
        if ($user?->hasRole('paramedis')) {
            return redirect('/paramedis');
        }
        
        return view('premium-paramedis-dashboard-simple');
    })->middleware('auth')->name('premium.dashboard');
    
    // Direct test route for premium dashboard
    Route::get('/premium-test', function () {
        return view('premium-paramedis-dashboard-simple');
    })->middleware('auth');
});

// DEPRECATED: Legacy Admin routes - Use modern Filament admin panel at /admin instead
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
            'has_dokter_role' => $user?->hasRole('dokter') ?? false,
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
