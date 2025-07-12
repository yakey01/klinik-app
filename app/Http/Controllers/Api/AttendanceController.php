<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\UserDevice;
use App\Services\GeolocationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    /**
     * Check-in dengan GPS
     */
    public function checkin(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'latitude' => 'required|numeric|between:-90,90',
                'longitude' => 'required|numeric|between:-180,180',
                'accuracy' => 'nullable|numeric',
                'device_info' => 'nullable|string',
                'face_image' => 'nullable|string', // Base64 image
                'timestamp' => 'nullable|string',
                'location_name' => 'nullable|string|max:255',
                'notes' => 'nullable|string|max:500',
            ]);

            $user = $request->user();
            $today = Carbon::today();

            // Check if user already checked in today
            if (Attendance::hasCheckedInToday($user->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda sudah melakukan check-in hari ini'
                ], 400);
            }
            
            // Validate geolocation
            $clinicCoords = GeolocationService::getClinicCoordinates();
            $isWithinRadius = GeolocationService::isWithinRadius(
                $request->latitude,
                $request->longitude,
                $clinicCoords['latitude'],
                $clinicCoords['longitude'],
                $clinicCoords['radius']
            );
            
            if (!$isWithinRadius) {
                $distance = GeolocationService::calculateDistance(
                    $request->latitude,
                    $request->longitude,
                    $clinicCoords['latitude'],
                    $clinicCoords['longitude']
                );
                
                return response()->json([
                    'success' => false,
                    'message' => 'Lokasi Anda terlalu jauh dari klinik',
                    'data' => [
                        'distance' => GeolocationService::formatDistance($distance),
                        'max_distance' => GeolocationService::formatDistance($clinicCoords['radius']),
                    ]
                ], 400);
            }
            
            // Validate GPS accuracy if provided
            if ($request->accuracy && !GeolocationService::isAccuracyValid($request->accuracy)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Akurasi GPS tidak memenuhi syarat. Pastikan GPS Anda aktif dengan baik.',
                ], 400);
            }

            // Get device information and auto-register device
            $deviceInfo = UserDevice::extractDeviceInfo($request);
            $deviceFingerprint = UserDevice::generateFingerprint($deviceInfo);
            
            // Auto-register device if enabled
            $registeredDevice = UserDevice::autoRegisterDevice($user->id, $deviceInfo);
            $deviceRegistrationInfo = null;
            
            if ($registeredDevice) {
                $deviceRegistrationInfo = [
                    'device_id' => $registeredDevice->id,
                    'device_name' => $registeredDevice->formatted_device_info,
                    'is_new' => $registeredDevice->wasRecentlyCreated,
                    'requires_approval' => $registeredDevice->status === 'pending',
                    'status' => $registeredDevice->status
                ];
            }

            // Handle face image base64
            $photoPath = null;
            if ($request->face_image) {
                $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $request->face_image));
                $imageName = 'checkin_' . $user->id . '_' . time() . '.jpg';
                $photoPath = 'attendance/checkin/' . $imageName;
                Storage::disk('public')->put($photoPath, $imageData);
            }

            // Create new attendance record with device binding
            $attendance = Attendance::create([
                'user_id' => $user->id,
                'date' => $today,
                'time_in' => Carbon::now()->format('H:i:s'),
                'latlon_in' => $request->latitude . ',' . $request->longitude,
                'location_name_in' => $request->location_name,
                'device_info' => $request->device_info,
                'device_id' => $deviceInfo['device_id'],
                'device_fingerprint' => $deviceFingerprint,
                'photo_in' => $photoPath,
                'notes' => $request->notes,
                'status' => $this->determineStatus(Carbon::now()),
            ]);

            // Check if this is a new device binding
            $deviceBinding = $request->attributes->get('device_binding');
            
            $response = [
                'success' => true,
                'message' => 'Check-in berhasil',
                'data' => [
                    'attendance_id' => $attendance->id,
                    'time_in' => $attendance->time_in,
                    'location' => $request->location_name ?? 'Lokasi GPS',
                    'status' => $attendance->status,
                    'coordinates' => [
                        'latitude' => $request->latitude,
                        'longitude' => $request->longitude
                    ]
                ]
            ];

            // Add device registration info
            if ($deviceRegistrationInfo) {
                $response['device_registration'] = $deviceRegistrationInfo;
                
                if ($deviceRegistrationInfo['is_new']) {
                    if ($deviceRegistrationInfo['requires_approval']) {
                        $response['message'] .= ' (Device baru memerlukan persetujuan admin)';
                    } else {
                        $response['message'] .= ' (Device baru berhasil didaftarkan)';
                    }
                }
            }

            return response()->json($response, 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Data yang dikirim tidak valid',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat check-in',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Check-out dengan GPS
     */
    public function checkout(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'latitude' => 'required|numeric|between:-90,90',
                'longitude' => 'required|numeric|between:-180,180',
                'accuracy' => 'nullable|numeric',
                'device_info' => 'nullable|string',
                'face_image' => 'nullable|string', // Base64 image
                'timestamp' => 'nullable|string',
                'location_name' => 'nullable|string|max:255',
                'notes' => 'nullable|string|max:500',
            ]);

            $user = $request->user();
            
            // Get today's attendance
            $attendance = Attendance::getTodayAttendance($user->id);

            if (!$attendance) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda belum melakukan check-in hari ini'
                ], 400);
            }

            if (!$attendance->canCheckOut()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda sudah melakukan check-out hari ini'
                ], 400);
            }

            // Get device information and auto-register device
            $deviceInfo = UserDevice::extractDeviceInfo($request);
            $registeredDevice = UserDevice::autoRegisterDevice($user->id, $deviceInfo);
            $deviceRegistrationInfo = null;
            
            if ($registeredDevice) {
                $deviceRegistrationInfo = [
                    'device_id' => $registeredDevice->id,
                    'device_name' => $registeredDevice->formatted_device_info,
                    'is_new' => $registeredDevice->wasRecentlyCreated,
                    'requires_approval' => $registeredDevice->status === 'pending',
                    'status' => $registeredDevice->status
                ];
            }

            // Handle face image base64
            $photoPath = null;
            if ($request->face_image) {
                $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $request->face_image));
                $imageName = 'checkout_' . $user->id . '_' . time() . '.jpg';
                $photoPath = 'attendance/checkout/' . $imageName;
                Storage::disk('public')->put($photoPath, $imageData);
            }

            // Update attendance record
            $attendance->update([
                'time_out' => Carbon::now()->format('H:i:s'),
                'latlon_out' => $request->latitude . ',' . $request->longitude,
                'location_name_out' => $request->location_name,
                'photo_out' => $photoPath,
                'notes' => $attendance->notes ? 
                    $attendance->notes . "\nCheck-out: " . $request->notes : 
                    $request->notes,
            ]);

            $response = [
                'success' => true,
                'message' => 'Check-out berhasil',
                'data' => [
                    'attendance_id' => $attendance->id,
                    'time_in' => $attendance->time_in,
                    'time_out' => $attendance->time_out,
                    'work_duration' => $attendance->formatted_work_duration,
                    'location_out' => $request->location_name ?? 'Lokasi GPS',
                    'coordinates' => [
                        'latitude' => $request->latitude,
                        'longitude' => $request->longitude
                    ]
                ]
            ];

            // Add device registration info
            if ($deviceRegistrationInfo) {
                $response['device_registration'] = $deviceRegistrationInfo;
                
                if ($deviceRegistrationInfo['is_new']) {
                    if ($deviceRegistrationInfo['requires_approval']) {
                        $response['message'] .= ' (Device baru memerlukan persetujuan admin)';
                    } else {
                        $response['message'] .= ' (Device baru berhasil didaftarkan)';
                    }
                }
            }

            return response()->json($response, 200);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Data yang dikirim tidak valid',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat check-out',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get attendance history
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $perPage = $request->get('per_page', 15);
            $month = $request->get('month', Carbon::now()->month);
            $year = $request->get('year', Carbon::now()->year);

            $attendances = Attendance::where('user_id', $user->id)
                ->whereYear('date', $year)
                ->whereMonth('date', $month)
                ->orderBy('date', 'desc')
                ->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => 'Data kehadiran berhasil diambil',
                'data' => $attendances->map(function ($attendance) {
                    return [
                        'id' => $attendance->id,
                        'date' => $attendance->date->format('Y-m-d'),
                        'day' => $attendance->date->format('l'),
                        'time_in' => $attendance->time_in,
                        'time_out' => $attendance->time_out,
                        'work_duration' => $attendance->formatted_work_duration,
                        'status' => $attendance->status,
                        'location_in' => $attendance->location_name_in,
                        'location_out' => $attendance->location_name_out,
                        'coordinates_in' => [
                            'latitude' => $attendance->latitude_in,
                            'longitude' => $attendance->longitude_in
                        ],
                        'coordinates_out' => $attendance->latlon_out ? [
                            'latitude' => $attendance->latitude_out,
                            'longitude' => $attendance->longitude_out
                        ] : null,
                        'photos' => [
                            'check_in' => $attendance->photo_in ? asset('storage/' . $attendance->photo_in) : null,
                            'check_out' => $attendance->photo_out ? asset('storage/' . $attendance->photo_out) : null,
                        ],
                        'notes' => $attendance->notes,
                    ];
                }),
                'pagination' => [
                    'current_page' => $attendances->currentPage(),
                    'last_page' => $attendances->lastPage(),
                    'per_page' => $attendances->perPage(),
                    'total' => $attendances->total(),
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengambil data',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get today's attendance status
     */
    public function today(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $attendance = Attendance::getTodayAttendance($user->id);

            if (!$attendance) {
                return response()->json([
                    'success' => true,
                    'message' => 'Belum ada data kehadiran hari ini',
                    'data' => [
                        'has_checked_in' => false,
                        'has_checked_out' => false,
                        'can_check_in' => true,
                        'can_check_out' => false,
                    ]
                ], 200);
            }

            return response()->json([
                'success' => true,
                'message' => 'Data kehadiran hari ini',
                'data' => [
                    'attendance_id' => $attendance->id,
                    'date' => $attendance->date->format('Y-m-d'),
                    'time_in' => $attendance->time_in,
                    'time_out' => $attendance->time_out,
                    'work_duration' => $attendance->formatted_work_duration,
                    'status' => $attendance->status,
                    'location_in' => $attendance->location_name_in,
                    'location_out' => $attendance->location_name_out,
                    'has_checked_in' => true,
                    'has_checked_out' => !is_null($attendance->time_out),
                    'can_check_in' => false,
                    'can_check_out' => $attendance->canCheckOut(),
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengambil data',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Determine attendance status based on time
     */
    private function determineStatus(Carbon $checkInTime): string
    {
        $workStartTime = Carbon::createFromTime(8, 0, 0); // 08:00
        
        if ($checkInTime->gt($workStartTime)) {
            return 'late';
        }
        
        return 'present';
    }
}