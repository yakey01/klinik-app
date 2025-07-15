<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Models\NonParamedisAttendance;
use App\Models\User;
use App\Models\WorkLocation;
use App\Services\GpsValidationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class NonParamedisDashboardController extends Controller
{
    protected GpsValidationService $gpsService;

    public function __construct(GpsValidationService $gpsService)
    {
        $this->gpsService = $gpsService;
    }
    /**
     * Standardized success response
     */
    private function successResponse(string $message, $data = null, $meta = [])
    {
        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => $data,
            'meta' => array_merge([
                'version' => '2.0',
                'timestamp' => now()->toISOString(),
                'request_id' => Str::uuid()->toString(),
            ], $meta)
        ]);
    }

    /**
     * Standardized error response
     */
    private function errorResponse(string $message, int $code = 400, $errors = null)
    {
        return response()->json([
            'status' => 'error',
            'message' => $message,
            'errors' => $errors,
            'meta' => [
                'version' => '2.0',
                'timestamp' => now()->toISOString(),
                'request_id' => Str::uuid()->toString(),
            ]
        ], $code);
    }


    /**
     * Calculate expected work days in a month (excluding weekends)
     */
    private function calculateExpectedWorkDays(Carbon $month): int
    {
        $start = $month->copy()->startOfMonth();
        $end = $month->copy()->endOfMonth();
        $workDays = 0;

        while ($start->lte($end)) {
            if ($start->isWeekday()) {
                $workDays++;
            }
            $start->addDay();
        }

        return $workDays;
    }

    /**
     * Get current attendance status
     */
    private function getCurrentAttendanceStatus(?NonParamedisAttendance $attendance): string
    {
        if (!$attendance) {
            return 'not_checked_in';
        }

        if ($attendance->check_out_time) {
            return 'checked_out';
        }

        if ($attendance->check_in_time) {
            return 'checked_in';
        }

        return 'not_checked_in';
    }

    /**
     * Get attendance subtitle for UI
     */
    private function getAttendanceSubtitle(string $status): string
    {
        return match($status) {
            'not_checked_in' => 'Belum check-in hari ini',
            'checked_in' => 'Sudah check-in, belum check-out',
            'checked_out' => 'Sudah selesai hari ini',
            default => 'Status tidak dikenal'
        };
    }

    /**
     * Get attendance icon for UI
     */
    private function getAttendanceIcon(string $status): string
    {
        return match($status) {
            'not_checked_in' => '🕐',
            'checked_in' => '✅',
            'checked_out' => '🏠',
            default => '❓'
        };
    }

    /**
     * Get dashboard data for non-paramedis user
     */
    public function dashboard(Request $request)
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return $this->errorResponse('User not authenticated', 401);
            }
            
            $today = Carbon::today();
            $currentMonth = Carbon::now()->startOfMonth();
            $currentWeek = Carbon::now()->startOfWeek();
            
            // Get today's attendance using new schema
            $todayAttendance = NonParamedisAttendance::where('user_id', $user->id)
                ->where('attendance_date', $today)
                ->first();
            
            // Calculate work hours for today
            $hoursToday = 0;
            $minutesToday = 0;
            if ($todayAttendance && $todayAttendance->total_work_minutes) {
                $hoursToday = floor($todayAttendance->total_work_minutes / 60);
                $minutesToday = $todayAttendance->total_work_minutes % 60;
            }
            
            // Get approved work days this month
            $workDaysThisMonth = NonParamedisAttendance::where('user_id', $user->id)
                ->where('attendance_date', '>=', $currentMonth)
                ->where('approval_status', 'approved')
                ->whereNotNull('check_in_time')
                ->count();
            
            // Calculate total expected work days in current month
            $totalExpectedWorkDays = $this->calculateExpectedWorkDays($currentMonth);
            
            // Calculate attendance rate
            $attendanceRate = $totalExpectedWorkDays > 0 
                ? round(($workDaysThisMonth / $totalExpectedWorkDays) * 100) 
                : 100;
            
            // Get shifts this week
            $shiftsThisWeek = NonParamedisAttendance::where('user_id', $user->id)
                ->where('attendance_date', '>=', $currentWeek)
                ->where('attendance_date', '<=', Carbon::now()->endOfWeek())
                ->count();
            
            // Calculate total work hours this month
            $totalWorkMinutesThisMonth = NonParamedisAttendance::where('user_id', $user->id)
                ->where('attendance_date', '>=', $currentMonth)
                ->where('approval_status', 'approved')
                ->sum('total_work_minutes') ?? 0;
            
            $totalWorkHoursThisMonth = round($totalWorkMinutesThisMonth / 60, 1);
            
            // Determine current status
            $currentStatus = $this->getCurrentAttendanceStatus($todayAttendance);
            
            return $this->successResponse('Dashboard data retrieved successfully', [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'initials' => $this->getInitials($user->name),
                    'role' => 'Admin Non-Medis'
                ],
                'stats' => [
                    'hours_today' => $hoursToday,
                    'minutes_today' => $minutesToday,
                    'work_days_this_month' => $workDaysThisMonth,
                    'total_work_hours_this_month' => $totalWorkHoursThisMonth,
                    'attendance_rate' => $attendanceRate,
                    'shifts_this_week' => $shiftsThisWeek,
                    'expected_work_days' => $totalExpectedWorkDays
                ],
                'current_status' => $currentStatus,
                'today_attendance' => $todayAttendance ? [
                    'check_in_time' => $todayAttendance->check_in_time?->format('H:i'),
                    'check_out_time' => $todayAttendance->check_out_time?->format('H:i'),
                    'work_duration' => $todayAttendance->formatted_work_duration,
                    'status' => $todayAttendance->status,
                    'approval_status' => $todayAttendance->approval_status
                ] : null,
                'quick_actions' => [
                    [
                        'id' => 'attendance',
                        'title' => $currentStatus === 'not_checked_in' ? 'Check In' : ($currentStatus === 'checked_in' ? 'Check Out' : 'Sudah Selesai'),
                        'subtitle' => $this->getAttendanceSubtitle($currentStatus),
                        'icon' => $this->getAttendanceIcon($currentStatus),
                        'action' => 'attendance',
                        'enabled' => in_array($currentStatus, ['not_checked_in', 'checked_in'])
                    ],
                    [
                        'id' => 'schedule',
                        'title' => 'Jadwal Kerja',
                        'subtitle' => "{$shiftsThisWeek} hari minggu ini",
                        'icon' => '📅',
                        'action' => 'schedule',
                        'enabled' => true
                    ],
                    [
                        'id' => 'reports',
                        'title' => 'Laporan Kehadiran',
                        'subtitle' => "Rate: {$attendanceRate}%",
                        'icon' => '📊',
                        'action' => 'reports',
                        'enabled' => true
                    ]
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Dashboard error: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->errorResponse('Failed to load dashboard data', 500, $e->getMessage());
        }
    }
    
    /**
     * Get attendance status and data
     */
    public function getAttendanceStatus(Request $request)
    {
        try {
            $user = Auth::user();
            $today = Carbon::today();
            
            $attendance = NonParamedisAttendance::where('user_id', $user->id)
                ->where('attendance_date', $today)
                ->first();
            
            $status = $this->getCurrentAttendanceStatus($attendance);
            
            // Get work location for reference
            $workLocation = WorkLocation::active()->first();
            
            return $this->successResponse('Attendance status retrieved', [
                'status' => $status,
                'check_in_time' => $attendance?->check_in_time?->format('H:i:s'),
                'check_out_time' => $attendance?->check_out_time?->format('H:i:s'),
                'work_duration' => $attendance?->formatted_work_duration,
                'location' => $workLocation ? [
                    'id' => $workLocation->id,
                    'name' => $workLocation->name,
                    'address' => $workLocation->address,
                    'radius' => $workLocation->radius_meters,
                    'coordinates' => $workLocation->coordinates
                ] : null,
                'can_check_in' => $status === 'not_checked_in',
                'can_check_out' => $status === 'checked_in'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Attendance status error: ' . $e->getMessage());
            return $this->errorResponse('Failed to get attendance status', 500);
        }
    }
    
    /**
     * Check in attendance
     */
    public function checkIn(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'latitude' => 'required|numeric|between:-90,90',
                'longitude' => 'required|numeric|between:-180,180',
                'accuracy' => 'nullable|numeric|min:0',
                'work_location_id' => 'nullable|exists:work_locations,id'
            ]);
            
            if ($validator->fails()) {
                return $this->errorResponse('Invalid GPS data', 422, $validator->errors());
            }
            
            $user = Auth::user();
            $today = Carbon::today();
            
            // Check if already checked in today
            $existingAttendance = NonParamedisAttendance::where('user_id', $user->id)
                ->where('attendance_date', $today)
                ->first();
            
            if ($existingAttendance && $existingAttendance->check_in_time) {
                return $this->errorResponse('Anda sudah melakukan check-in hari ini', 422);
            }
            
            // Validate GPS location using service
            $gpsValidation = $this->gpsService->validateLocation(
                $request->latitude, 
                $request->longitude, 
                $request->accuracy
            );
            
            if (!$gpsValidation['is_valid']) {
                return $this->errorResponse(
                    $this->gpsService->getValidationMessage($gpsValidation),
                    422,
                    [
                        'gps_validation' => $gpsValidation,
                        'distance' => $gpsValidation['distance'],
                        'gps_quality' => $this->gpsService->getGpsQuality($request->accuracy)
                    ]
                );
            }
            
            // Determine work location
            $workLocationId = $request->work_location_id ?? $gpsValidation['location']['id'] ?? null;
            
            // Create or update attendance record
            $attendance = NonParamedisAttendance::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'attendance_date' => $today,
                ],
                [
                    'work_location_id' => $workLocationId,
                    'check_in_time' => now(),
                    'check_in_latitude' => $request->latitude,
                    'check_in_longitude' => $request->longitude,
                    'check_in_accuracy' => $request->accuracy ?? 0,
                    'check_in_distance' => $gpsValidation['distance'],
                    'check_in_valid_location' => $gpsValidation['is_valid'],
                    'status' => 'checked_in',
                    'approval_status' => 'pending',
                    'device_info' => [
                        'user_agent' => $request->header('User-Agent'),
                        'ip_address' => $request->ip(),
                        'platform' => 'mobile_web'
                    ],
                    'gps_metadata' => [
                        'accuracy' => $request->accuracy,
                        'validation_result' => $gpsValidation,
                        'timestamp' => now()->toISOString()
                    ]
                ]
            );
            
            return $this->successResponse('Check-in berhasil!', [
                'attendance_id' => $attendance->id,
                'check_in_time' => $attendance->check_in_time->format('H:i:s'),
                'status' => 'checked_in',
                'location' => $gpsValidation['location'],
                'distance' => $gpsValidation['distance']
            ]);
            
        } catch (\Exception $e) {
            Log::error('Check-in error: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'request_data' => $request->all()
            ]);
            
            return $this->errorResponse('Check-in gagal', 500, $e->getMessage());
        }
    }
    
    /**
     * Check out attendance
     */
    public function checkOut(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'latitude' => 'required|numeric|between:-90,90',
                'longitude' => 'required|numeric|between:-180,180',
                'accuracy' => 'nullable|numeric|min:0'
            ]);
            
            if ($validator->fails()) {
                return $this->errorResponse('Invalid GPS data', 422, $validator->errors());
            }
            
            $user = Auth::user();
            $today = Carbon::today();
            
            // Find today's attendance
            $attendance = NonParamedisAttendance::where('user_id', $user->id)
                ->where('attendance_date', $today)
                ->whereNotNull('check_in_time')
                ->whereNull('check_out_time')
                ->first();
            
            if (!$attendance) {
                return $this->errorResponse('Tidak ditemukan data check-in hari ini', 422);
            }
            
            // Validate GPS location using service
            $gpsValidation = $this->gpsService->validateLocation(
                $request->latitude, 
                $request->longitude, 
                $request->accuracy
            );
            
            // Calculate work duration
            $checkOutTime = now();
            $workDurationMinutes = $attendance->check_in_time->diffInMinutes($checkOutTime);
            
            // Update attendance with check-out data
            $attendance->update([
                'check_out_time' => $checkOutTime,
                'check_out_latitude' => $request->latitude,
                'check_out_longitude' => $request->longitude,
                'check_out_accuracy' => $request->accuracy ?? 0,
                'check_out_distance' => $gpsValidation['distance'],
                'check_out_valid_location' => $gpsValidation['is_valid'],
                'total_work_minutes' => $workDurationMinutes,
                'status' => 'checked_out',
                'approval_status' => 'pending' // Will be auto-approved later by admin or system
            ]);
            
            return $this->successResponse('Check-out berhasil!', [
                'attendance_id' => $attendance->id,
                'check_out_time' => $attendance->check_out_time->format('H:i:s'),
                'work_duration_hours' => round($workDurationMinutes / 60, 1),
                'work_duration_formatted' => $attendance->formatted_work_duration,
                'status' => 'checked_out',
                'location_valid' => $gpsValidation['is_valid'],
                'distance' => $gpsValidation['distance']
            ]);
            
        } catch (\Exception $e) {
            Log::error('Check-out error: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'request_data' => $request->all()
            ]);
            
            return $this->errorResponse('Check-out gagal', 500, $e->getMessage());
        }
    }
    
    /**
     * Get attendance history for today
     */
    public function getTodayHistory(Request $request)
    {
        try {
            $user = Auth::user();
            $today = Carbon::today();
            
            $attendance = NonParamedisAttendance::where('user_id', $user->id)
                ->where('attendance_date', $today)
                ->with('workLocation')
                ->first();
            
            $history = [];
            
            if ($attendance) {
                if ($attendance->check_in_time) {
                    $history[] = [
                        'action' => 'Check-in',
                        'time' => $attendance->check_in_time->format('H:i'),
                        'subtitle' => 'Hari ini • ' . ($attendance->workLocation?->name ?? 'Lokasi kerja'),
                        'location_valid' => $attendance->check_in_valid_location,
                        'distance' => $attendance->check_in_distance
                    ];
                }
                
                if ($attendance->check_out_time) {
                    $history[] = [
                        'action' => 'Check-out',
                        'time' => $attendance->check_out_time->format('H:i'),
                        'subtitle' => 'Hari ini • ' . $attendance->formatted_work_duration,
                        'location_valid' => $attendance->check_out_valid_location,
                        'distance' => $attendance->check_out_distance
                    ];
                }
            }
            
            return $this->successResponse('Today history retrieved', [
                'history' => $history,
                'has_activity' => count($history) > 0,
                'attendance_summary' => $attendance ? [
                    'total_work_time' => $attendance->formatted_work_duration,
                    'status' => $attendance->status,
                    'approval_status' => $attendance->approval_status
                ] : null
            ]);
            
        } catch (\Exception $e) {
            Log::error('Today history error: ' . $e->getMessage());
            return $this->errorResponse('Failed to get today history', 500);
        }
    }
    
    /**
     * Get schedule for current month
     */
    public function getSchedule(Request $request)
    {
        try {
            $user = Auth::user();
            $month = $request->get('month', Carbon::now()->month);
            $year = $request->get('year', Carbon::now()->year);
            
            $startOfMonth = Carbon::create($year, $month, 1)->startOfMonth();
            $endOfMonth = Carbon::create($year, $month, 1)->endOfMonth();
            
            // Get scheduled attendance for the month
            $attendances = NonParamedisAttendance::where('user_id', $user->id)
                ->whereBetween('attendance_date', [$startOfMonth, $endOfMonth])
                ->with('workLocation')
                ->orderBy('attendance_date')
                ->get();
            
            // Format monthly calendar data
            $monthlyCalendar = [];
            foreach ($attendances as $attendance) {
                $monthlyCalendar[] = [
                    'date' => $attendance->attendance_date->format('Y-m-d'),
                    'status' => $attendance->status,
                    'approval_status' => $attendance->approval_status,
                    'work_duration' => $attendance->formatted_work_duration,
                    'location' => $attendance->workLocation?->name
                ];
            }
            
            // Get current week schedule
            $currentWeek = Carbon::now()->startOfWeek();
            $weeklySchedule = NonParamedisAttendance::where('user_id', $user->id)
                ->where('attendance_date', '>=', $currentWeek)
                ->where('attendance_date', '<=', Carbon::now()->endOfWeek())
                ->with('workLocation')
                ->orderBy('attendance_date')
                ->get();
            
            $weeklyShifts = [];
            foreach ($weeklySchedule as $shift) {
                $weeklyShifts[] = [
                    'date' => $shift->attendance_date->format('Y-m-d'),
                    'day_name' => $shift->attendance_date->locale('id')->dayName,
                    'check_in' => $shift->check_in_time?->format('H:i'),
                    'check_out' => $shift->check_out_time?->format('H:i'),
                    'duration' => $shift->formatted_work_duration,
                    'location' => $shift->workLocation?->name ?? 'Klinik Dokterku',
                    'status' => $shift->status,
                    'approval_status' => $shift->approval_status
                ];
            }
            
            return $this->successResponse('Schedule retrieved successfully', [
                'month' => [
                    'name' => $startOfMonth->locale('id')->monthName . ' ' . $year,
                    'total_days' => $attendances->count(),
                    'work_days' => $attendances->where('status', 'checked_out')->count(),
                    'calendar' => $monthlyCalendar
                ],
                'weekly_shifts' => $weeklyShifts,
                'summary' => [
                    'total_work_hours' => round($attendances->sum('total_work_minutes') / 60, 1),
                    'average_daily_hours' => $attendances->where('total_work_minutes', '>', 0)->count() > 0 
                        ? round($attendances->sum('total_work_minutes') / $attendances->where('total_work_minutes', '>', 0)->count() / 60, 1)
                        : 0
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Schedule error: ' . $e->getMessage());
            return $this->errorResponse('Failed to get schedule', 500);
        }
    }
    
    /**
     * Get attendance reports and analytics
     */
    public function getReports(Request $request)
    {
        try {
            $user = Auth::user();
            $period = $request->get('period', 'month'); // month, week, year
            $date = $request->get('date', Carbon::now()->format('Y-m-d'));
            
            $baseDate = Carbon::parse($date);
            
            // Determine date range based on period
            switch ($period) {
                case 'week':
                    $startDate = $baseDate->copy()->startOfWeek();
                    $endDate = $baseDate->copy()->endOfWeek();
                    break;
                case 'year':
                    $startDate = $baseDate->copy()->startOfYear();
                    $endDate = $baseDate->copy()->endOfYear();
                    break;
                default: // month
                    $startDate = $baseDate->copy()->startOfMonth();
                    $endDate = $baseDate->copy()->endOfMonth();
            }
            
            // Get attendance data for the period
            $attendances = NonParamedisAttendance::where('user_id', $user->id)
                ->whereBetween('attendance_date', [$startDate, $endDate])
                ->with('workLocation')
                ->orderBy('attendance_date', 'desc')
                ->get();
            
            // Calculate summary statistics
            $totalDays = $attendances->count();
            $workDays = $attendances->where('status', 'checked_out')->count();
            $approvedDays = $attendances->where('approval_status', 'approved')->count();
            $pendingDays = $attendances->where('approval_status', 'pending')->count();
            $rejectedDays = $attendances->where('approval_status', 'rejected')->count();
            
            $totalWorkMinutes = $attendances->sum('total_work_minutes');
            $totalWorkHours = round($totalWorkMinutes / 60, 1);
            $averageDailyHours = $workDays > 0 ? round($totalWorkMinutes / $workDays / 60, 1) : 0;
            
            // Expected work days (weekdays only)
            $expectedDays = $this->calculateExpectedWorkDays($startDate);
            $attendanceRate = $expectedDays > 0 ? round(($workDays / $expectedDays) * 100, 1) : 100;
            
            // Format recent history
            $recentHistory = [];
            foreach ($attendances->take(15) as $record) {
                $recentHistory[] = [
                    'date' => $record->attendance_date->format('d M Y'),
                    'day' => $record->attendance_date->locale('id')->dayName,
                    'check_in' => $record->check_in_time?->format('H:i'),
                    'check_out' => $record->check_out_time?->format('H:i'),
                    'duration' => $record->formatted_work_duration,
                    'location' => $record->workLocation?->name,
                    'status' => $record->status,
                    'approval_status' => $record->approval_status,
                    'location_valid' => $record->check_in_valid_location && $record->check_out_valid_location
                ];
            }
            
            return $this->successResponse('Reports retrieved successfully', [
                'period' => [
                    'type' => $period,
                    'start_date' => $startDate->format('Y-m-d'),
                    'end_date' => $endDate->format('Y-m-d'),
                    'display_name' => $this->getPeriodDisplayName($period, $baseDate)
                ],
                'summary' => [
                    'total_scheduled_days' => $totalDays,
                    'work_days_completed' => $workDays,
                    'expected_work_days' => $expectedDays,
                    'attendance_rate' => $attendanceRate,
                    'total_work_hours' => $totalWorkHours,
                    'average_daily_hours' => $averageDailyHours,
                    'approval_summary' => [
                        'approved' => $approvedDays,
                        'pending' => $pendingDays,
                        'rejected' => $rejectedDays
                    ]
                ],
                'recent_history' => $recentHistory,
                'performance_indicators' => [
                    'punctuality_score' => $this->calculatePunctualityScore($attendances),
                    'consistency_score' => $this->calculateConsistencyScore($attendances),
                    'location_compliance' => $this->calculateLocationCompliance($attendances)
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Reports error: ' . $e->getMessage());
            return $this->errorResponse('Failed to get reports', 500);
        }
    }
    
    /**
     * Get user profile data
     */
    public function getProfile(Request $request)
    {
        try {
            $user = Auth::user();
            
            // Get attendance statistics
            $currentMonth = Carbon::now()->startOfMonth();
            $totalAttendanceThisMonth = NonParamedisAttendance::where('user_id', $user->id)
                ->where('attendance_date', '>=', $currentMonth)
                ->count();
            
            $approvedAttendanceThisMonth = NonParamedisAttendance::where('user_id', $user->id)
                ->where('attendance_date', '>=', $currentMonth)
                ->where('approval_status', 'approved')
                ->count();
            
            return $this->successResponse('Profile retrieved successfully', [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'initials' => $this->getInitials($user->name),
                    'email' => $user->email,
                    'username' => $user->username,
                    'nip' => $user->nip,
                    'phone' => $user->no_telepon,
                    'role' => 'Administrator Non-Medis',
                    'join_date' => $user->tanggal_bergabung ? $user->tanggal_bergabung->format('d M Y') : null,
                    'is_verified' => true,
                    'status' => 'active'
                ],
                'attendance_stats' => [
                    'total_this_month' => $totalAttendanceThisMonth,
                    'approved_this_month' => $approvedAttendanceThisMonth,
                    'approval_rate' => $totalAttendanceThisMonth > 0 
                        ? round(($approvedAttendanceThisMonth / $totalAttendanceThisMonth) * 100, 1) 
                        : 100
                ],
                'settings' => [
                    'notifications_enabled' => true,
                    'dark_mode' => false,
                    'language' => 'id',
                    'auto_checkout' => false,
                    'gps_accuracy_required' => true
                ],
                'permissions' => [
                    'can_check_in' => true,
                    'can_check_out' => true,
                    'can_view_reports' => true,
                    'can_edit_profile' => false
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Profile error: ' . $e->getMessage());
            return $this->errorResponse('Failed to get profile', 500);
        }
    }
    
    /**
     * Helper methods
     */
    
    private function getInitials($name): string
    {
        $words = explode(' ', $name);
        $initials = '';
        
        foreach ($words as $word) {
            if (strlen($word) > 0) {
                $initials .= strtoupper($word[0]);
            }
            if (strlen($initials) >= 2) break;
        }
        
        return $initials ?: 'UN';
    }
    
    private function getPeriodDisplayName(string $period, Carbon $date): string
    {
        return match($period) {
            'week' => 'Minggu ' . $date->weekOfYear . ', ' . $date->year,
            'year' => 'Tahun ' . $date->year,
            default => $date->locale('id')->monthName . ' ' . $date->year
        };
    }
    
    private function calculatePunctualityScore($attendances): float
    {
        if ($attendances->isEmpty()) return 100.0;
        
        $onTimeCount = 0;
        $totalCount = $attendances->where('check_in_time', '!=', null)->count();
        
        foreach ($attendances as $attendance) {
            if (!$attendance->check_in_time) continue;
            
            // Consider on-time if check-in is before 08:15
            if ($attendance->check_in_time->format('H:i') <= '08:15') {
                $onTimeCount++;
            }
        }
        
        return $totalCount > 0 ? round(($onTimeCount / $totalCount) * 100, 1) : 100.0;
    }
    
    private function calculateConsistencyScore($attendances): float
    {
        if ($attendances->count() < 5) return 100.0;
        
        $workDurations = $attendances
            ->where('total_work_minutes', '>', 0)
            ->pluck('total_work_minutes')
            ->toArray();
        
        if (count($workDurations) < 2) return 100.0;
        
        $mean = array_sum($workDurations) / count($workDurations);
        $variance = array_sum(array_map(function($x) use ($mean) { 
            return pow($x - $mean, 2); 
        }, $workDurations)) / count($workDurations);
        
        $stdDev = sqrt($variance);
        $coefficient = $mean > 0 ? ($stdDev / $mean) : 0;
        
        // Convert to consistency score (lower coefficient = higher consistency)
        return max(0, round((1 - min($coefficient, 1)) * 100, 1));
    }
    
    private function calculateLocationCompliance($attendances): float
    {
        if ($attendances->isEmpty()) return 100.0;
        
        $validLocationCount = $attendances->where('check_in_valid_location', true)->count();
        $totalCount = $attendances->where('check_in_time', '!=', null)->count();
        
        return $totalCount > 0 ? round(($validLocationCount / $totalCount) * 100, 1) : 100.0;
    }
}