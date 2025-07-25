<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Paramedis\AttendanceController;
use App\Models\WorkLocation;
use App\Http\Controllers\Auth\UnifiedAuthController;
use App\Http\Controllers\Api\DokterStatsController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->group(function () {
    // User info
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Bendahara API - Financial Management & Reporting
    Route::prefix('bendahara')->middleware('role:bendahara|admin')->group(function () {
        Route::get('/dashboard-stats', [\App\Http\Controllers\Api\BendaharaController::class, 'getDashboardStats']);
        Route::get('/financial-overview', [\App\Http\Controllers\Api\BendaharaController::class, 'getFinancialOverview']);
        Route::post('/generate-report', [\App\Http\Controllers\Api\BendaharaController::class, 'generateReport']);
        Route::get('/validation-queue', [\App\Http\Controllers\Api\BendaharaController::class, 'getValidationQueue']);
        Route::post('/bulk-validation', [\App\Http\Controllers\Api\BendaharaController::class, 'bulkValidation']);
        Route::get('/cash-flow-analysis', [\App\Http\Controllers\Api\BendaharaController::class, 'getCashFlowAnalysis']);
        Route::get('/budget-tracking', [\App\Http\Controllers\Api\BendaharaController::class, 'getBudgetTracking']);
        Route::post('/clear-cache', [\App\Http\Controllers\Api\BendaharaController::class, 'clearCache']);
        Route::get('/health-check', [\App\Http\Controllers\Api\BendaharaController::class, 'healthCheck']);
    });

    // NEW FIXED MOBILE DASHBOARD API
    Route::prefix('mobile-dashboard')->group(function () {
        Route::get('/jaspel-summary', [\App\Http\Controllers\Api\V2\MobileDashboardController::class, 'getJaspelSummary']);
    });

    // NEW CLEAN PARAMEDIS DASHBOARD
    Route::prefix('new-paramedis')->middleware('role:paramedis')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\Api\NewParamedisDashboardController::class, 'getDashboardData']);
    });

    // Bulk Operations API
    Route::prefix('bulk')->group(function () {
        Route::post('/create', [\App\Http\Controllers\Api\V2\BulkOperationController::class, 'bulkCreate']);
        Route::put('/update', [\App\Http\Controllers\Api\V2\BulkOperationController::class, 'bulkUpdate']);
        Route::delete('/delete', [\App\Http\Controllers\Api\V2\BulkOperationController::class, 'bulkDelete']);
        Route::post('/validate', [\App\Http\Controllers\Api\V2\BulkOperationController::class, 'bulkValidate']);
        Route::post('/import', [\App\Http\Controllers\Api\V2\BulkOperationController::class, 'bulkImport']);
        Route::get('/stats', [\App\Http\Controllers\Api\V2\BulkOperationController::class, 'getStats']);
        Route::get('/supported-models', [\App\Http\Controllers\Api\V2\BulkOperationController::class, 'getSupportedModels']);
    });

    // GPS Attendance Routes with Device Binding
    Route::prefix('attendance')->middleware('device.binding')->group(function () {
        Route::post('/checkin', [AttendanceController::class, 'checkin']);
        Route::post('/checkout', [AttendanceController::class, 'checkout']);
        Route::get('/history', [AttendanceController::class, 'index']);
        Route::get('/today', [AttendanceController::class, 'today']);
    });

    // Face Recognition Routes
    Route::prefix('face-recognition')->group(function () {
        Route::post('/register', [\App\Http\Controllers\Paramedis\FaceRecognitionController::class, 'register']);
        Route::post('/verify', [\App\Http\Controllers\Paramedis\FaceRecognitionController::class, 'verify']);
        Route::get('/status', [\App\Http\Controllers\Paramedis\FaceRecognitionController::class, 'status']);
        Route::put('/update', [\App\Http\Controllers\Paramedis\FaceRecognitionController::class, 'update']);
    });

    // Paramedis Attendance Routes (Role-specific)
    Route::prefix('paramedis')->middleware(['web', 'auth'])->group(function () {
        Route::post('/attendance/checkin', [AttendanceController::class, 'checkin']);
        Route::post('/attendance/checkout', [AttendanceController::class, 'checkout']);
        Route::post('/attendance/quick-checkin', [AttendanceController::class, 'quickCheckin']);
        Route::post('/attendance/quick-checkout', [AttendanceController::class, 'quickCheckout']);
        Route::get('/attendance/history', [AttendanceController::class, 'index']);
        Route::get('/attendance/today', [AttendanceController::class, 'today']);
        Route::get('/attendance/status', function (Request $request) {
            $today = \Carbon\Carbon::today();
            $attendance = \App\Models\Attendance::where('user_id', $request->user()->id)
                ->where('date', $today)
                ->first();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'has_checked_in' => $attendance ? true : false,
                    'has_checked_out' => $attendance && $attendance->time_out ? true : false,
                    'can_check_in' => !$attendance,
                    'can_check_out' => $attendance && !$attendance->time_out,
                    'attendance' => $attendance ? [
                        'id' => $attendance->id,
                        'date' => $attendance->date->format('Y-m-d'),
                        'time_in' => $attendance->time_in,
                        'time_out' => $attendance->time_out,
                        'status' => $attendance->status,
                        'work_duration' => $attendance->formatted_work_duration,
                    ] : null
                ]
            ]);
        });
        
        // Mobile Dashboard Routes - WORLD-CLASS dynamic data implementation
        Route::get('/dashboard', function() {
            $user = auth()->user();
            if (!$user) {
                return response()->json(['error' => 'Not authenticated'], 401);
            }
            
            // Get paramedis data
            $paramedis = \App\Models\Pegawai::where('user_id', $user->id)
                ->where('jenis_pegawai', 'Paramedis')
                ->first();
            
            if (!$paramedis) {
                return response()->json(['error' => 'Paramedis data not found'], 404);
            }
            
            // Calculate dynamic Jaspel data from validated Tindakan
            $today = \Carbon\Carbon::today();
            $thisMonth = \Carbon\Carbon::now()->startOfMonth();
            $thisWeek = \Carbon\Carbon::now()->startOfWeek();
            
            // Monthly Jaspel from Jaspel model (consistent with Jaspel page)
            $jaspelMonthly = \App\Models\Jaspel::where('user_id', $user->id)
                ->whereMonth('tanggal', $thisMonth->month)
                ->whereYear('tanggal', $thisMonth->year)
                ->whereIn('status_validasi', ['disetujui', 'approved'])
                ->sum('nominal');
            
            // Weekly Jaspel from Jaspel model (consistent calculation)
            $jaspelWeekly = \App\Models\Jaspel::where('user_id', $user->id)
                ->where('tanggal', '>=', $thisWeek)
                ->whereIn('status_validasi', ['disetujui', 'approved'])
                ->sum('nominal');
            
            // Approved vs Pending breakdown using Jaspel model
            $approvedJaspel = \App\Models\Jaspel::where('user_id', $user->id)
                ->whereMonth('tanggal', $thisMonth->month)
                ->whereYear('tanggal', $thisMonth->year)
                ->whereIn('status_validasi', ['disetujui', 'approved'])
                ->sum('nominal');
                
            // WORLD-CLASS: Calculate comprehensive pending Jaspel from multiple sources
            // 1. Existing Jaspel records with pending status
            $pendingJaspelRecords = \App\Models\Jaspel::where('user_id', $user->id)
                ->whereMonth('tanggal', $thisMonth->month)
                ->whereYear('tanggal', $thisMonth->year)
                ->where('status_validasi', 'pending')
                ->sum('nominal');
                
            // 2. Approved Tindakan that haven't generated Jaspel records yet (paramedis portion)
            $pendingFromTindakan = \App\Models\Tindakan::where('paramedis_id', $paramedis->id)
                ->whereMonth('tanggal_tindakan', $thisMonth->month)
                ->whereYear('tanggal_tindakan', $thisMonth->year)
                ->whereIn('status_validasi', ['approved', 'disetujui'])
                ->whereDoesntHave('jaspel', function($query) use ($user) {
                    $query->where('user_id', $user->id)
                          ->where('jenis_jaspel', 'paramedis');
                })
                ->where('jasa_paramedis', '>', 0)
                ->sum('jasa_paramedis');
                
            // Total pending = existing pending Jaspel + paramedis portion of approved Tindakan without Jaspel
            $pendingJaspel = $pendingJaspelRecords + ($pendingFromTindakan * 0.15); // 15% paramedis calculation
            
            // Shifts and attendance
            $shiftsThisMonth = \App\Models\JadwalJaga::where('pegawai_id', $user->id)
                ->whereMonth('tanggal_jaga', \Carbon\Carbon::now()->month)
                ->count();
                
            $todayAttendance = \App\Models\Attendance::where('user_id', $user->id)
                ->whereDate('date', $today)
                ->first();
            
            // CRITICAL LOGGING: Track what the old route is actually returning
            \Log::critical('🚨 OLD ROUTE RESPONSE', [
                'route' => '/api/paramedis/dashboard',
                'user_id' => $user->id,
                'user_name' => $user->name,
                'jaspel_monthly' => $jaspelMonthly,
                'jaspel_weekly' => $jaspelWeekly,
                'approved_jaspel' => $approvedJaspel,
                'pending_jaspel' => $pendingJaspel,
                'timestamp' => now()->toISOString()
            ]);
            
            return response()->json([
                'jaspel_monthly' => $jaspelMonthly,
                'jaspel_weekly' => $jaspelWeekly, 
                'approved_jaspel' => $approvedJaspel,
                'pending_jaspel' => $pendingJaspel,
                'minutes_worked' => $todayAttendance ? $todayAttendance->work_duration_minutes ?? 0 : 0,
                'shifts_this_month' => $shiftsThisMonth,
                'paramedis_name' => $user->name,
                'paramedis_specialty' => $paramedis->spesialisasi ?? 'Paramedis',
                'today_attendance' => $todayAttendance ? [
                    'check_in' => $todayAttendance->time_in?->format('H:i'),
                    'check_out' => $todayAttendance->time_out?->format('H:i'),
                    'status' => $todayAttendance->time_out ? 'checked_out' : 'checked_in'
                ] : null,
                'recent_jaspel' => \App\Models\Jaspel::where('user_id', $user->id)
                    ->whereIn('status_validasi', ['disetujui', 'approved'])
                    ->with(['tindakan.pasien:id,nama_pasien'])
                    ->orderByDesc('tanggal')
                    ->limit(5)
                    ->get()
                    ->map(function($jaspel) {
                        $tindakan = $jaspel->tindakan;
                        return [
                            'id' => $jaspel->id,
                            'tanggal' => $jaspel->tanggal->format('Y-m-d'),
                            'nominal' => $jaspel->nominal,
                            'status_validasi' => $jaspel->status_validasi,
                            'jenis_tindakan' => $jaspel->jenis_jaspel,
                            'pasien' => $tindakan && $tindakan->pasien ? $tindakan->pasien->nama_pasien : 'Unknown'
                        ];
                    })
            ]);
        });
        Route::get('/schedule', [\App\Http\Controllers\Paramedis\ParamedisDashboardController::class, 'schedule']);
        Route::get('/performance', [\App\Http\Controllers\Paramedis\ParamedisDashboardController::class, 'performance']);
        Route::get('/notifications', [\App\Http\Controllers\Paramedis\ParamedisDashboardController::class, 'notifications']);
        Route::put('/notifications/{id}/read', [\App\Http\Controllers\Paramedis\ParamedisDashboardController::class, 'markNotificationRead']);
    });
});

