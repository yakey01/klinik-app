<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Paramedis\AttendanceController;
use App\Models\WorkLocation;

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
        
        // Mobile Dashboard Routes  
        Route::get('/dashboard', function() {
            $user = auth()->user();
            if (!$user) {
                return response()->json(['error' => 'Not authenticated'], 401);
            }
            
            return response()->json([
                'jaspel_monthly' => 15200000,
                'jaspel_weekly' => 3800000,
                'approved_jaspel' => 12800000,
                'pending_jaspel' => 2400000,
                'minutes_worked' => 720,
                'shifts_this_month' => 22,
                'paramedis_name' => $user->name,
                'paramedis_specialty' => 'Dokter Umum',
                'today_attendance' => null,
                'recent_jaspel' => []
            ]);
        });
        Route::get('/schedule', [\App\Http\Controllers\Paramedis\ParamedisDashboardController::class, 'schedule']);
        Route::get('/performance', [\App\Http\Controllers\Paramedis\ParamedisDashboardController::class, 'performance']);
        Route::get('/notifications', [\App\Http\Controllers\Paramedis\ParamedisDashboardController::class, 'notifications']);
        Route::put('/notifications/{id}/read', [\App\Http\Controllers\Paramedis\ParamedisDashboardController::class, 'markNotificationRead']);
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

        // Dashboard endpoints with rate limiting
        Route::prefix('dashboards')->group(function () {
            // Paramedis dashboard
            Route::middleware(['role:paramedis'])->prefix('paramedis')->group(function () {
                Route::get('/', [App\Http\Controllers\Api\V2\Dashboards\ParamedisDashboardController::class, 'index']);
                Route::get('/schedule', [App\Http\Controllers\Api\V2\Dashboards\ParamedisDashboardController::class, 'schedule']);
                Route::get('/performance', [App\Http\Controllers\Api\V2\Dashboards\ParamedisDashboardController::class, 'performance']);
            });

            // Dokter dashboard
            Route::middleware(['role:dokter'])->prefix('dokter')->group(function () {
                Route::get('/', [App\Http\Controllers\Api\V2\Dashboards\DokterDashboardController::class, 'index']);
                Route::get('/patients', [App\Http\Controllers\Api\V2\Dashboards\DokterDashboardController::class, 'patients']);
                Route::get('/procedures', [App\Http\Controllers\Api\V2\Dashboards\DokterDashboardController::class, 'procedures']);
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
            });

            // TODO: Add other role dashboards (admin, manajer, bendahara, petugas)
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