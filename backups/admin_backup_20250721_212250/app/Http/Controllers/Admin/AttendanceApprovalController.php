<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\NonParamedisAttendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class AttendanceApprovalController extends Controller
{
    /**
     * Get pending attendance approvals
     */
    public function getPendingApprovals(Request $request)
    {
        $query = NonParamedisAttendance::with(['user'])
            ->where('status', 'pending')
            ->whereNull('approved_at')
            ->whereNull('approved_by');

        // Filter by date range
        if ($request->filled('start_date')) {
            $query->where('attendance_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->where('attendance_date', '<=', $request->end_date);
        }

        // Filter by user
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $attendances = $query->orderBy('attendance_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'message' => 'Pending approvals retrieved',
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
     * Get attendance history with approval status
     */
    public function getAttendanceHistory(Request $request)
    {
        $query = NonParamedisAttendance::with(['user', 'approvedBy'])
            ->whereNotNull('approved_at');

        // Filter by approval status
        if ($request->filled('approval_status')) {
            $query->where('approval_status', $request->approval_status);
        }

        // Filter by date range
        if ($request->filled('start_date')) {
            $query->where('attendance_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->where('attendance_date', '<=', $request->end_date);
        }

        // Filter by user
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $attendances = $query->orderBy('approved_at', 'desc')
            ->paginate(20);

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
     * Approve attendance record
     */
    public function approveAttendance(Request $request, NonParamedisAttendance $attendance)
    {
        $validator = Validator::make($request->all(), [
            'approval_notes' => 'nullable|string|max:500',
            'override_status' => 'nullable|in:present,late,absent',
            'override_check_in' => 'nullable|date_format:H:i:s',
            'override_check_out' => 'nullable|date_format:H:i:s',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        if ($attendance->approved_at) {
            return response()->json([
                'success' => false,
                'message' => 'Attendance already processed',
            ], 400);
        }

        DB::transaction(function () use ($request, $attendance) {
            $updateData = [
                'approval_status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'approval_notes' => $request->approval_notes,
            ];

            // Apply admin overrides if provided
            if ($request->filled('override_status')) {
                $updateData['status'] = $request->override_status;
                $updateData['admin_override'] = true;
            }

            if ($request->filled('override_check_in')) {
                $updateData['check_in_time'] = $request->override_check_in;
                $updateData['admin_override'] = true;
            }

            if ($request->filled('override_check_out')) {
                $updateData['check_out_time'] = $request->override_check_out;
                $updateData['admin_override'] = true;
            }

            $attendance->update($updateData);
        });

        $attendance->load(['user', 'approvedBy']);

        return response()->json([
            'success' => true,
            'message' => 'Attendance approved successfully',
            'data' => ['attendance' => $attendance],
            'meta' => [
                'timestamp' => now()->toISOString(),
                'request_id' => \Illuminate\Support\Str::uuid()->toString(),
            ]
        ]);
    }

    /**
     * Reject attendance record
     */
    public function rejectAttendance(Request $request, NonParamedisAttendance $attendance)
    {
        $validator = Validator::make($request->all(), [
            'rejection_reason' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        if ($attendance->approved_at) {
            return response()->json([
                'success' => false,
                'message' => 'Attendance already processed',
            ], 400);
        }

        $attendance->update([
            'approval_status' => 'rejected',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'approval_notes' => $request->rejection_reason,
        ]);

        $attendance->load(['user', 'approvedBy']);

        return response()->json([
            'success' => true,
            'message' => 'Attendance rejected successfully',
            'data' => ['attendance' => $attendance],
            'meta' => [
                'timestamp' => now()->toISOString(),
                'request_id' => \Illuminate\Support\Str::uuid()->toString(),
            ]
        ]);
    }

    /**
     * Bulk approve attendances
     */
    public function bulkApprove(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'attendance_ids' => 'required|array',
            'attendance_ids.*' => 'required|exists:non_paramedis_attendances,id',
            'approval_notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $attendanceIds = $request->attendance_ids;
        $approvedCount = 0;
        $errors = [];

        DB::transaction(function () use ($request, $attendanceIds, &$approvedCount, &$errors) {
            foreach ($attendanceIds as $attendanceId) {
                $attendance = NonParamedisAttendance::find($attendanceId);
                
                if (!$attendance) {
                    $errors[] = "Attendance {$attendanceId} not found";
                    continue;
                }

                if ($attendance->approved_at) {
                    $errors[] = "Attendance {$attendanceId} already processed";
                    continue;
                }

                $attendance->update([
                    'approval_status' => 'approved',
                    'approved_by' => auth()->id(),
                    'approved_at' => now(),
                    'approval_notes' => $request->approval_notes,
                ]);

                $approvedCount++;
            }
        });

        return response()->json([
            'success' => true,
            'message' => "Successfully approved {$approvedCount} attendance records",
            'data' => [
                'approved_count' => $approvedCount,
                'errors' => $errors,
            ],
            'meta' => [
                'timestamp' => now()->toISOString(),
                'request_id' => \Illuminate\Support\Str::uuid()->toString(),
            ]
        ]);
    }

    /**
     * Bulk reject attendances
     */
    public function bulkReject(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'attendance_ids' => 'required|array',
            'attendance_ids.*' => 'required|exists:non_paramedis_attendances,id',
            'rejection_reason' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $attendanceIds = $request->attendance_ids;
        $rejectedCount = 0;
        $errors = [];

        DB::transaction(function () use ($request, $attendanceIds, &$rejectedCount, &$errors) {
            foreach ($attendanceIds as $attendanceId) {
                $attendance = NonParamedisAttendance::find($attendanceId);
                
                if (!$attendance) {
                    $errors[] = "Attendance {$attendanceId} not found";
                    continue;
                }

                if ($attendance->approved_at) {
                    $errors[] = "Attendance {$attendanceId} already processed";
                    continue;
                }

                $attendance->update([
                    'approval_status' => 'rejected',
                    'approved_by' => auth()->id(),
                    'approved_at' => now(),
                    'approval_notes' => $request->rejection_reason,
                ]);

                $rejectedCount++;
            }
        });

        return response()->json([
            'success' => true,
            'message' => "Successfully rejected {$rejectedCount} attendance records",
            'data' => [
                'rejected_count' => $rejectedCount,
                'errors' => $errors,
            ],
            'meta' => [
                'timestamp' => now()->toISOString(),
                'request_id' => \Illuminate\Support\Str::uuid()->toString(),
            ]
        ]);
    }

    /**
     * Get approval statistics
     */
    public function getApprovalStats()
    {
        $today = now()->startOfDay();
        $thisMonth = now()->startOfMonth();
        
        $stats = [
            'pending_approvals' => NonParamedisAttendance::where('status', 'pending')
                ->whereNull('approved_at')
                ->count(),
            'today_pending' => NonParamedisAttendance::where('attendance_date', $today->format('Y-m-d'))
                ->where('status', 'pending')
                ->whereNull('approved_at')
                ->count(),
            'month_approved' => NonParamedisAttendance::where('attendance_date', '>=', $thisMonth)
                ->where('approval_status', 'approved')
                ->count(),
            'month_rejected' => NonParamedisAttendance::where('attendance_date', '>=', $thisMonth)
                ->where('approval_status', 'rejected')
                ->count(),
            'avg_approval_time' => $this->calculateAverageApprovalTime(),
        ];

        return response()->json([
            'success' => true,
            'message' => 'Approval statistics retrieved',
            'data' => ['statistics' => $stats],
            'meta' => [
                'timestamp' => now()->toISOString(),
                'request_id' => \Illuminate\Support\Str::uuid()->toString(),
            ]
        ]);
    }

    /**
     * Calculate average approval time in hours
     */
    private function calculateAverageApprovalTime()
    {
        $approvedAttendances = NonParamedisAttendance::whereNotNull('approved_at')
            ->where('approved_at', '>=', now()->subMonth())
            ->get();

        if ($approvedAttendances->isEmpty()) {
            return 0;
        }

        $totalHours = 0;
        $count = 0;

        foreach ($approvedAttendances as $attendance) {
            $createdAt = Carbon::parse($attendance->created_at);
            $approvedAt = Carbon::parse($attendance->approved_at);
            $diffInHours = $createdAt->diffInHours($approvedAt);
            
            $totalHours += $diffInHours;
            $count++;
        }

        return $count > 0 ? round($totalHours / $count, 2) : 0;
    }
}