// 🎯 World-Class Work Location Assignment API
Route::prefix('admin')->middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::prefix('work-location-assignments')->group(function () {
        Route::get('/dashboard', [App\Http\Controllers\Admin\WorkLocationAssignmentController::class, 'getDashboardData']);
        Route::get('/recommendations/{user}', [App\Http\Controllers\Admin\WorkLocationAssignmentController::class, 'getRecommendations']);
        Route::post('/smart-assignment', [App\Http\Controllers\Admin\WorkLocationAssignmentController::class, 'smartAssignment']);
        Route::post('/bulk-smart-assignment', [App\Http\Controllers\Admin\WorkLocationAssignmentController::class, 'bulkSmartAssignment']);
        Route::post('/manual-assignment', [App\Http\Controllers\Admin\WorkLocationAssignmentController::class, 'manualAssignment']);
        Route::get('/history', [App\Http\Controllers\Admin\WorkLocationAssignmentController::class, 'getAssignmentHistory']);
        Route::delete('/remove-assignment', [App\Http\Controllers\Admin\WorkLocationAssignmentController::class, 'removeAssignment']);
    });
});

// Public WorkLocation endpoint for attendance systems
Route::get('/work-locations/active', function () {
    $locations = WorkLocation::active()->get(['id', 'name', 'latitude', 'longitude', 'radius_meters', 'location_type', 'address']);
    
    return response()->json($locations);
});

// Non-Paramedis endpoints - REMOVED FOR REBUILD

/*
|--------------------------------------------------------------------------
| API v2 Routes
|--------------------------------------------------------------------------
|
| Enhanced API routes with versioning, standardized responses, and 
| comprehensive mobile support for all user roles.
|
*/

