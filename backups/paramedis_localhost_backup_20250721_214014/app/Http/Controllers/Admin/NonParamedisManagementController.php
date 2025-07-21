<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Role;
use App\Models\Schedule;
use App\Models\Shift;
use App\Models\NonParamedisAttendance;
use App\Models\WorkLocation;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class NonParamedisManagementController extends Controller
{
    /**
     * Display a listing of NonParamedis users
     */
    public function index(Request $request)
    {
        $query = User::with(['role', 'nonParamedisAttendances' => function($q) {
            $q->where('attendance_date', '>=', now()->subMonth());
        }])->byRole('non_paramedis');

        // Filter by status
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->active();
            } else {
                $query->where('is_active', false);
            }
        }

        // Search by name or email
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('nip', 'like', "%{$search}%");
            });
        }

        $users = $query->paginate(20);

        return response()->json([
            'success' => true,
            'message' => 'NonParamedis users retrieved',
            'data' => [
                'users' => $users->items(),
                'pagination' => [
                    'current_page' => $users->currentPage(),
                    'last_page' => $users->lastPage(),
                    'per_page' => $users->perPage(),
                    'total' => $users->total(),
                    'from' => $users->firstItem(),
                    'to' => $users->lastItem(),
                ]
            ],
            'meta' => [
                'timestamp' => now()->toISOString(),
                'request_id' => \Illuminate\Support\Str::uuid()->toString(),
            ]
        ]);
    }

    /**
     * Display the specified NonParamedis user
     */
    public function show(User $user)
    {
        if (!$user->hasRole('non_paramedis')) {
            return response()->json([
                'success' => false,
                'message' => 'User is not a NonParamedis',
            ], 404);
        }

        $user->load(['role', 'nonParamedisAttendances' => function($q) {
            $q->orderBy('attendance_date', 'desc')->limit(30);
        }]);

        // Calculate statistics
        $thisMonth = now()->startOfMonth();
        $attendanceStats = NonParamedisAttendance::where('user_id', $user->id)
            ->where('attendance_date', '>=', $thisMonth)
            ->selectRaw('
                COUNT(*) as total_days,
                SUM(CASE WHEN status = "present" THEN 1 ELSE 0 END) as present_days,
                SUM(CASE WHEN status = "late" THEN 1 ELSE 0 END) as late_days,
                SUM(CASE WHEN status = "absent" THEN 1 ELSE 0 END) as absent_days,
                AVG(CASE WHEN check_in_time IS NOT NULL THEN TIME_TO_SEC(check_in_time) END) as avg_check_in_seconds
            ')
            ->first();

        $avgCheckInTime = null;
        if ($attendanceStats->avg_check_in_seconds) {
            $avgCheckInTime = gmdate('H:i:s', $attendanceStats->avg_check_in_seconds);
        }

        return response()->json([
            'success' => true,
            'message' => 'NonParamedis user details retrieved',
            'data' => [
                'user' => $user,
                'statistics' => [
                    'total_days' => $attendanceStats->total_days ?? 0,
                    'present_days' => $attendanceStats->present_days ?? 0,
                    'late_days' => $attendanceStats->late_days ?? 0,
                    'absent_days' => $attendanceStats->absent_days ?? 0,
                    'attendance_rate' => $attendanceStats->total_days > 0 
                        ? round(($attendanceStats->present_days + $attendanceStats->late_days) / $attendanceStats->total_days * 100, 2) 
                        : 0,
                    'avg_check_in_time' => $avgCheckInTime,
                ],
            ],
            'meta' => [
                'timestamp' => now()->toISOString(),
                'request_id' => \Illuminate\Support\Str::uuid()->toString(),
            ]
        ]);
    }

    /**
     * Create a new NonParamedis user
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'username' => 'required|string|max:255|unique:users',
            'password' => ['required', Password::defaults()],
            'nip' => 'required|string|max:50|unique:users',
            'no_telepon' => 'nullable|string|max:20',
            'tanggal_bergabung' => 'required|date',
            'is_active' => 'boolean',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'bio' => 'nullable|string|max:1000',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|in:male,female',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $nonParamedisRole = Role::where('name', 'non_paramedis')->first();
        if (!$nonParamedisRole) {
            return response()->json([
                'success' => false,
                'message' => 'NonParamedis role not found',
            ], 500);
        }

        $userData = $request->all();
        $userData['role_id'] = $nonParamedisRole->id;
        $userData['password'] = Hash::make($request->password);
        $userData['is_active'] = $request->get('is_active', true);

        $user = User::create($userData);
        $user->load('role');

        return response()->json([
            'success' => true,
            'message' => 'NonParamedis user created successfully',
            'data' => ['user' => $user],
            'meta' => [
                'timestamp' => now()->toISOString(),
                'request_id' => \Illuminate\Support\Str::uuid()->toString(),
            ]
        ], 201);
    }

    /**
     * Update the specified NonParamedis user
     */
    public function update(Request $request, User $user)
    {
        if (!$user->hasRole('non_paramedis')) {
            return response()->json([
                'success' => false,
                'message' => 'User is not a NonParamedis',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $user->id,
            'username' => 'sometimes|required|string|max:255|unique:users,username,' . $user->id,
            'nip' => 'sometimes|required|string|max:50|unique:users,nip,' . $user->id,
            'no_telepon' => 'nullable|string|max:20',
            'tanggal_bergabung' => 'sometimes|required|date',
            'is_active' => 'boolean',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'bio' => 'nullable|string|max:1000',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|in:male,female',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user->update($request->all());
        $user->load('role');

        return response()->json([
            'success' => true,
            'message' => 'NonParamedis user updated successfully',
            'data' => ['user' => $user],
            'meta' => [
                'timestamp' => now()->toISOString(),
                'request_id' => \Illuminate\Support\Str::uuid()->toString(),
            ]
        ]);
    }

    /**
     * Toggle user active status
     */
    public function toggleStatus(User $user)
    {
        if (!$user->hasRole('non_paramedis')) {
            return response()->json([
                'success' => false,
                'message' => 'User is not a NonParamedis',
            ], 404);
        }

        $user->update(['is_active' => !$user->is_active]);

        return response()->json([
            'success' => true,
            'message' => 'User status updated successfully',
            'data' => [
                'user' => $user,
                'new_status' => $user->is_active ? 'active' : 'inactive',
            ],
            'meta' => [
                'timestamp' => now()->toISOString(),
                'request_id' => \Illuminate\Support\Str::uuid()->toString(),
            ]
        ]);
    }

    /**
     * Reset user password
     */
    public function resetPassword(Request $request, User $user)
    {
        if (!$user->hasRole('non_paramedis')) {
            return response()->json([
                'success' => false,
                'message' => 'User is not a NonParamedis',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'password' => ['required', Password::defaults()],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Password reset successfully',
            'meta' => [
                'timestamp' => now()->toISOString(),
                'request_id' => \Illuminate\Support\Str::uuid()->toString(),
            ]
        ]);
    }

    /**
     * Get user attendance history
     */
    public function getAttendanceHistory(Request $request, User $user)
    {
        if (!$user->hasRole('non_paramedis')) {
            return response()->json([
                'success' => false,
                'message' => 'User is not a NonParamedis',
            ], 404);
        }

        $query = NonParamedisAttendance::where('user_id', $user->id);

        // Filter by date range
        if ($request->filled('start_date')) {
            $query->where('attendance_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->where('attendance_date', '<=', $request->end_date);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $attendances = $query->orderBy('attendance_date', 'desc')->paginate(30);

        return response()->json([
            'success' => true,
            'message' => 'Attendance history retrieved',
            'data' => [
                'attendances' => $attendances->items(),
                'pagination' => [
                    'current_page' => $attendances->currentPage(),
                    'last_page' => $attendances->lastPage(),
                    'per_page' => $attendances->perPage(),
                    'total' => $attendances->total(),
                    'from' => $attendances->firstItem(),
                    'to' => $attendances->lastItem(),
                ]
            ],
            'meta' => [
                'timestamp' => now()->toISOString(),
                'request_id' => \Illuminate\Support\Str::uuid()->toString(),
            ]
        ]);
    }

    /**
     * Get user schedule
     */
    public function getSchedule(Request $request, User $user)
    {
        if (!$user->hasRole('non_paramedis')) {
            return response()->json([
                'success' => false,
                'message' => 'User is not a NonParamedis',
            ], 404);
        }

        $startDate = $request->get('start_date', now()->startOfMonth());
        $endDate = $request->get('end_date', now()->endOfMonth());

        $schedules = Schedule::where('user_id', $user->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->with(['shift', 'user'])
            ->orderBy('date')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'User schedule retrieved',
            'data' => [
                'schedules' => $schedules,
                'summary' => [
                    'total_days' => $schedules->count(),
                    'work_days' => $schedules->where('shift_id', '!=', null)->count(),
                    'off_days' => $schedules->where('shift_id', null)->count(),
                ]
            ],
            'meta' => [
                'timestamp' => now()->toISOString(),
                'request_id' => \Illuminate\Support\Str::uuid()->toString(),
            ]
        ]);
    }

    /**
     * Assign schedule to user
     */
    public function assignSchedule(Request $request, User $user)
    {
        if (!$user->hasRole('non_paramedis')) {
            return response()->json([
                'success' => false,
                'message' => 'User is not a NonParamedis',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'schedules' => 'required|array',
            'schedules.*.date' => 'required|date',
            'schedules.*.shift_id' => 'nullable|exists:shifts,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $createdSchedules = [];
        foreach ($request->schedules as $scheduleData) {
            $schedule = Schedule::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'date' => $scheduleData['date'],
                ],
                [
                    'shift_id' => $scheduleData['shift_id'] ?? null,
                    'created_by' => auth()->id(),
                ]
            );
            $schedule->load('shift');
            $createdSchedules[] = $schedule;
        }

        return response()->json([
            'success' => true,
            'message' => 'Schedule assigned successfully',
            'data' => ['schedules' => $createdSchedules],
            'meta' => [
                'timestamp' => now()->toISOString(),
                'request_id' => \Illuminate\Support\Str::uuid()->toString(),
            ]
        ]);
    }

    /**
     * Get available shifts for scheduling
     */
    public function getAvailableShifts()
    {
        $shifts = Shift::orderBy('start_time')->get();

        return response()->json([
            'success' => true,
            'message' => 'Available shifts retrieved',
            'data' => ['shifts' => $shifts],
            'meta' => [
                'timestamp' => now()->toISOString(),
                'request_id' => \Illuminate\Support\Str::uuid()->toString(),
            ]
        ]);
    }

    /**
     * Get dashboard statistics
     */
    public function getDashboardStats()
    {
        $today = now()->startOfDay();
        $thisMonth = now()->startOfMonth();
        
        $stats = [
            'total_users' => User::byRole('non_paramedis')->count(),
            'active_users' => User::byRole('non_paramedis')->active()->count(),
            'inactive_users' => User::byRole('non_paramedis')->where('is_active', false)->count(),
            'today_present' => NonParamedisAttendance::where('attendance_date', $today->format('Y-m-d'))
                ->where('status', 'present')
                ->count(),
            'today_late' => NonParamedisAttendance::where('attendance_date', $today->format('Y-m-d'))
                ->where('status', 'late')
                ->count(),
            'today_absent' => NonParamedisAttendance::where('attendance_date', $today->format('Y-m-d'))
                ->where('status', 'absent')
                ->count(),
            'month_attendance_rate' => $this->calculateMonthlyAttendanceRate($thisMonth),
        ];

        return response()->json([
            'success' => true,
            'message' => 'Dashboard statistics retrieved',
            'data' => ['statistics' => $stats],
            'meta' => [
                'timestamp' => now()->toISOString(),
                'request_id' => \Illuminate\Support\Str::uuid()->toString(),
            ]
        ]);
    }

    /**
     * Calculate monthly attendance rate
     */
    private function calculateMonthlyAttendanceRate($month)
    {
        $totalWorkingDays = NonParamedisAttendance::where('attendance_date', '>=', $month)
            ->where('attendance_date', '<', $month->copy()->addMonth())
            ->count();

        if ($totalWorkingDays === 0) {
            return 0;
        }

        $presentDays = NonParamedisAttendance::where('attendance_date', '>=', $month)
            ->where('attendance_date', '<', $month->copy()->addMonth())
            ->whereIn('status', ['present', 'late'])
            ->count();

        return round(($presentDays / $totalWorkingDays) * 100, 2);
    }
}
