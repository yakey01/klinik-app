<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Models\NonParamedisAttendance;
use App\Models\Schedule;
use App\Models\Shift;
use App\Models\User;
use App\Models\WorkLocation;
use App\Services\GpsValidationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
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
     * Get work schedule for current month with admin-assigned shifts
     */
    public function getSchedule(Request $request)
    {
        try {
            $user = Auth::user();
            $month = $request->get('month', Carbon::now()->month);
            $year = $request->get('year', Carbon::now()->year);
            
            $startOfMonth = Carbon::create($year, $month, 1)->startOfMonth();
            $endOfMonth = Carbon::create($year, $month, 1)->endOfMonth();
            
            // Get assigned schedules from admin
            $schedules = Schedule::where('user_id', $user->id)
                ->whereBetween('date', [$startOfMonth, $endOfMonth])
                ->with(['shift', 'user'])
                ->orderBy('date')
                ->get();
            
            // Get actual attendance records for comparison
            $attendances = NonParamedisAttendance::where('user_id', $user->id)
                ->whereBetween('attendance_date', [$startOfMonth, $endOfMonth])
                ->with('workLocation')
                ->get()
                ->keyBy(function($item) {
                    return $item->attendance_date->format('Y-m-d');
                });
            
            // Format monthly calendar data with schedule and attendance comparison
            $monthlyCalendar = [];
            foreach ($schedules as $schedule) {
                $dateKey = $schedule->date->format('Y-m-d');
                $attendance = $attendances->get($dateKey);
                
                $calendarEntry = [
                    'date' => $dateKey,
                    'day_name' => $schedule->date->locale('id')->dayName,
                    'is_day_off' => $schedule->is_day_off,
                    'scheduled_shift' => $schedule->shift ? [
                        'name' => $schedule->shift->name,
                        'start_time' => $schedule->shift->start_time,
                        'end_time' => $schedule->shift->end_time,
                        'description' => $schedule->shift->description
                    ] : null,
                    'notes' => $schedule->notes,
                    'attendance_status' => $attendance ? [
                        'present' => true,
                        'check_in' => $attendance->check_in_time?->format('H:i'),
                        'check_out' => $attendance->check_out_time?->format('H:i'),
                        'work_duration' => $attendance->formatted_work_duration,
                        'status' => $attendance->status,
                        'approval_status' => $attendance->approval_status,
                        'on_time' => $this->isOnTime($attendance, $schedule->shift)
                    ] : [
                        'present' => false,
                        'status' => $schedule->date->isPast() ? 'absent' : 'upcoming'
                    ]
                ];
                
                $monthlyCalendar[] = $calendarEntry;
            }
            
            // Get current week detailed schedule
            $currentWeek = Carbon::now()->startOfWeek();
            $endOfWeek = Carbon::now()->endOfWeek();
            
            $weeklySchedules = Schedule::where('user_id', $user->id)
                ->whereBetween('date', [$currentWeek, $endOfWeek])
                ->with(['shift'])
                ->orderBy('date')
                ->get();
            
            $weeklyShifts = [];
            foreach ($weeklySchedules as $schedule) {
                $dateKey = $schedule->date->format('Y-m-d');
                $attendance = $attendances->get($dateKey);
                
                $weeklyShifts[] = [
                    'date' => $dateKey,
                    'day_name' => $schedule->date->locale('id')->dayName,
                    'is_today' => $schedule->date->isToday(),
                    'is_day_off' => $schedule->is_day_off,
                    'shift' => $schedule->shift ? [
                        'name' => $schedule->shift->name,
                        'start_time' => $schedule->shift->start_time,
                        'end_time' => $schedule->shift->end_time,
                        'duration_hours' => $this->calculateShiftDuration($schedule->shift)
                    ] : null,
                    'attendance' => $attendance ? [
                        'check_in' => $attendance->check_in_time?->format('H:i'),
                        'check_out' => $attendance->check_out_time?->format('H:i'),
                        'actual_duration' => $attendance->formatted_work_duration,
                        'status' => $attendance->status
                    ] : null,
                    'notes' => $schedule->notes
                ];
            }
            
            // Upcoming shifts (next 7 days)
            $nextWeek = Carbon::now()->addDay();
            $nextWeekEnd = Carbon::now()->addDays(7);
            
            $upcomingShifts = Schedule::where('user_id', $user->id)
                ->whereBetween('date', [$nextWeek, $nextWeekEnd])
                ->where('is_day_off', false)
                ->with(['shift'])
                ->orderBy('date')
                ->take(5)
                ->get()
                ->map(function($schedule) {
                    return [
                        'date' => $schedule->date->format('Y-m-d'),
                        'day_name' => $schedule->date->locale('id')->dayName,
                        'relative_date' => $schedule->date->locale('id')->diffForHumans(),
                        'shift_name' => $schedule->shift?->name,
                        'start_time' => $schedule->shift?->start_time,
                        'end_time' => $schedule->shift?->end_time,
                        'notes' => $schedule->notes
                    ];
                });
            
            // Schedule statistics
            $totalScheduledDays = $schedules->where('is_day_off', false)->count();
            $totalPresentDays = $attendances->where('status', 'checked_out')->count();
            $attendanceRate = $totalScheduledDays > 0 ? round(($totalPresentDays / $totalScheduledDays) * 100, 1) : 0;
            
            return $this->successResponse('Work schedule retrieved successfully', [
                'month' => [
                    'name' => $startOfMonth->locale('id')->monthName . ' ' . $year,
                    'year' => $year,
                    'total_days' => $schedules->count(),
                    'work_days_scheduled' => $totalScheduledDays,
                    'days_off' => $schedules->where('is_day_off', true)->count(),
                    'calendar' => $monthlyCalendar
                ],
                'current_week' => [
                    'week_start' => $currentWeek->format('Y-m-d'),
                    'week_end' => $endOfWeek->format('Y-m-d'),
                    'shifts' => $weeklyShifts
                ],
                'upcoming_shifts' => $upcomingShifts,
                'statistics' => [
                    'attendance_rate' => $attendanceRate,
                    'total_scheduled_hours' => $this->calculateTotalScheduledHours($schedules),
                    'total_worked_hours' => round($attendances->sum('total_work_minutes') / 60, 1),
                    'average_daily_hours' => $attendances->where('total_work_minutes', '>', 0)->count() > 0 
                        ? round($attendances->sum('total_work_minutes') / $attendances->where('total_work_minutes', '>', 0)->count() / 60, 1)
                        : 0
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Schedule error: ' . $e->getMessage());
            return $this->errorResponse('Failed to get work schedule', 500);
        }
    }
    
    /**
     * Check if attendance was on time based on scheduled shift
     */
    private function isOnTime($attendance, $shift): bool
    {
        if (!$attendance->check_in_time || !$shift) {
            return false;
        }
        
        $scheduledStart = Carbon::parse($shift->start_time);
        $actualStart = $attendance->check_in_time;
        
        // Allow 15 minutes grace period
        return $actualStart->diffInMinutes($scheduledStart, false) <= 15;
    }
    
    /**
     * Calculate shift duration in hours
     */
    private function calculateShiftDuration($shift): float
    {
        if (!$shift) return 0;
        
        $start = Carbon::parse($shift->start_time);
        $end = Carbon::parse($shift->end_time);
        
        // Handle overnight shifts
        if ($end->lessThan($start)) {
            $end->addDay();
        }
        
        return round($start->diffInHours($end), 1);
    }
    
    /**
     * Calculate total scheduled hours for the period
     */
    private function calculateTotalScheduledHours($schedules): float
    {
        $totalHours = 0;
        
        foreach ($schedules as $schedule) {
            if (!$schedule->is_day_off && $schedule->shift) {
                $totalHours += $this->calculateShiftDuration($schedule->shift);
            }
        }
        
        return $totalHours;
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
    
    /**
     * Update user profile information
     */
    public function updateProfile(Request $request)
    {
        try {
            $user = Auth::user();
            
            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string|max:255',
                'email' => 'sometimes|required|email|unique:users,email,' . $user->id,
                'phone' => 'sometimes|nullable|string|max:20',
                'address' => 'sometimes|nullable|string|max:500',
                'bio' => 'sometimes|nullable|string|max:1000',
                'date_of_birth' => 'sometimes|nullable|date',
                'gender' => 'sometimes|nullable|in:male,female',
                'emergency_contact_name' => 'sometimes|nullable|string|max:255',
                'emergency_contact_phone' => 'sometimes|nullable|string|max:20',
            ]);
            
            if ($validator->fails()) {
                return $this->errorResponse('Validation failed', 422, $validator->errors());
            }
            
            $updateData = $request->only([
                'name', 'email', 'phone', 'address', 'bio', 
                'date_of_birth', 'gender', 'emergency_contact_name', 'emergency_contact_phone'
            ]);
            
            $user->update($updateData);
            
            // Log profile update
            Log::info('Profile updated', [
                'user_id' => $user->id,
                'updated_fields' => array_keys($updateData),
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent')
            ]);
            
            return $this->successResponse('Profile updated successfully', [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'address' => $user->address,
                    'bio' => $user->bio,
                    'date_of_birth' => $user->date_of_birth,
                    'gender' => $user->gender,
                    'emergency_contact_name' => $user->emergency_contact_name,
                    'emergency_contact_phone' => $user->emergency_contact_phone,
                    'updated_at' => $user->updated_at->format('Y-m-d H:i:s')
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Profile update error: ' . $e->getMessage());
            return $this->errorResponse('Failed to update profile', 500);
        }
    }
    
    /**
     * Change user password
     */
    public function changePassword(Request $request)
    {
        try {
            $user = Auth::user();
            
            $validator = Validator::make($request->all(), [
                'current_password' => 'required|string',
                'new_password' => 'required|string|min:8|confirmed',
                'new_password_confirmation' => 'required|string'
            ]);
            
            if ($validator->fails()) {
                return $this->errorResponse('Validation failed', 422, $validator->errors());
            }
            
            // Verify current password
            if (!Hash::check($request->current_password, $user->password)) {
                return $this->errorResponse('Current password is incorrect', 422, [
                    'current_password' => ['Current password is incorrect']
                ]);
            }
            
            // Update password
            $user->update([
                'password' => Hash::make($request->new_password)
            ]);
            
            // Revoke all other tokens for security
            $user->tokens()->where('id', '!=', $user->currentAccessToken()->id)->delete();
            
            // Log password change
            Log::info('Password changed', [
                'user_id' => $user->id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent')
            ]);
            
            return $this->successResponse('Password changed successfully', [
                'message' => 'Password updated successfully. Other sessions have been logged out for security.',
                'tokens_revoked' => true
            ]);
            
        } catch (\Exception $e) {
            Log::error('Password change error: ' . $e->getMessage());
            return $this->errorResponse('Failed to change password', 500);
        }
    }
    
    /**
     * Upload profile photo
     */
    public function uploadPhoto(Request $request)
    {
        try {
            $user = Auth::user();
            
            $validator = Validator::make($request->all(), [
                'photo' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048' // 2MB max
            ]);
            
            if ($validator->fails()) {
                return $this->errorResponse('Validation failed', 422, $validator->errors());
            }
            
            $photo = $request->file('photo');
            
            // Create storage directory if it doesn't exist
            $uploadPath = storage_path('app/public/profile-photos');
            if (!file_exists($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }
            
            // Generate unique filename
            $filename = 'profile_' . $user->id . '_' . time() . '.' . $photo->getClientOriginalExtension();
            
            // Store the photo
            $photo->storeAs('public/profile-photos', $filename);
            
            // Delete old photo if exists
            if ($user->profile_photo_path) {
                $oldPhotoPath = storage_path('app/public/' . $user->profile_photo_path);
                if (file_exists($oldPhotoPath)) {
                    unlink($oldPhotoPath);
                }
            }
            
            // Update user record
            $user->update([
                'profile_photo_path' => 'profile-photos/' . $filename
            ]);
            
            // Log photo upload
            Log::info('Profile photo uploaded', [
                'user_id' => $user->id,
                'filename' => $filename,
                'ip_address' => $request->ip()
            ]);
            
            return $this->successResponse('Profile photo uploaded successfully', [
                'photo_url' => asset('storage/profile-photos/' . $filename),
                'filename' => $filename,
                'uploaded_at' => now()->format('Y-m-d H:i:s')
            ]);
            
        } catch (\Exception $e) {
            Log::error('Photo upload error: ' . $e->getMessage());
            return $this->errorResponse('Failed to upload photo', 500);
        }
    }
    
    /**
     * Get user settings
     */
    public function getSettings(Request $request)
    {
        try {
            $user = Auth::user();
            
            // Get user preferences (you can extend this)
            $settings = [
                'notifications' => [
                    'email_notifications' => $user->email_notifications ?? true,
                    'push_notifications' => $user->push_notifications ?? true,
                    'attendance_reminders' => $user->attendance_reminders ?? true,
                    'schedule_updates' => $user->schedule_updates ?? true,
                ],
                'privacy' => [
                    'profile_visibility' => $user->profile_visibility ?? 'colleagues',
                    'location_sharing' => $user->location_sharing ?? true,
                    'activity_status' => $user->activity_status ?? true,
                ],
                'work' => [
                    'default_work_location' => $user->default_work_location_id,
                    'auto_check_out' => $user->auto_check_out ?? false,
                    'overtime_alerts' => $user->overtime_alerts ?? true,
                ],
                'app' => [
                    'language' => $user->language ?? 'id',
                    'timezone' => $user->timezone ?? 'Asia/Jakarta',
                    'theme' => $user->theme ?? 'light',
                ]
            ];
            
            return $this->successResponse('Settings retrieved successfully', [
                'settings' => $settings,
                'available_work_locations' => WorkLocation::active()->get(['id', 'name', 'address']),
                'available_languages' => [
                    ['code' => 'id', 'name' => 'Bahasa Indonesia'],
                    ['code' => 'en', 'name' => 'English']
                ],
                'available_themes' => ['light', 'dark', 'auto']
            ]);
            
        } catch (\Exception $e) {
            Log::error('Settings retrieval error: ' . $e->getMessage());
            return $this->errorResponse('Failed to get settings', 500);
        }
    }
    
    /**
     * Update user settings
     */
    public function updateSettings(Request $request)
    {
        try {
            $user = Auth::user();
            
            $validator = Validator::make($request->all(), [
                'notifications.email_notifications' => 'sometimes|boolean',
                'notifications.push_notifications' => 'sometimes|boolean',
                'notifications.attendance_reminders' => 'sometimes|boolean',
                'notifications.schedule_updates' => 'sometimes|boolean',
                'privacy.profile_visibility' => 'sometimes|in:public,colleagues,private',
                'privacy.location_sharing' => 'sometimes|boolean',
                'privacy.activity_status' => 'sometimes|boolean',
                'work.default_work_location' => 'sometimes|nullable|exists:work_locations,id',
                'work.auto_check_out' => 'sometimes|boolean',
                'work.overtime_alerts' => 'sometimes|boolean',
                'app.language' => 'sometimes|in:id,en',
                'app.timezone' => 'sometimes|string',
                'app.theme' => 'sometimes|in:light,dark,auto',
            ]);
            
            if ($validator->fails()) {
                return $this->errorResponse('Validation failed', 422, $validator->errors());
            }
            
            $settings = $request->all();
            $updateData = [];
            
            // Map settings to user fields
            if (isset($settings['notifications'])) {
                $updateData = array_merge($updateData, [
                    'email_notifications' => $settings['notifications']['email_notifications'] ?? $user->email_notifications,
                    'push_notifications' => $settings['notifications']['push_notifications'] ?? $user->push_notifications,
                    'attendance_reminders' => $settings['notifications']['attendance_reminders'] ?? $user->attendance_reminders,
                    'schedule_updates' => $settings['notifications']['schedule_updates'] ?? $user->schedule_updates,
                ]);
            }
            
            if (isset($settings['privacy'])) {
                $updateData = array_merge($updateData, [
                    'profile_visibility' => $settings['privacy']['profile_visibility'] ?? $user->profile_visibility,
                    'location_sharing' => $settings['privacy']['location_sharing'] ?? $user->location_sharing,
                    'activity_status' => $settings['privacy']['activity_status'] ?? $user->activity_status,
                ]);
            }
            
            if (isset($settings['work'])) {
                $updateData = array_merge($updateData, [
                    'default_work_location_id' => $settings['work']['default_work_location'] ?? $user->default_work_location_id,
                    'auto_check_out' => $settings['work']['auto_check_out'] ?? $user->auto_check_out,
                    'overtime_alerts' => $settings['work']['overtime_alerts'] ?? $user->overtime_alerts,
                ]);
            }
            
            if (isset($settings['app'])) {
                $updateData = array_merge($updateData, [
                    'language' => $settings['app']['language'] ?? $user->language,
                    'timezone' => $settings['app']['timezone'] ?? $user->timezone,
                    'theme' => $settings['app']['theme'] ?? $user->theme,
                ]);
            }
            
            $user->update($updateData);
            
            // Log settings update
            Log::info('Settings updated', [
                'user_id' => $user->id,
                'updated_settings' => array_keys($updateData),
                'ip_address' => $request->ip()
            ]);
            
            return $this->successResponse('Settings updated successfully', [
                'updated_fields' => array_keys($updateData),
                'updated_at' => now()->format('Y-m-d H:i:s')
            ]);
            
        } catch (\Exception $e) {
            Log::error('Settings update error: ' . $e->getMessage());
            return $this->errorResponse('Failed to update settings', 500);
        }
    }
}