Route::prefix('v2')->group(function () {
    // Public routes (no authentication required)
    Route::prefix('auth')->group(function () {
        Route::post('/login', [App\Http\Controllers\Api\V2\Auth\AuthController::class, 'login']);
        Route::post('/refresh', [App\Http\Controllers\Api\V2\Auth\AuthController::class, 'refresh']);
        // TODO: Add forgot password, reset password routes
    });

    // Work locations (public for GPS validation)
    Route::get('/locations/work-locations', function () {
        $locations = WorkLocation::active()->get(['id', 'name', 'latitude', 'longitude', 'radius_meters', 'location_type', 'address']);
        return response()->json([
            'success' => true,
            'message' => 'Work locations retrieved',
            'data' => $locations,
            'meta' => [
                'version' => '2.0',
                'timestamp' => now()->toISOString(),
                'request_id' => \Illuminate\Support\Str::uuid()->toString(),
            ]
        ]);
    });

    // System information (public)
    Route::prefix('system')->group(function () {
        Route::get('/health', function () {
            return response()->json([
                'success' => true,
                'message' => 'API is healthy',
                'data' => [
                    'status' => 'ok',
                    'version' => '2.0',
                    'timestamp' => now()->toISOString(),
                    'database' => 'connected',
                ],
                'meta' => [
                    'version' => '2.0',
                    'timestamp' => now()->toISOString(),
                    'request_id' => \Illuminate\Support\Str::uuid()->toString(),
                ]
            ]);
        });

        Route::get('/version', function () {
            return response()->json([
                'success' => true,
                'message' => 'API version information',
                'data' => [
                    'api_version' => '2.0',
                    'laravel_version' => app()->version(),
                    'release_date' => '2025-07-15',
                    'features' => [
                        'authentication' => '✓',
                        'attendance' => '✓',
                        'dashboards' => '✓',
                        'role_based_access' => '✓',
                        'mobile_optimization' => '✓',
                        'offline_sync' => 'pending',
                        'push_notifications' => 'pending',
                    ],
                ],
                'meta' => [
                    'version' => '2.0',
                    'timestamp' => now()->toISOString(),
                    'request_id' => \Illuminate\Support\Str::uuid()->toString(),
                ]
            ]);
        });
    });

    // Protected routes (authentication required)
    Route::middleware(['auth:sanctum', App\Http\Middleware\Api\ApiResponseHeadersMiddleware::class])->group(function () {
        
        // Authentication endpoints
        Route::prefix('auth')->group(function () {
            Route::post('/logout', [App\Http\Controllers\Api\V2\Auth\AuthController::class, 'logout']);
            Route::post('/logout-all', [App\Http\Controllers\Api\V2\Auth\AuthController::class, 'logoutAll']);
            Route::get('/me', [App\Http\Controllers\Api\V2\Auth\AuthController::class, 'me']);
            Route::put('/profile', [App\Http\Controllers\Api\V2\Auth\AuthController::class, 'updateProfile']);
            Route::post('/change-password', [App\Http\Controllers\Api\V2\Auth\AuthController::class, 'changePassword']);
            
            // Enhanced authentication features
            Route::get('/sessions', [App\Http\Controllers\Api\V2\Auth\AuthController::class, 'getSessions']);
            Route::delete('/sessions/{session_id}', [App\Http\Controllers\Api\V2\Auth\AuthController::class, 'endSession']);
            
            // Biometric authentication
            Route::prefix('biometric')->group(function () {
                Route::post('/setup', [App\Http\Controllers\Api\V2\Auth\AuthController::class, 'setupBiometric']);
                Route::post('/verify', [App\Http\Controllers\Api\V2\Auth\AuthController::class, 'verifyBiometric']);
                Route::get('/', [App\Http\Controllers\Api\V2\Auth\AuthController::class, 'getBiometrics']);
                Route::delete('/{biometric_type}', [App\Http\Controllers\Api\V2\Auth\AuthController::class, 'removeBiometric']);
            });
        });

        // Device management endpoints
        Route::prefix('devices')->group(function () {
            Route::get('/', [App\Http\Controllers\Api\V2\Auth\AuthController::class, 'getDevices']);
            Route::post('/register', [App\Http\Controllers\Api\V2\Auth\AuthController::class, 'registerDevice']);
            Route::delete('/{device_id}', [App\Http\Controllers\Api\V2\Auth\AuthController::class, 'revokeDevice']);
        });

        // Attendance endpoints with rate limiting
        Route::prefix('attendance')->middleware([App\Http\Middleware\Api\ApiRateLimitMiddleware::class . ':attendance'])->group(function () {
            Route::post('/checkin', [App\Http\Controllers\Api\V2\Attendance\AttendanceController::class, 'checkin']);
            Route::post('/checkout', [App\Http\Controllers\Api\V2\Attendance\AttendanceController::class, 'checkout']);
            Route::get('/today', [App\Http\Controllers\Api\V2\Attendance\AttendanceController::class, 'today']);
            Route::get('/history', [App\Http\Controllers\Api\V2\Attendance\AttendanceController::class, 'history']);
            Route::get('/statistics', [App\Http\Controllers\Api\V2\Attendance\AttendanceController::class, 'statistics']);
        });

        // Jaspel endpoints
        Route::prefix('jaspel')->group(function () {
            // API endpoints requiring token authentication
            Route::middleware(['auth:sanctum'])->group(function () {
                Route::get('/summary', [App\Http\Controllers\Api\V2\Jaspel\JaspelController::class, 'summary']);
                Route::get('/history', [App\Http\Controllers\Api\V2\Jaspel\JaspelController::class, 'history']);
                Route::get('/monthly-report/{year}/{month}', [App\Http\Controllers\Api\V2\Jaspel\JaspelController::class, 'monthlyReport']);
                Route::get('/yearly-summary/{year}', [App\Http\Controllers\Api\V2\Jaspel\JaspelController::class, 'yearlySummary']);
                
                // Admin only endpoint
                Route::post('/calculate-from-tindakan', [App\Http\Controllers\Api\V2\Jaspel\JaspelController::class, 'calculateFromTindakan'])
                    ->middleware(['role:admin,bendahara']);
            });
            
            // DIAGNOSTIC: Catch misdirected mobile-data requests
            Route::get('/mobile-data', function () {
                return response()->json([
                    'success' => false,
                    'error' => 'ROUTING_DIAGNOSTIC',
                    'message' => 'This request was made to /api/v2/jaspel/mobile-data but should go to /paramedis/api/v2/jaspel/mobile-data',
                    'correct_endpoints' => [
                        '/paramedis/api/v2/jaspel/mobile-data',
                        '/api/v2/jaspel/mobile-data-alt'
                    ],
                    'debug_info' => [
                        'requested_path' => request()->path(),
                        'full_url' => request()->fullUrl(),
                        'user_agent' => request()->userAgent(),
                        'referer' => request()->header('referer'),
                        'auth_user' => auth()->id()
                    ]
                ], 404);
            })->middleware(['throttle:60,1']);
            
            // Mobile app endpoint moved to web routes for session compatibility
        });

        // Dashboard endpoints with rate limiting
        Route::prefix('dashboards')->group(function () {
            // Paramedis dashboard
            Route::middleware(['role:paramedis'])->prefix('paramedis')->group(function () {
                Route::get('/', [App\Http\Controllers\Api\V2\Dashboards\ParamedisDashboardController::class, 'index']);
                Route::get('/jaspel', [App\Http\Controllers\Api\V2\Dashboards\ParamedisDashboardController::class, 'getJaspel']);
                Route::get('/jadwal-jaga', [App\Http\Controllers\Api\V2\Dashboards\ParamedisDashboardController::class, 'getJadwalJaga']);
                Route::get('/tindakan', [App\Http\Controllers\Api\V2\Dashboards\ParamedisDashboardController::class, 'getTindakan']);
                Route::get('/presensi', [App\Http\Controllers\Api\V2\Dashboards\ParamedisDashboardController::class, 'getPresensi']);
                Route::get('/attendance/status', [App\Http\Controllers\Api\V2\Dashboards\ParamedisDashboardController::class, 'getAttendanceStatus']);
                Route::get('/schedules', [App\Http\Controllers\Api\V2\Dashboards\ParamedisDashboardController::class, 'schedules']);
                Route::post('/checkin', [App\Http\Controllers\Api\V2\Dashboards\ParamedisDashboardController::class, 'checkIn']);
                Route::post('/checkout', [App\Http\Controllers\Api\V2\Dashboards\ParamedisDashboardController::class, 'checkOut']);
                Route::post('/refresh-work-location', [App\Http\Controllers\Api\V2\Dashboards\ParamedisDashboardController::class, 'refreshWorkLocation']);
                Route::get('/work-location/status', [App\Http\Controllers\Api\V2\Dashboards\ParamedisDashboardController::class, 'getWorkLocationStatus']);
                Route::post('/work-location/check-and-assign', [App\Http\Controllers\Api\V2\Dashboards\ParamedisDashboardController::class, 'checkAndAssignWorkLocation']);
                
                // DI Paramedis (Daftar Isian) endpoints
                Route::prefix('di-paramedis')->group(function () {
                    Route::get('/', [App\Http\Controllers\Api\V2\DiParamedisController::class, 'index']);
                    Route::get('/summary', [App\Http\Controllers\Api\V2\DiParamedisController::class, 'summary']);
                    Route::get('/{id}', [App\Http\Controllers\Api\V2\DiParamedisController::class, 'show']);
                    Route::post('/', [App\Http\Controllers\Api\V2\DiParamedisController::class, 'store']);
                    Route::put('/{id}', [App\Http\Controllers\Api\V2\DiParamedisController::class, 'update']);
                    Route::post('/{id}/submit', [App\Http\Controllers\Api\V2\DiParamedisController::class, 'submit']);
                    Route::post('/{id}/tindakan', [App\Http\Controllers\Api\V2\DiParamedisController::class, 'addTindakanMedis']);
                    Route::post('/{id}/obat', [App\Http\Controllers\Api\V2\DiParamedisController::class, 'addObat']);
                    Route::post('/{id}/signature', [App\Http\Controllers\Api\V2\DiParamedisController::class, 'uploadSignature']);
                });
            });

            // Dokter dashboard - Mobile app API endpoints
            Route::prefix('dokter')->middleware(['enhanced.role:dokter'])->group(function () {
                // Test endpoint for authentication verification
                Route::get('/test', function () {
                    $user = auth()->user();
                    return response()->json([
                        'success' => true,
                        'message' => 'Dokter API endpoint is working - Authentication verified',
                        'data' => [
                            'timestamp' => now()->toISOString(),
                            'user' => [
                                'id' => $user->id,
                                'name' => $user->name,
                                'role' => $user->role?->name,
                                'authenticated' => true,
                                'role_validated' => true,
                            ],
                            'session' => [
                                'token_name' => $user->currentAccessToken()?->name,
                                'ip_address' => request()->ip(),
                                'user_agent' => request()->userAgent(),
                            ],
                        ],
                        'meta' => [
                            'version' => '2.0',
                            'timestamp' => now()->toISOString(),
                            'request_id' => \Illuminate\Support\Str::uuid()->toString(),
                        ]
                    ]);
                });
                
                // Dashboard endpoints - Real API dengan DokterDashboardController
                Route::get('/', [App\Http\Controllers\Api\V2\Dashboards\DokterDashboardController::class, 'index']);
                Route::get('/jadwal-jaga', [App\Http\Controllers\Api\V2\Dashboards\DokterDashboardController::class, 'getJadwalJaga']);
                Route::get('/jaspel', [App\Http\Controllers\Api\V2\Dashboards\DokterDashboardController::class, 'getJaspel']);
                Route::get('/tindakan', [App\Http\Controllers\Api\V2\Dashboards\DokterDashboardController::class, 'getTindakan']);
                Route::get('/presensi', [App\Http\Controllers\Api\V2\Dashboards\DokterDashboardController::class, 'getPresensi']);
                Route::get('/attendance', [App\Http\Controllers\Api\V2\Dashboards\DokterDashboardController::class, 'getAttendance']);
                Route::get('/patients', [App\Http\Controllers\Api\V2\Dashboards\DokterDashboardController::class, 'getPatients']);
                Route::get('/test', [App\Http\Controllers\Api\V2\Dashboards\DokterDashboardController::class, 'test']);
                
                // Schedule endpoints
                Route::get('/schedules', [App\Http\Controllers\Api\V2\Dashboards\DokterDashboardController::class, 'schedules']);
                Route::get('/weekly-schedules', [App\Http\Controllers\Api\V2\Dashboards\DokterDashboardController::class, 'getWeeklySchedule']);
                Route::get('/igd-schedules', [App\Http\Controllers\Api\V2\Dashboards\DokterDashboardController::class, 'getIgdSchedules']);
                
                // Work location endpoints
                Route::post('/refresh-work-location', [App\Http\Controllers\Api\V2\Dashboards\DokterDashboardController::class, 'refreshWorkLocation']);
                Route::get('/work-location/status', [App\Http\Controllers\Api\V2\Dashboards\DokterDashboardController::class, 'getWorkLocationStatus']);
                Route::post('/work-location/check-and-assign', [App\Http\Controllers\Api\V2\Dashboards\DokterDashboardController::class, 'checkAndAssignWorkLocation']);
                
                // Attendance endpoints
                Route::get('/attendance/status', function () {
                    $user = auth()->user();
                    $today = \Carbon\Carbon::today();
                    $attendance = \App\Models\Attendance::where('user_id', $user->id)
                        ->where('date', $today)
                        ->first();
                    
                    return response()->json([
                        'success' => true,
                        'message' => 'Attendance status retrieved',
                        'data' => [
                            'status' => $attendance ? 
                                ($attendance->time_out ? 'checked_out' : 'checked_in') : 
                                'not_checked_in',
                            'check_in_time' => $attendance?->time_in?->format('H:i'),
                            'check_out_time' => $attendance?->time_out?->format('H:i'),
                            'work_duration' => $attendance?->formatted_work_duration,
                        ],
                        'meta' => [
                            'version' => '2.0',
                            'timestamp' => now()->toISOString(),
                            'request_id' => \Illuminate\Support\Str::uuid()->toString(),
                        ]
                    ]);
                });
                
                Route::get('/attendance/today-history', function () {
                    $user = auth()->user();
                    $today = \Carbon\Carbon::today();
                    $attendance = \App\Models\Attendance::where('user_id', $user->id)
                        ->where('date', $today)
                        ->first();
                    
                    $history = [];
                    if ($attendance) {
                        if ($attendance->time_in) {
                            $history[] = [
                                'time' => $attendance->time_in->format('H:i'),
                                'action' => 'Check In',
                                'subtitle' => 'Masuk kerja'
                            ];
                        }
                        if ($attendance->time_out) {
                            $history[] = [
                                'time' => $attendance->time_out->format('H:i'),
                                'action' => 'Check Out',
                                'subtitle' => 'Selesai kerja'
                            ];
                        }
                    }
                    
                    return response()->json([
                        'success' => true,
                        'message' => 'Today attendance history retrieved',
                        'data' => [
                            'has_activity' => !empty($history),
                            'history' => $history
                        ],
                        'meta' => [
                            'version' => '2.0',
                            'timestamp' => now()->toISOString(),
                            'request_id' => \Illuminate\Support\Str::uuid()->toString(),
                        ]
                    ]);
                });
                
                // Patient endpoints (placeholder)
                Route::get('/patients', function () {
                    return response()->json([
                        'success' => true,
                        'message' => 'Patient data will be available soon',
                        'data' => [
                            'patients_today' => 12,
                            'upcoming_appointments' => []
                        ],
                        'meta' => [
                            'version' => '2.0',
                            'timestamp' => now()->toISOString(),
                            'request_id' => \Illuminate\Support\Str::uuid()->toString(),
                        ]
                    ]);
                });
                
                // Tindakan endpoints (placeholder)
                Route::get('/tindakan', function () {
                    return response()->json([
                        'success' => true,
                        'message' => 'Tindakan data will be available soon',
                        'data' => [
                            'tindakan_today' => 8,
                            'recent_tindakan' => []
                        ],
                        'meta' => [
                            'version' => '2.0',
                            'timestamp' => now()->toISOString(),
                            'request_id' => \Illuminate\Support\Str::uuid()->toString(),
                        ]
                    ]);
                });
                
                // Jaspel endpoints - WORLD-CLASS implementation using DokterDashboardController
                // Route removed - handled by line 319
            });

            // Non-Paramedis dashboard - Enhanced with proper authentication and role validation
            Route::prefix('nonparamedis')->middleware(['enhanced.role:non_paramedis'])->group(function () {
                // Test endpoint for authentication verification
                Route::get('/test', function () {
                    $user = auth()->user();
                    return response()->json([
                        'success' => true,
                        'message' => 'API endpoint is working - Authentication verified',
                        'data' => [
                            'timestamp' => now()->toISOString(),
                            'user' => [
                                'id' => $user->id,
                                'name' => $user->name,
                                'role' => $user->role?->name,
                                'authenticated' => true,
                                'role_validated' => true,
                            ],
                            'session' => [
                                'token_name' => $user->currentAccessToken()?->name,
                                'ip_address' => request()->ip(),
                                'user_agent' => request()->userAgent(),
                            ],
                        ],
                        'meta' => [
                            'version' => '2.0',
                            'timestamp' => now()->toISOString(),
                            'request_id' => \Illuminate\Support\Str::uuid()->toString(),
                        ]
                    ]);
                });
                
                // Dashboard endpoints with enhanced security
                Route::get('/', [App\Http\Controllers\Api\V2\NonParamedisDashboardController::class, 'dashboard']);
                Route::get('/attendance/status', [App\Http\Controllers\Api\V2\NonParamedisDashboardController::class, 'getAttendanceStatus']);
                
                // Attendance actions with rate limiting
                Route::middleware([App\Http\Middleware\Api\ApiRateLimitMiddleware::class . ':attendance'])->group(function () {
                    Route::post('/attendance/checkin', [App\Http\Controllers\Api\V2\NonParamedisDashboardController::class, 'checkIn']);
                    Route::post('/attendance/checkout', [App\Http\Controllers\Api\V2\NonParamedisDashboardController::class, 'checkOut']);
                });
                
                Route::get('/attendance/today-history', [App\Http\Controllers\Api\V2\NonParamedisDashboardController::class, 'getTodayHistory']);
                Route::get('/schedule', [App\Http\Controllers\Api\V2\NonParamedisDashboardController::class, 'getSchedule']);
                Route::get('/reports', [App\Http\Controllers\Api\V2\NonParamedisDashboardController::class, 'getReports']);
                Route::get('/profile', [App\Http\Controllers\Api\V2\NonParamedisDashboardController::class, 'getProfile']);
                
                // Profile management endpoints
                Route::put('/profile/update', [App\Http\Controllers\Api\V2\NonParamedisDashboardController::class, 'updateProfile']);
                Route::put('/profile/password', [App\Http\Controllers\Api\V2\NonParamedisDashboardController::class, 'changePassword']);
                Route::post('/profile/photo', [App\Http\Controllers\Api\V2\NonParamedisDashboardController::class, 'uploadPhoto']);
                
                // Settings endpoints
                Route::get('/settings', [App\Http\Controllers\Api\V2\NonParamedisDashboardController::class, 'getSettings']);
                Route::put('/settings', [App\Http\Controllers\Api\V2\NonParamedisDashboardController::class, 'updateSettings']);
            });

            // TODO: Add other role dashboards (admin, manajer, bendahara, petugas)
        });

        // Admin panel routes
        Route::prefix('admin')->middleware(['enhanced.role:admin'])->group(function () {
            // NonParamedis management
            Route::prefix('nonparamedis')->group(function () {
                Route::get('/', [App\Http\Controllers\Admin\NonParamedisManagementController::class, 'index']);
                Route::post('/', [App\Http\Controllers\Admin\NonParamedisManagementController::class, 'store']);
                Route::get('/dashboard-stats', [App\Http\Controllers\Admin\NonParamedisManagementController::class, 'getDashboardStats']);
                Route::get('/available-shifts', [App\Http\Controllers\Admin\NonParamedisManagementController::class, 'getAvailableShifts']);
                
                Route::prefix('{user}')->group(function () {
                    Route::get('/', [App\Http\Controllers\Admin\NonParamedisManagementController::class, 'show']);
                    Route::put('/', [App\Http\Controllers\Admin\NonParamedisManagementController::class, 'update']);
                    Route::patch('/toggle-status', [App\Http\Controllers\Admin\NonParamedisManagementController::class, 'toggleStatus']);
                    Route::post('/reset-password', [App\Http\Controllers\Admin\NonParamedisManagementController::class, 'resetPassword']);
                    Route::get('/attendance-history', [App\Http\Controllers\Admin\NonParamedisManagementController::class, 'getAttendanceHistory']);
                    Route::get('/schedule', [App\Http\Controllers\Admin\NonParamedisManagementController::class, 'getSchedule']);
                    Route::post('/assign-schedule', [App\Http\Controllers\Admin\NonParamedisManagementController::class, 'assignSchedule']);
                });
            });
            
            // Attendance approval routes
            Route::prefix('attendance-approvals')->group(function () {
                Route::get('/pending', [App\Http\Controllers\Admin\AttendanceApprovalController::class, 'getPendingApprovals']);
                Route::get('/history', [App\Http\Controllers\Admin\AttendanceApprovalController::class, 'getAttendanceHistory']);
                Route::get('/stats', [App\Http\Controllers\Admin\AttendanceApprovalController::class, 'getApprovalStats']);
                Route::post('/bulk-approve', [App\Http\Controllers\Admin\AttendanceApprovalController::class, 'bulkApprove']);
                Route::post('/bulk-reject', [App\Http\Controllers\Admin\AttendanceApprovalController::class, 'bulkReject']);
                
                Route::prefix('{attendance}')->group(function () {
                    Route::post('/approve', [App\Http\Controllers\Admin\AttendanceApprovalController::class, 'approveAttendance']);
                    Route::post('/reject', [App\Http\Controllers\Admin\AttendanceApprovalController::class, 'rejectAttendance']);
                });
            });
            
            // Reporting routes
            Route::prefix('reports')->group(function () {
                Route::get('/attendance-summary', [App\Http\Controllers\Admin\NonParamedisReportController::class, 'getAttendanceSummary']);
                Route::get('/detailed-report', [App\Http\Controllers\Admin\NonParamedisReportController::class, 'getDetailedReport']);
                Route::get('/performance-analytics', [App\Http\Controllers\Admin\NonParamedisReportController::class, 'getPerformanceAnalytics']);
                Route::get('/trend-analysis', [App\Http\Controllers\Admin\NonParamedisReportController::class, 'getTrendAnalysis']);
                Route::post('/export-csv', [App\Http\Controllers\Admin\NonParamedisReportController::class, 'exportAttendanceCSV']);
                Route::get('/download/{filename}', [App\Http\Controllers\Admin\NonParamedisReportController::class, 'downloadExport'])->name('admin.reports.download');
            });
        });

        // Notification endpoints
        Route::prefix('notifications')->group(function () {
            Route::get('/', [App\Http\Controllers\Api\V2\NotificationController::class, 'index']);
            Route::get('/unread-count', [App\Http\Controllers\Api\V2\NotificationController::class, 'getUnreadCount']);
            Route::get('/recent', [App\Http\Controllers\Api\V2\NotificationController::class, 'getRecent']);
            Route::post('/mark-all-read', [App\Http\Controllers\Api\V2\NotificationController::class, 'markAllAsRead']);
            Route::get('/settings', [App\Http\Controllers\Api\V2\NotificationController::class, 'getSettings']);
            Route::put('/settings', [App\Http\Controllers\Api\V2\NotificationController::class, 'updateSettings']);
            
            Route::prefix('{notification}')->group(function () {
                Route::post('/mark-read', [App\Http\Controllers\Api\V2\NotificationController::class, 'markAsRead']);
                Route::delete('/', [App\Http\Controllers\Api\V2\NotificationController::class, 'destroy']);
            });
        });
        
        // Offline support endpoints
        Route::prefix('offline')->group(function () {
            Route::get('/data', [App\Http\Controllers\Api\V2\OfflineController::class, 'getOfflineData']);
            Route::post('/sync-attendance', [App\Http\Controllers\Api\V2\OfflineController::class, 'syncOfflineAttendance']);
            Route::get('/status', [App\Http\Controllers\Api\V2\OfflineController::class, 'getOfflineStatus']);
            Route::get('/device-info', [App\Http\Controllers\Api\V2\OfflineController::class, 'getDeviceInfo']);
            Route::post('/test', [App\Http\Controllers\Api\V2\OfflineController::class, 'testOffline']);
        });
        
        // Face Recognition endpoints
        Route::prefix('face-recognition')->middleware([App\Http\Middleware\Api\ApiRateLimitMiddleware::class . ':face_recognition'])->group(function () {
            Route::post('/register', [\App\Http\Controllers\Paramedis\FaceRecognitionController::class, 'register']);
            Route::post('/verify', [\App\Http\Controllers\Paramedis\FaceRecognitionController::class, 'verify']);
            Route::get('/status', [\App\Http\Controllers\Paramedis\FaceRecognitionController::class, 'status']);
            Route::put('/update', [\App\Http\Controllers\Paramedis\FaceRecognitionController::class, 'update']);
        });

        // TODO: Add more v2 endpoints
        // - User management endpoints
        // - Jaspel endpoints
        // - Schedules endpoints
        // - Patients endpoints
        // - Transactions endpoints
        // - Notifications endpoints
        // - Files endpoints
    });
});

