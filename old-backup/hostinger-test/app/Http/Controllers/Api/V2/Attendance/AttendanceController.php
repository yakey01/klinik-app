<?php

namespace App\Http\Controllers\Api\V2\Attendance;

use App\Http\Controllers\Api\V2\BaseApiController;
use App\Models\Attendance;
use App\Models\WorkLocation;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

/**
 * @OA\Tag(
 *     name="Attendance",
 *     description="GPS-based attendance management system"
 * )
 */
class AttendanceController extends BaseApiController
{
    /**
     * @OA\Post(
     *     path="/api/v2/attendance/checkin",
     *     summary="GPS-based attendance check-in",
     *     tags={"Attendance"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"latitude", "longitude"},
     *             @OA\Property(property="latitude", type="number", format="float", example="-6.2088"),
     *             @OA\Property(property="longitude", type="number", format="float", example="106.8456"),
     *             @OA\Property(property="accuracy", type="number", format="float", example=10.5),
     *             @OA\Property(property="face_image", type="string", format="base64", description="Base64 encoded face image"),
     *             @OA\Property(property="location_name", type="string", example="Klinik Utama"),
     *             @OA\Property(property="notes", type="string", example="Check-in normal")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Check-in successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Check-in berhasil"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="attendance_id", type="integer", example=123),
     *                 @OA\Property(property="time_in", type="string", format="time", example="08:15:30"),
     *                 @OA\Property(property="status", type="string", example="present"),
     *                 @OA\Property(
     *                     property="coordinates",
     *                     type="object",
     *                     @OA\Property(property="latitude", type="number", format="float"),
     *                     @OA\Property(property="longitude", type="number", format="float")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=400, description="Already checked in or validation error"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=422, description="Validation errors")
     * )
     */
    public function checkin(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'accuracy' => 'nullable|numeric|min:0',
            'face_image' => 'nullable|string',
            'location_name' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $user = $this->getAuthenticatedUser();
        $today = Carbon::today();

        // Check if user already checked in today
        $existingAttendance = Attendance::where('user_id', $user->id)
            ->whereDate('date', $today)
            ->first();

        if ($existingAttendance) {
            return $this->errorResponse(
                'Anda sudah melakukan check-in hari ini',
                400,
                null,
                'ALREADY_CHECKED_IN'
            );
        }

        // Validate GPS location
        $gpsValidation = $this->validateGpsLocation(
            $request->latitude,
            $request->longitude,
            $request->accuracy
        );

        if (!$gpsValidation['valid']) {
            return $this->errorResponse(
                $gpsValidation['message'],
                400,
                null,
                'GPS_VALIDATION_FAILED'
            );
        }

        // Handle face recognition if provided
        $faceRecognitionResult = null;
        if ($request->face_image) {
            $faceRecognitionResult = $this->processFaceRecognition($user->id, $request->face_image);
        }

        // Create attendance record
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => $today,
            'time_in' => Carbon::now(),
            'latitude_in' => $request->latitude,
            'longitude_in' => $request->longitude,
            'accuracy_in' => $request->accuracy,
            'location_name_in' => $request->location_name ?? $gpsValidation['work_location']?->name,
            'work_location_id' => $gpsValidation['work_location']?->id,
            'status' => 'present',
            'notes_in' => $request->notes,
            'face_recognition_in' => $faceRecognitionResult,
            'ip_address_in' => $request->ip(),
            'user_agent_in' => $request->userAgent(),
        ]);

        // Clear cache
        $this->clearUserAttendanceCache($user->id);

        return $this->successResponse([
            'attendance_id' => $attendance->id,
            'time_in' => $attendance->time_in->format('H:i:s'),
            'status' => $attendance->status,
            'coordinates' => [
                'latitude' => $attendance->latitude_in,
                'longitude' => $attendance->longitude_in,
                'accuracy' => $attendance->accuracy_in,
            ],
            'location' => [
                'name' => $attendance->location_name_in,
                'work_location_id' => $attendance->work_location_id,
            ],
            'face_recognition' => $faceRecognitionResult ? [
                'verified' => $faceRecognitionResult['verified'] ?? false,
                'confidence' => $faceRecognitionResult['confidence'] ?? 0,
            ] : null,
        ], 'Check-in berhasil', 201);
    }

    /**
     * @OA\Post(
     *     path="/api/v2/attendance/checkout",
     *     summary="GPS-based attendance check-out",
     *     tags={"Attendance"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"latitude", "longitude"},
     *             @OA\Property(property="latitude", type="number", format="float", example="-6.2088"),
     *             @OA\Property(property="longitude", type="number", format="float", example="106.8456"),
     *             @OA\Property(property="accuracy", type="number", format="float", example=10.5),
     *             @OA\Property(property="face_image", type="string", format="base64", description="Base64 encoded face image"),
     *             @OA\Property(property="notes", type="string", example="Check-out normal")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Check-out successful"
     *     ),
     *     @OA\Response(response=400, description="Not checked in or validation error"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=422, description="Validation errors")
     * )
     */
    public function checkout(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'accuracy' => 'nullable|numeric|min:0',
            'face_image' => 'nullable|string',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $user = $this->getAuthenticatedUser();
        $today = Carbon::today();

        // Find today's attendance record
        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('date', $today)
            ->first();

        if (!$attendance) {
            return $this->errorResponse(
                'Anda belum melakukan check-in hari ini',
                400,
                null,
                'NOT_CHECKED_IN'
            );
        }

        if ($attendance->time_out) {
            return $this->errorResponse(
                'Anda sudah melakukan check-out hari ini',
                400,
                null,
                'ALREADY_CHECKED_OUT'
            );
        }

        // Validate GPS location
        $gpsValidation = $this->validateGpsLocation(
            $request->latitude,
            $request->longitude,
            $request->accuracy
        );

        if (!$gpsValidation['valid']) {
            return $this->errorResponse(
                $gpsValidation['message'],
                400,
                null,
                'GPS_VALIDATION_FAILED'
            );
        }

        // Handle face recognition if provided
        $faceRecognitionResult = null;
        if ($request->face_image) {
            $faceRecognitionResult = $this->processFaceRecognition($user->id, $request->face_image);
        }

        // Update attendance record
        $attendance->update([
            'time_out' => Carbon::now(),
            'latitude_out' => $request->latitude,
            'longitude_out' => $request->longitude,
            'accuracy_out' => $request->accuracy,
            'location_name_out' => $gpsValidation['work_location']?->name,
            'notes_out' => $request->notes,
            'face_recognition_out' => $faceRecognitionResult,
            'ip_address_out' => $request->ip(),
            'user_agent_out' => $request->userAgent(),
        ]);

        // Calculate work duration
        $workDuration = $attendance->time_in->diffInMinutes($attendance->time_out);

        // Clear cache
        $this->clearUserAttendanceCache($user->id);

        return $this->successResponse([
            'attendance_id' => $attendance->id,
            'time_out' => $attendance->time_out->format('H:i:s'),
            'work_duration' => [
                'minutes' => $workDuration,
                'formatted' => $this->formatWorkDuration($workDuration),
            ],
            'coordinates' => [
                'latitude' => $attendance->latitude_out,
                'longitude' => $attendance->longitude_out,
                'accuracy' => $attendance->accuracy_out,
            ],
            'face_recognition' => $faceRecognitionResult ? [
                'verified' => $faceRecognitionResult['verified'] ?? false,
                'confidence' => $faceRecognitionResult['confidence'] ?? 0,
            ] : null,
        ], 'Check-out berhasil');
    }

    /**
     * @OA\Get(
     *     path="/api/v2/attendance/today",
     *     summary="Get today's attendance status",
     *     tags={"Attendance"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Today's attendance status"
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function today(Request $request): JsonResponse
    {
        $user = $this->getAuthenticatedUser();
        
        $attendance = Cache::remember(
            "attendance:today:{$user->id}",
            config('api.cache.attendance_today_ttl', 60),
            function () use ($user) {
                return Attendance::where('user_id', $user->id)
                    ->whereDate('date', Carbon::today())
                    ->with('workLocation')
                    ->first();
            }
        );

        if (!$attendance) {
            return $this->successResponse([
                'has_checked_in' => false,
                'has_checked_out' => false,
                'can_check_in' => true,
                'can_check_out' => false,
                'attendance' => null,
            ], 'Today\'s attendance status');
        }

        return $this->successResponse([
            'has_checked_in' => true,
            'has_checked_out' => $attendance->time_out !== null,
            'can_check_in' => false,
            'can_check_out' => $attendance->time_out === null,
            'attendance' => [
                'id' => $attendance->id,
                'date' => $attendance->date->format('Y-m-d'),
                'time_in' => $attendance->time_in?->format('H:i:s'),
                'time_out' => $attendance->time_out?->format('H:i:s'),
                'status' => $attendance->status,
                'work_duration' => $attendance->time_out ? [
                    'minutes' => $attendance->time_in->diffInMinutes($attendance->time_out),
                    'formatted' => $this->formatWorkDuration($attendance->time_in->diffInMinutes($attendance->time_out)),
                ] : null,
                'location' => [
                    'name_in' => $attendance->location_name_in,
                    'name_out' => $attendance->location_name_out,
                    'work_location' => $attendance->workLocation ? [
                        'id' => $attendance->workLocation->id,
                        'name' => $attendance->workLocation->name,
                    ] : null,
                ],
            ],
        ], 'Today\'s attendance status');
    }

    /**
     * @OA\Get(
     *     path="/api/v2/attendance/history",
     *     summary="Get attendance history",
     *     tags={"Attendance"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         required=false,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Items per page",
     *         required=false,
     *         @OA\Schema(type="integer", example=15)
     *     ),
     *     @OA\Parameter(
     *         name="month",
     *         in="query",
     *         description="Filter by month (YYYY-MM)",
     *         required=false,
     *         @OA\Schema(type="string", example="2025-07")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Attendance history retrieved successfully"
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function history(Request $request): JsonResponse
    {
        $user = $this->getAuthenticatedUser();
        $perPage = min($request->get('per_page', 15), 50); // Max 50 items per page

        $query = Attendance::where('user_id', $user->id)
            ->with(['workLocation:id,name'])
            ->orderBy('date', 'desc');

        // Filter by month if provided
        if ($request->month && preg_match('/^\d{4}-\d{2}$/', $request->month)) {
            $month = Carbon::createFromFormat('Y-m', $request->month);
            $query->whereYear('date', $month->year)
                  ->whereMonth('date', $month->month);
        }

        $attendances = $query->paginate($perPage);

        $data = $attendances->getCollection()->map(function ($attendance) {
            return [
                'id' => $attendance->id,
                'date' => $attendance->date->format('Y-m-d'),
                'day_name' => $attendance->date->format('l'),
                'time_in' => $attendance->time_in?->format('H:i:s'),
                'time_out' => $attendance->time_out?->format('H:i:s'),
                'status' => $attendance->status,
                'work_duration' => $attendance->time_out ? [
                    'minutes' => $attendance->time_in->diffInMinutes($attendance->time_out),
                    'formatted' => $this->formatWorkDuration($attendance->time_in->diffInMinutes($attendance->time_out)),
                ] : null,
                'location' => [
                    'name_in' => $attendance->location_name_in,
                    'name_out' => $attendance->location_name_out,
                    'work_location' => $attendance->workLocation ? [
                        'id' => $attendance->workLocation->id,
                        'name' => $attendance->workLocation->name,
                    ] : null,
                ],
            ];
        });

        return $this->paginatedResponse($attendances->setCollection($data), 'Attendance history retrieved');
    }

    /**
     * @OA\Get(
     *     path="/api/v2/attendance/statistics",
     *     summary="Get attendance statistics",
     *     tags={"Attendance"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="month",
     *         in="query",
     *         description="Month for statistics (YYYY-MM)",
     *         required=false,
     *         @OA\Schema(type="string", example="2025-07")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Attendance statistics retrieved successfully"
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function statistics(Request $request): JsonResponse
    {
        $user = $this->getAuthenticatedUser();
        $month = $request->month ? Carbon::createFromFormat('Y-m', $request->month) : Carbon::now();

        $monthlyAttendances = Attendance::where('user_id', $user->id)
            ->whereYear('date', $month->year)
            ->whereMonth('date', $month->month)
            ->get();

        $totalDays = $monthlyAttendances->count();
        $presentDays = $monthlyAttendances->where('status', 'present')->count();
        $lateDays = $monthlyAttendances->where('status', 'late')->count();
        $totalMinutes = $monthlyAttendances->sum(function ($attendance) {
            return $attendance->time_out ? $attendance->time_in->diffInMinutes($attendance->time_out) : 0;
        });

        return $this->successResponse([
            'month' => $month->format('Y-m'),
            'month_name' => $month->format('F Y'),
            'total_days' => $totalDays,
            'present_days' => $presentDays,
            'late_days' => $lateDays,
            'absent_days' => max(0, $month->daysInMonth - $totalDays),
            'total_hours' => round($totalMinutes / 60, 2),
            'average_hours_per_day' => $totalDays > 0 ? round($totalMinutes / 60 / $totalDays, 2) : 0,
            'attendance_rate' => $totalDays > 0 ? round(($presentDays / $totalDays) * 100, 1) : 0,
        ], 'Attendance statistics retrieved');
    }

    /**
     * Validate GPS location against work locations
     */
    private function validateGpsLocation(float $latitude, float $longitude, ?float $accuracy): array
    {
        $gpsConfig = config('api.gps.attendance_validation');
        
        if (!$gpsConfig['enabled']) {
            return ['valid' => true, 'work_location' => null];
        }

        // Check accuracy requirement
        if ($accuracy && $accuracy > $gpsConfig['required_accuracy']) {
            return [
                'valid' => false,
                'message' => 'GPS accuracy tidak mencukupi. Diperlukan akurasi maksimal ' . $gpsConfig['required_accuracy'] . ' meter.',
                'work_location' => null,
            ];
        }

        // Get active work locations
        $workLocations = Cache::remember(
            'work_locations:active',
            config('api.cache.work_locations_ttl', 1800),
            fn() => WorkLocation::active()->get()
        );

        foreach ($workLocations as $location) {
            $distance = $this->calculateDistance(
                $latitude,
                $longitude,
                $location->latitude,
                $location->longitude
            );

            $allowedRadius = $location->radius_meters + $gpsConfig['location_radius_buffer'];

            if ($distance <= $allowedRadius) {
                return [
                    'valid' => true,
                    'work_location' => $location,
                    'distance' => round($distance, 2),
                ];
            }
        }

        return [
            'valid' => false,
            'message' => 'Lokasi Anda tidak berada dalam radius lokasi kerja yang diizinkan.',
            'work_location' => null,
        ];
    }

    /**
     * Calculate distance between two coordinates using Haversine formula
     */
    private function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000; // Earth radius in meters

        $lat1Rad = deg2rad($lat1);
        $lat2Rad = deg2rad($lat2);
        $deltaLatRad = deg2rad($lat2 - $lat1);
        $deltaLonRad = deg2rad($lon2 - $lon1);

        $a = sin($deltaLatRad / 2) * sin($deltaLatRad / 2) +
            cos($lat1Rad) * cos($lat2Rad) *
            sin($deltaLonRad / 2) * sin($deltaLonRad / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Process face recognition (placeholder)
     */
    private function processFaceRecognition(int $userId, string $faceImage): ?array
    {
        // TODO: Implement actual face recognition logic
        // For now, return a mock result
        return [
            'verified' => true,
            'confidence' => 0.95,
            'processed_at' => Carbon::now()->toISOString(),
        ];
    }

    /**
     * Format work duration in human readable format
     */
    private function formatWorkDuration(int $minutes): string
    {
        $hours = intval($minutes / 60);
        $remainingMinutes = $minutes % 60;

        if ($hours > 0) {
            return $hours . ' jam ' . $remainingMinutes . ' menit';
        }

        return $remainingMinutes . ' menit';
    }

    /**
     * Clear user attendance cache
     */
    private function clearUserAttendanceCache(int $userId): void
    {
        Cache::forget("attendance:today:{$userId}");
        // Clear other related cache keys as needed
    }
}