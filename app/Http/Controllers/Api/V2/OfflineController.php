<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\NonParamedisAttendance;
use App\Models\WorkLocation;
use App\Models\Schedule;
use App\Models\Notification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class OfflineController extends Controller
{
    /**
     * Get offline data bundle for user
     */
    public function getOfflineData(Request $request)
    {
        $user = auth()->user();
        $today = Carbon::today();
        $endDate = $today->copy()->addDays(7); // Next 7 days
        
        // Get work locations
        $workLocations = WorkLocation::active()->get([
            'id', 'name', 'latitude', 'longitude', 'radius_meters', 'location_type', 'address'
        ]);
        
        // Get user's schedule for next 7 days
        $schedules = Schedule::where('user_id', $user->id)
            ->whereBetween('date', [$today, $endDate])
            ->with(['shift:id,name,start_time,end_time'])
            ->get();
        
        // Get recent attendance history (last 30 days)
        $attendanceHistory = NonParamedisAttendance::where('user_id', $user->id)
            ->where('attendance_date', '>=', $today->copy()->subDays(30))
            ->orderBy('attendance_date', 'desc')
            ->get();
        
        // Get unread notifications
        $notifications = Notification::where('user_id', $user->id)
            ->unread()
            ->notExpired()
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();
        
        // Get user settings
        $userSettings = [
            'email_notifications' => $user->email_notifications,
            'push_notifications' => $user->push_notifications,
            'attendance_reminders' => $user->attendance_reminders,
            'schedule_updates' => $user->schedule_updates,
            'timezone' => $user->timezone,
            'language' => $user->language,
            'theme' => $user->theme,
        ];
        
        return response()->json([
            'success' => true,
            'message' => 'Offline data bundle retrieved',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'nip' => $user->nip,
                    'role' => $user->role?->name,
                    'profile_photo_path' => $user->profile_photo_path,
                ],
                'work_locations' => $workLocations,
                'schedules' => $schedules,
                'attendance_history' => $attendanceHistory,
                'notifications' => $notifications,
                'settings' => $userSettings,
                'cache_timestamp' => now()->toISOString(),
            ],
            'meta' => [
                'version' => '2.0',
                'timestamp' => now()->toISOString(),
                'request_id' => \Illuminate\Support\Str::uuid()->toString(),
            ]
        ]);
    }
    
    /**
     * Sync offline attendance data
     */
    public function syncOfflineAttendance(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'offline_data' => 'required|array',
            'offline_data.*.id' => 'required|string',
            'offline_data.*.action' => 'required|in:checkin,checkout',
            'offline_data.*.timestamp' => 'required|date',
            'offline_data.*.latitude' => 'required|numeric',
            'offline_data.*.longitude' => 'required|numeric',
            'offline_data.*.accuracy' => 'nullable|numeric',
            'offline_data.*.work_location_id' => 'nullable|exists:work_locations,id',
            'offline_data.*.device_info' => 'nullable|array',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }
        
        $user = auth()->user();
        $syncedItems = [];
        $failedItems = [];
        
        DB::transaction(function () use ($request, $user, &$syncedItems, &$failedItems) {
            foreach ($request->offline_data as $item) {
                try {
                    $timestamp = Carbon::parse($item['timestamp']);
                    $date = $timestamp->format('Y-m-d');
                    
                    // Get or create attendance record
                    $attendance = NonParamedisAttendance::firstOrCreate(
                        [
                            'user_id' => $user->id,
                            'attendance_date' => $date,
                        ],
                        [
                            'status' => 'incomplete',
                            'approval_status' => 'pending',
                        ]
                    );
                    
                    // Process based on action
                    if ($item['action'] === 'checkin') {
                        if (!$attendance->check_in_time) {
                            $attendance->update([
                                'check_in_time' => $timestamp,
                                'check_in_latitude' => $item['latitude'],
                                'check_in_longitude' => $item['longitude'],
                                'check_in_accuracy' => $item['accuracy'] ?? null,
                                'work_location_id' => $item['work_location_id'] ?? null,
                                'device_info' => $item['device_info'] ?? null,
                                'status' => 'checked_in',
                            ]);
                        }
                    } elseif ($item['action'] === 'checkout') {
                        if ($attendance->check_in_time && !$attendance->check_out_time) {
                            $attendance->update([
                                'check_out_time' => $timestamp,
                                'check_out_latitude' => $item['latitude'],
                                'check_out_longitude' => $item['longitude'],
                                'check_out_accuracy' => $item['accuracy'] ?? null,
                                'status' => 'checked_out',
                            ]);
                            
                            // Calculate work duration
                            $attendance->updateWorkDuration();
                        }
                    }
                    
                    $syncedItems[] = [
                        'id' => $item['id'],
                        'status' => 'synced',
                        'attendance_id' => $attendance->id,
                    ];
                    
                } catch (\Exception $e) {
                    $failedItems[] = [
                        'id' => $item['id'],
                        'status' => 'failed',
                        'error' => $e->getMessage(),
                    ];
                }
            }
        });
        
        return response()->json([
            'success' => true,
            'message' => 'Offline attendance sync completed',
            'data' => [
                'synced_count' => count($syncedItems),
                'failed_count' => count($failedItems),
                'synced_items' => $syncedItems,
                'failed_items' => $failedItems,
            ],
            'meta' => [
                'version' => '2.0',
                'timestamp' => now()->toISOString(),
                'request_id' => \Illuminate\Support\Str::uuid()->toString(),
            ]
        ]);
    }
    
    /**
     * Get device information for debugging
     */
    public function getDeviceInfo(Request $request)
    {
        $deviceInfo = [
            'user_agent' => $request->userAgent(),
            'ip_address' => $request->ip(),
            'timestamp' => now()->toISOString(),
            'supports_service_worker' => $request->header('Service-Worker') !== null,
            'is_mobile' => $this->isMobile($request->userAgent()),
            'platform' => $this->getPlatform($request->userAgent()),
        ];
        
        return response()->json([
            'success' => true,
            'message' => 'Device information retrieved',
            'data' => $deviceInfo,
            'meta' => [
                'version' => '2.0',
                'timestamp' => now()->toISOString(),
                'request_id' => \Illuminate\Support\Str::uuid()->toString(),
            ]
        ]);
    }
    
    /**
     * Check offline sync status
     */
    public function getOfflineStatus(Request $request)
    {
        $user = auth()->user();
        $today = Carbon::today();
        
        // Check for today's attendance
        $todayAttendance = NonParamedisAttendance::where('user_id', $user->id)
            ->where('attendance_date', $today->format('Y-m-d'))
            ->first();
        
        // Check for pending sync items (this would be stored in a separate table in production)
        $hasPendingSync = false; // Placeholder
        
        return response()->json([
            'success' => true,
            'message' => 'Offline status retrieved',
            'data' => [
                'is_online' => true, // This would be determined by the client
                'has_pending_sync' => $hasPendingSync,
                'today_attendance' => $todayAttendance,
                'can_check_in' => !$todayAttendance || !$todayAttendance->check_in_time,
                'can_check_out' => $todayAttendance && $todayAttendance->check_in_time && !$todayAttendance->check_out_time,
                'last_sync' => $todayAttendance?->updated_at,
            ],
            'meta' => [
                'version' => '2.0',
                'timestamp' => now()->toISOString(),
                'request_id' => \Illuminate\Support\Str::uuid()->toString(),
            ]
        ]);
    }
    
    /**
     * Test offline functionality
     */
    public function testOffline(Request $request)
    {
        // This endpoint can be used to test offline functionality
        return response()->json([
            'success' => true,
            'message' => 'Offline test endpoint',
            'data' => [
                'timestamp' => now()->toISOString(),
                'user_id' => auth()->id(),
                'request_data' => $request->all(),
            ],
            'meta' => [
                'version' => '2.0',
                'timestamp' => now()->toISOString(),
                'request_id' => \Illuminate\Support\Str::uuid()->toString(),
            ]
        ]);
    }
    
    /**
     * Check if user agent is mobile
     */
    private function isMobile($userAgent)
    {
        $mobileKeywords = ['Mobile', 'Android', 'iPhone', 'iPad', 'iPod', 'BlackBerry', 'Windows Phone'];
        
        foreach ($mobileKeywords as $keyword) {
            if (stripos($userAgent, $keyword) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Get platform from user agent
     */
    private function getPlatform($userAgent)
    {
        if (stripos($userAgent, 'Android') !== false) {
            return 'Android';
        } elseif (stripos($userAgent, 'iPhone') !== false || stripos($userAgent, 'iPad') !== false) {
            return 'iOS';
        } elseif (stripos($userAgent, 'Windows') !== false) {
            return 'Windows';
        } elseif (stripos($userAgent, 'Macintosh') !== false) {
            return 'macOS';
        } elseif (stripos($userAgent, 'Linux') !== false) {
            return 'Linux';
        }
        
        return 'Unknown';
    }
}