/*
|--------------------------------------------------------------------------
| Enhanced API V1 Routes - Mobile Integration
|--------------------------------------------------------------------------
|
| Enhanced RESTful API routes for mobile integration with the new enhanced
| management system. Includes comprehensive endpoints for patients,
| procedures, financial management, and analytics.
|
*/

// API Health Check
Route::get('/health', function () {
    return response()->json([
        'status' => 'healthy',
        'timestamp' => now()->toISOString(),
        'version' => 'v1',
        'service' => 'Dokterku Enhanced API',
    ]);
})->name('api.health');

// Enhanced V1 API Routes
Route::prefix('v1')->name('api.v1.')->group(function () {
    
    // Auth required routes with rate limiting
    Route::middleware(['auth:sanctum', App\Http\Middleware\ApiRateLimiter::class . ':200:1'])->group(function () {
        
        // User info endpoint
        Route::get('/me', function (Request $request) {
            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                    'role' => $request->user()->role?->name,
                ],
                'timestamp' => now()->toISOString(),
            ]);
        })->name('me');

        // Patient Management API
        Route::prefix('patients')->name('patients.')->group(function () {
            Route::get('/', [App\Http\Controllers\Api\V1\PasienController::class, 'index'])->name('index');
            Route::post('/', [App\Http\Controllers\Api\V1\PasienController::class, 'store'])->name('store');
            Route::get('/search', [App\Http\Controllers\Api\V1\PasienController::class, 'search'])->name('search');
            Route::get('/statistics', [App\Http\Controllers\Api\V1\PasienController::class, 'statistics'])->name('statistics');
            Route::get('/{id}', [App\Http\Controllers\Api\V1\PasienController::class, 'show'])->name('show');
            Route::put('/{id}', [App\Http\Controllers\Api\V1\PasienController::class, 'update'])->name('update');
            Route::delete('/{id}', [App\Http\Controllers\Api\V1\PasienController::class, 'destroy'])->name('destroy');
        });

        // Medical Procedures API
        Route::prefix('procedures')->name('procedures.')->group(function () {
            Route::get('/', [App\Http\Controllers\Api\V1\TindakanController::class, 'index'])->name('index');
            Route::post('/', [App\Http\Controllers\Api\V1\TindakanController::class, 'store'])->name('store');
            Route::get('/statistics', [App\Http\Controllers\Api\V1\TindakanController::class, 'statistics'])->name('statistics');
            Route::get('/{id}', [App\Http\Controllers\Api\V1\TindakanController::class, 'show'])->name('show');
            Route::put('/{id}', [App\Http\Controllers\Api\V1\TindakanController::class, 'update'])->name('update');
            Route::patch('/{id}/status', [App\Http\Controllers\Api\V1\TindakanController::class, 'updateStatus'])->name('update-status');
            Route::get('/patient/{patientId}/timeline', [App\Http\Controllers\Api\V1\TindakanController::class, 'patientTimeline'])->name('patient-timeline');
        });

        // Revenue Management API
        Route::prefix('revenue')->name('revenue.')->group(function () {
            Route::get('/', [App\Http\Controllers\Api\V1\PendapatanController::class, 'index'])->name('index');
            Route::post('/', [App\Http\Controllers\Api\V1\PendapatanController::class, 'store'])->name('store');
            Route::get('/analytics', [App\Http\Controllers\Api\V1\PendapatanController::class, 'analytics'])->name('analytics');
            Route::get('/suggestions', [App\Http\Controllers\Api\V1\PendapatanController::class, 'suggestions'])->name('suggestions');
            Route::post('/bulk-create-from-procedures', [App\Http\Controllers\Api\V1\PendapatanController::class, 'bulkCreateFromTindakan'])->name('bulk-create-from-procedures');
            Route::get('/{id}', [App\Http\Controllers\Api\V1\PendapatanController::class, 'show'])->name('show');
            Route::put('/{id}', [App\Http\Controllers\Api\V1\PendapatanController::class, 'update'])->name('update');
            Route::delete('/{id}', [App\Http\Controllers\Api\V1\PendapatanController::class, 'destroy'])->name('destroy');
        });

        // Expense Management API
        Route::prefix('expenses')->name('expenses.')->group(function () {
            Route::get('/', [App\Http\Controllers\Api\V1\PengeluaranController::class, 'index'])->name('index');
            Route::post('/', [App\Http\Controllers\Api\V1\PengeluaranController::class, 'store'])->name('store');
            Route::get('/budget-analysis', [App\Http\Controllers\Api\V1\PengeluaranController::class, 'budgetAnalysis'])->name('budget-analysis');
            Route::get('/suggestions', [App\Http\Controllers\Api\V1\PengeluaranController::class, 'suggestions'])->name('suggestions');
            Route::post('/bulk-update-status', [App\Http\Controllers\Api\V1\PengeluaranController::class, 'bulkUpdateStatus'])->name('bulk-update-status');
            Route::get('/{id}', [App\Http\Controllers\Api\V1\PengeluaranController::class, 'show'])->name('show');
            Route::put('/{id}', [App\Http\Controllers\Api\V1\PengeluaranController::class, 'update'])->name('update');
            Route::delete('/{id}', [App\Http\Controllers\Api\V1\PengeluaranController::class, 'destroy'])->name('destroy');
        });

        // Analytics & Reporting API
        Route::prefix('analytics')->name('analytics.')->group(function () {
            Route::get('/dashboard', [App\Http\Controllers\Api\V1\AnalyticsController::class, 'dashboard'])->name('dashboard');
            Route::get('/patients', [App\Http\Controllers\Api\V1\AnalyticsController::class, 'patients'])->name('patients');
            Route::get('/financial', [App\Http\Controllers\Api\V1\AnalyticsController::class, 'financial'])->name('financial');
            Route::get('/procedures', [App\Http\Controllers\Api\V1\AnalyticsController::class, 'procedures'])->name('procedures');
            Route::get('/trends', [App\Http\Controllers\Api\V1\AnalyticsController::class, 'trends'])->name('trends');
            Route::get('/comparative', [App\Http\Controllers\Api\V1\AnalyticsController::class, 'comparative'])->name('comparative');
            Route::get('/performance', [App\Http\Controllers\Api\V1\AnalyticsController::class, 'performance'])->name('performance');
            Route::post('/custom-report', [App\Http\Controllers\Api\V1\AnalyticsController::class, 'customReport'])->name('custom-report');
        });

        // ML Insights and Predictive Analytics API
        Route::prefix('ml-insights')->name('ml-insights.')->group(function () {
            Route::get('/patient-flow-prediction', [App\Http\Controllers\Api\V1\MLInsightsController::class, 'patientFlowPrediction'])->name('patient-flow-prediction');
            Route::get('/revenue-forecast', [App\Http\Controllers\Api\V1\MLInsightsController::class, 'revenueForecast'])->name('revenue-forecast');
            Route::get('/disease-patterns', [App\Http\Controllers\Api\V1\MLInsightsController::class, 'diseasePatterns'])->name('disease-patterns');
            Route::get('/resource-optimization', [App\Http\Controllers\Api\V1\MLInsightsController::class, 'resourceOptimization'])->name('resource-optimization');
            Route::get('/dashboard', [App\Http\Controllers\Api\V1\MLInsightsController::class, 'dashboard'])->name('dashboard');
            Route::get('/quick-summary', [App\Http\Controllers\Api\V1\MLInsightsController::class, 'quickSummary'])->name('quick-summary');
            Route::get('/predictive-alerts', [App\Http\Controllers\Api\V1\MLInsightsController::class, 'predictiveAlerts'])->name('predictive-alerts');
        });

        // Mobile App Specific Endpoints
        Route::prefix('mobile')->name('mobile.')->group(function () {
            
            // Quick access endpoints for mobile
            Route::get('/dashboard-summary', function (Request $request) {
                $today = now()->startOfDay();
                $thisMonth = now()->startOfMonth();

                return response()->json([
                    'success' => true,
                    'data' => [
                        'today' => [
                            'new_patients' => \App\Models\Pasien::whereDate('created_at', $today)->count(),
                            'procedures' => \App\Models\Tindakan::whereDate('tanggal_tindakan', $today)->count(),
                            'revenue' => \App\Models\Pendapatan::whereDate('tanggal_pendapatan', $today)->sum('jumlah'),
                        ],
                        'month' => [
                            'new_patients' => \App\Models\Pasien::where('created_at', '>=', $thisMonth)->count(),
                            'procedures' => \App\Models\Tindakan::where('tanggal_tindakan', '>=', $thisMonth)->count(),
                            'revenue' => \App\Models\Pendapatan::where('tanggal_pendapatan', '>=', $thisMonth)->sum('jumlah'),
                        ],
                        'pending_approvals' => \App\Models\Tindakan::where('status_validasi', 'pending')->count(),
                    ],
                    'timestamp' => now()->toISOString(),
                ]);
            })->name('dashboard-summary');

            // Recent activity for mobile
            Route::get('/recent-activity', function (Request $request) {
                $limit = min($request->get('limit', 10), 50);
                
                $activities = \App\Models\Tindakan::with(['pasien:id,nama_pasien,nomor_pasien', 'jenisTindakan:id,nama_tindakan'])
                    ->orderByDesc('created_at')
                    ->limit($limit)
                    ->get()
                    ->map(function ($tindakan) {
                        return [
                            'id' => $tindakan->id,
                            'type' => 'procedure',
                            'title' => $tindakan->jenisTindakan?->nama_tindakan ?? 'Medical Procedure',
                            'subtitle' => $tindakan->pasien?->nama_pasien ?? 'Unknown Patient',
                            'status' => $tindakan->status_validasi,
                            'date' => $tindakan->created_at->toISOString(),
                            'formatted_date' => $tindakan->created_at->diffForHumans(),
                        ];
                    });

                return response()->json([
                    'success' => true,
                    'data' => $activities,
                    'timestamp' => now()->toISOString(),
                ]);
            })->name('recent-activity');

            // Quick stats for mobile widgets
            Route::get('/quick-stats', function (Request $request) {
                $period = $request->get('period', 'week'); // day, week, month
                
                $now = now();
                [$startDate, $endDate] = match ($period) {
                    'day' => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
                    'month' => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
                    default => [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()],
                };

                return response()->json([
                    'success' => true,
                    'data' => [
                        'period' => $period,
                        'patients' => [
                            'new' => \App\Models\Pasien::whereBetween('created_at', [$startDate, $endDate])->count(),
                            'total' => \App\Models\Pasien::count(),
                        ],
                        'procedures' => [
                            'total' => \App\Models\Tindakan::whereBetween('tanggal_tindakan', [$startDate, $endDate])->count(),
                            'approved' => \App\Models\Tindakan::whereBetween('tanggal_tindakan', [$startDate, $endDate])->where('status_validasi', 'approved')->count(),
                            'pending' => \App\Models\Tindakan::whereBetween('tanggal_tindakan', [$startDate, $endDate])->where('status_validasi', 'pending')->count(),
                        ],
                        'financial' => [
                            'revenue' => \App\Models\Pendapatan::whereBetween('tanggal_pendapatan', [$startDate, $endDate])->sum('jumlah'),
                            'expenses' => \App\Models\Pengeluaran::whereBetween('tanggal_pengeluaran', [$startDate, $endDate])->sum('jumlah'),
                        ],
                    ],
                    'timestamp' => now()->toISOString(),
                ]);
            })->name('quick-stats');

        });

    });

    // Public endpoints (no auth required) with higher rate limiting
    Route::middleware([App\Http\Middleware\ApiRateLimiter::class . ':60:1'])->group(function () {
        
        // API Documentation endpoint
        Route::get('/docs', function () {
            return response()->json([
                'success' => true,
                'message' => 'Dokterku Enhanced API v1',
                'documentation' => [
                    'base_url' => url('/api/v1'),
                    'authentication' => 'Bearer Token (Sanctum)',
                    'rate_limit' => '200 requests per minute (authenticated), 60 requests per minute (public)',
                    'endpoints' => [
                        'patients' => '/api/v1/patients',
                        'procedures' => '/api/v1/procedures',
                        'revenue' => '/api/v1/revenue',
                        'expenses' => '/api/v1/expenses',
                        'analytics' => '/api/v1/analytics',
                        'mobile' => '/api/v1/mobile',
                    ],
                    'supported_formats' => ['JSON'],
                    'version' => 'v1.0.0',
                ],
                'timestamp' => now()->toISOString(),
            ]);
        })->name('docs');

    });

});

