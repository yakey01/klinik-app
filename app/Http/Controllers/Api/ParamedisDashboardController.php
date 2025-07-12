<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Jaspel;
use App\Models\Tindakan;
use App\Models\Notification;
use App\Models\Schedule;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ParamedisDashboardController extends Controller
{
    /**
     * Get dashboard summary for paramedis
     */
    public function dashboard(Request $request)
    {
        $user = $request->user();
        $today = Carbon::today();
        
        // Get attendance status
        $attendance = Attendance::where('user_id', $user->id)
            ->where('date', $today)
            ->first();
            
        // Get today's schedule
        $schedule = Schedule::where('user_id', $user->id)
            ->where('date', $today)
            ->with('shift')
            ->first();
            
        // Get performance stats for current month
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();
        
        // Count procedures performed
        $procedureCount = Tindakan::where('user_id', $user->id)
            ->whereBetween('tanggal', [$startOfMonth, $endOfMonth])
            ->count();
            
        // Calculate total jaspel earned
        $totalJaspel = Jaspel::where('user_id', $user->id)
            ->whereBetween('tanggal', [$startOfMonth, $endOfMonth])
            ->where('status', 'approved')
            ->sum('jumlah');
            
        // Get attendance summary
        $attendanceSummary = Attendance::where('user_id', $user->id)
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->select(
                DB::raw('COUNT(*) as total_days'),
                DB::raw('SUM(CASE WHEN status = "present" THEN 1 ELSE 0 END) as present_days'),
                DB::raw('SUM(CASE WHEN status = "late" THEN 1 ELSE 0 END) as late_days'),
                DB::raw('SUM(CASE WHEN status = "absent" THEN 1 ELSE 0 END) as absent_days')
            )
            ->first();
            
        // Get recent notifications
        $notifications = $user->notifications()
            ->latest()
            ->limit(5)
            ->get()
            ->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'type' => $notification->type,
                    'title' => $notification->data['title'] ?? 'Notification',
                    'message' => $notification->data['message'] ?? '',
                    'read' => !is_null($notification->read_at),
                    'created_at' => $notification->created_at->diffForHumans(),
                ];
            });
            
        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role->name,
                    'avatar' => $user->avatar_url ?? null,
                ],
                'attendance' => [
                    'status' => $attendance ? $attendance->status : 'not_checked_in',
                    'checked_in' => $attendance ? true : false,
                    'checked_out' => $attendance && $attendance->time_out ? true : false,
                    'time_in' => $attendance ? $attendance->time_in : null,
                    'time_out' => $attendance ? $attendance->time_out : null,
                    'work_duration' => $attendance ? $attendance->formatted_work_duration : null,
                ],
                'schedule' => $schedule ? [
                    'shift' => $schedule->shift->name,
                    'start_time' => $schedule->shift->start_time,
                    'end_time' => $schedule->shift->end_time,
                    'is_day_off' => $schedule->is_day_off,
                ] : null,
                'performance' => [
                    'procedures_count' => $procedureCount,
                    'total_jaspel' => $totalJaspel,
                    'attendance_rate' => $attendanceSummary->total_days > 0 
                        ? round(($attendanceSummary->present_days / $attendanceSummary->total_days) * 100, 1)
                        : 0,
                    'present_days' => $attendanceSummary->present_days ?? 0,
                    'late_days' => $attendanceSummary->late_days ?? 0,
                    'absent_days' => $attendanceSummary->absent_days ?? 0,
                ],
                'notifications' => $notifications,
                'quick_stats' => [
                    'patients_today' => Tindakan::where('user_id', $user->id)
                        ->where('tanggal', $today)
                        ->count(),
                    'pending_jaspel' => Jaspel::where('user_id', $user->id)
                        ->where('status', 'pending')
                        ->count(),
                ],
            ],
        ]);
    }
    
    /**
     * Get detailed schedule for paramedis
     */
    public function schedule(Request $request)
    {
        $user = $request->user();
        $date = $request->input('date', Carbon::today()->format('Y-m-d'));
        $startDate = Carbon::parse($date)->startOfWeek();
        $endDate = Carbon::parse($date)->endOfWeek();
        
        $schedules = Schedule::where('user_id', $user->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->with('shift')
            ->get()
            ->map(function ($schedule) {
                return [
                    'date' => $schedule->date->format('Y-m-d'),
                    'day' => $schedule->date->format('l'),
                    'shift' => $schedule->shift ? [
                        'name' => $schedule->shift->name,
                        'start_time' => $schedule->shift->start_time,
                        'end_time' => $schedule->shift->end_time,
                    ] : null,
                    'is_day_off' => $schedule->is_day_off,
                    'notes' => $schedule->notes,
                ];
            });
            
        return response()->json([
            'success' => true,
            'data' => [
                'week_start' => $startDate->format('Y-m-d'),
                'week_end' => $endDate->format('Y-m-d'),
                'schedules' => $schedules,
            ],
        ]);
    }
    
    /**
     * Get performance details
     */
    public function performance(Request $request)
    {
        $user = $request->user();
        $month = $request->input('month', Carbon::now()->format('Y-m'));
        $startDate = Carbon::parse($month)->startOfMonth();
        $endDate = Carbon::parse($month)->endOfMonth();
        
        // Daily performance data
        $dailyPerformance = Tindakan::where('user_id', $user->id)
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->groupBy('tanggal')
            ->select(
                'tanggal',
                DB::raw('COUNT(*) as procedure_count'),
                DB::raw('COUNT(DISTINCT pasien_id) as patient_count')
            )
            ->get()
            ->map(function ($item) {
                return [
                    'date' => $item->tanggal->format('Y-m-d'),
                    'procedures' => $item->procedure_count,
                    'patients' => $item->patient_count,
                ];
            });
            
        // Procedure type breakdown
        $procedureTypes = Tindakan::where('user_id', $user->id)
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->join('jenis_tindakan', 'tindakan.jenis_tindakan_id', '=', 'jenis_tindakan.id')
            ->groupBy('jenis_tindakan.id', 'jenis_tindakan.nama')
            ->select(
                'jenis_tindakan.nama as procedure_type',
                DB::raw('COUNT(*) as count')
            )
            ->get();
            
        // Jaspel summary
        $jaspelSummary = Jaspel::where('user_id', $user->id)
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->groupBy('status')
            ->select(
                'status',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(jumlah) as total')
            )
            ->get()
            ->keyBy('status');
            
        return response()->json([
            'success' => true,
            'data' => [
                'month' => $month,
                'summary' => [
                    'total_procedures' => $dailyPerformance->sum('procedures'),
                    'total_patients' => Tindakan::where('user_id', $user->id)
                        ->whereBetween('tanggal', [$startDate, $endDate])
                        ->distinct('pasien_id')
                        ->count(),
                    'approved_jaspel' => $jaspelSummary->get('approved')->total ?? 0,
                    'pending_jaspel' => $jaspelSummary->get('pending')->total ?? 0,
                ],
                'daily_performance' => $dailyPerformance,
                'procedure_types' => $procedureTypes,
                'jaspel_breakdown' => [
                    'approved' => [
                        'count' => $jaspelSummary->get('approved')->count ?? 0,
                        'total' => $jaspelSummary->get('approved')->total ?? 0,
                    ],
                    'pending' => [
                        'count' => $jaspelSummary->get('pending')->count ?? 0,
                        'total' => $jaspelSummary->get('pending')->total ?? 0,
                    ],
                    'rejected' => [
                        'count' => $jaspelSummary->get('rejected')->count ?? 0,
                        'total' => $jaspelSummary->get('rejected')->total ?? 0,
                    ],
                ],
            ],
        ]);
    }
    
    /**
     * Mark notification as read
     */
    public function markNotificationRead(Request $request, $id)
    {
        $notification = $request->user()->notifications()->find($id);
        
        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found',
            ], 404);
        }
        
        $notification->markAsRead();
        
        return response()->json([
            'success' => true,
            'message' => 'Notification marked as read',
        ]);
    }
    
    /**
     * Get all notifications with pagination
     */
    public function notifications(Request $request)
    {
        $notifications = $request->user()->notifications()
            ->paginate(10);
            
        return response()->json([
            'success' => true,
            'data' => $notifications,
        ]);
    }
}