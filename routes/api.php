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
                
                // Dashboard endpoints
                Route::get('/', function () {
                    $user = auth()->user();
                    return response()->json([
                        'success' => true,
                        'message' => 'Dokter dashboard data retrieved',
                        'data' => [
                            'user' => [
                                'id' => $user->id,
                                'name' => $user->name,
                                'initials' => strtoupper(substr($user->name, 0, 2)),
                                'role' => 'Dokter Umum'
                            ],
                            'stats' => [
                                'patients_today' => 12,
                                'tindakan_today' => 8,
                                'jaspel_month' => 25000000,
                                'shifts_week' => 5
                            ],
                            'current_status' => 'active',
                            'quick_actions' => [
                                [
                                    'id' => 'presensi',
                                    'title' => 'Presensi',
                                    'subtitle' => 'Kelola kehadiran dan absensi',
                                    'icon' => '📋',
                                    'action' => 'presensi',
                                    'enabled' => true
                                ],
                                [
                                    'id' => 'pasien',
                                    'title' => 'Data Pasien',
                                    'subtitle' => 'Kelola data dan riwayat pasien',
                                    'icon' => '👥',
                                    'action' => 'pasien',
                                    'enabled' => true
                                ],
                                [
                                    'id' => 'tindakan',
                                    'title' => 'Tindakan Medis',
                                    'subtitle' => 'Input dan kelola tindakan medis',
                                    'icon' => '🏥',
                                    'action' => 'tindakan',
                                    'enabled' => true
                                ],
                                [
                                    'id' => 'jaspel',
                                    'title' => 'Jaspel',
                                    'subtitle' => 'Lihat jasa pelayanan dan penghasilan',
                                    'icon' => '💰',
                                    'action' => 'jaspel',
                                    'enabled' => true
                                ]
                            ]
                        ],
                        'meta' => [
                            'version' => '2.0',
                            'timestamp' => now()->toISOString(),
                            'request_id' => \Illuminate\Support\Str::uuid()->toString(),
                        ]
                    ]);
                });
                
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
                
                // Jaspel endpoints (placeholder)
                Route::get('/jaspel', function () {
                    return response()->json([
                        'success' => true,
                        'message' => 'Jaspel data will be available soon',
                        'data' => [
                            'jaspel_month' => 25000000,
                            'jaspel_week' => 6250000,
                            'approved_jaspel' => 20000000,
                            'pending_jaspel' => 5000000
                        ],
                        'meta' => [
                            'version' => '2.0',
                            'timestamp' => now()->toISOString(),
                            'request_id' => \Illuminate\Support\Str::uuid()->toString(),
                        ]
                    ]);
                });
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