// Fallback for undefined enhanced API routes
Route::prefix('v1')->group(function () {
    Route::fallback(function () {
        return response()->json([
            'success' => false,
            'message' => 'Enhanced API endpoint not found',
            'available_endpoints' => [
                'patients' => '/api/v1/patients',
                'procedures' => '/api/v1/procedures',
                'revenue' => '/api/v1/revenue',
                'expenses' => '/api/v1/expenses',
                'analytics' => '/api/v1/analytics',
                'mobile' => '/api/v1/mobile',
            ],
            'documentation' => url('/api/v1/docs'),
            'timestamp' => now()->toISOString(),
        ], 404);
    });
});

// Temporary paramedis login API route to bypass CSRF
Route::post('/paramedis/login', function (Request $request) {
    // Simulate the UnifiedAuthController logic but for API
    $identifier = $request->input('email_or_username');
    $password = $request->input('password');
    
    // Find pegawai
    $pegawai = \App\Models\Pegawai::where('username', $identifier)
        ->orWhere('nik', $identifier)
        ->first();
    
    if (!$pegawai || !$pegawai->aktif) {
        return response()->json([
            'success' => false,
            'message' => 'Invalid credentials or user not active'
        ], 401);
    }
    
    // Check password
    if (!\Illuminate\Support\Facades\Hash::check($password, $pegawai->password)) {
        return response()->json([
            'success' => false,
            'message' => 'Invalid password'
        ], 401);
    }
    
    // Check if it's a paramedis
    if ($pegawai->jenis_pegawai !== 'Paramedis') {
        return response()->json([
            'success' => false,
            'message' => 'Access denied. Only paramedis can login here.'
        ], 403);
    }
    
    // Create or get user
    $role = \Spatie\Permission\Models\Role::where('name', 'paramedis')->first();
    if (!$role) {
        return response()->json([
            'success' => false,
            'message' => 'Paramedis role not found'
        ], 500);
    }
    
    $userEmail = $pegawai->nik . '@pegawai.local';
    $user = \App\Models\User::where('email', $userEmail)->first();
    
    if (!$user) {
        $user = \App\Models\User::create([
            'name' => $pegawai->nama_lengkap,
            'username' => $pegawai->username,
            'email' => $userEmail,
            'role_id' => $role->id,
            'is_active' => $pegawai->aktif,
            'password' => $pegawai->password,
        ]);
        
        $pegawai->update(['user_id' => $user->id]);
    }
    
    // Login the user
    \Illuminate\Support\Facades\Auth::login($user);
    
    return response()->json([
        'success' => true,
        'message' => 'Login successful',
        'user' => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => 'paramedis'
        ],
        'redirect_url' => '/paramedis'
    ]);
})->name('api.paramedis.login');

// Dokter Dashboard Stats API - Fix for 500 error
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/dokter/stats', [DokterStatsController::class, 'stats'])->name('api.dokter.stats');
    Route::get('/api/dokter/stats', [DokterStatsController::class, 'stats'])->name('api.dokter.stats.alt');
});

// Alternative route without auth for testing
Route::get('/public/dokter/stats', [DokterStatsController::class, 'stats'])->name('api.public.dokter.stats');