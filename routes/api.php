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
    Route::prefix('paramedis')->middleware('paramedis')->group(function () {
        Route::post('/attendance/checkin', [AttendanceController::class, 'checkin']);
        Route::post('/attendance/checkout', [AttendanceController::class, 'checkout']);
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
    });
});