<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AttendanceController;

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
        Route::post('/register', [\App\Http\Controllers\Api\FaceRecognitionController::class, 'register']);
        Route::post('/verify', [\App\Http\Controllers\Api\FaceRecognitionController::class, 'verify']);
        Route::get('/status', [\App\Http\Controllers\Api\FaceRecognitionController::class, 'status']);
        Route::put('/update', [\App\Http\Controllers\Api\FaceRecognitionController::class, 'update']);
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
        Route::get('/schedule', [\App\Http\Controllers\Api\ParamedisDashboardController::class, 'schedule']);
        Route::get('/performance', [\App\Http\Controllers\Api\ParamedisDashboardController::class, 'performance']);
        Route::get('/notifications', [\App\Http\Controllers\Api\ParamedisDashboardController::class, 'notifications']);
        Route::put('/notifications/{id}/read', [\App\Http\Controllers\Api\ParamedisDashboardController::class, 'markNotificationRead']);
    });
});