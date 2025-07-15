<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\NonParamedisAttendance;
use App\Models\Schedule;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Response;

class NonParamedisReportController extends Controller
{
    /**
     * Get attendance summary report
     */
    public function getAttendanceSummary(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'user_id' => 'nullable|exists:users,id',
            'group_by' => 'nullable|in:daily,weekly,monthly',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);
        $groupBy = $request->get('group_by', 'daily');

        $query = NonParamedisAttendance::with(['user'])
            ->whereBetween('attendance_date', [$startDate, $endDate]);

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Group by period
        $attendanceData = $query->get()->groupBy(function($item) use ($groupBy) {
            return match($groupBy) {
                'weekly' => $item->attendance_date->format('Y-W'),
                'monthly' => $item->attendance_date->format('Y-m'),
                default => $item->attendance_date->format('Y-m-d'),
            };
        });

        $summary = [];
        foreach ($attendanceData as $period => $attendances) {
            $summary[] = [
                'period' => $period,
                'total_records' => $attendances->count(),
                'present' => $attendances->where('status', 'present')->count(),
                'late' => $attendances->where('status', 'late')->count(),
                'absent' => $attendances->where('status', 'absent')->count(),
                'pending_approval' => $attendances->where('approval_status', 'pending')->count(),
                'approved' => $attendances->where('approval_status', 'approved')->count(),
                'rejected' => $attendances->where('approval_status', 'rejected')->count(),
                'total_work_hours' => round($attendances->sum('total_work_minutes') / 60, 2),
            ];
        }

        return response()->json([
            'success' => true,
            'message' => 'Attendance summary retrieved',
            'data' => [
                'summary' => $summary,
                'totals' => $this->calculateTotals($attendanceData->flatten()),
                'period' => [
                    'start_date' => $startDate->format('Y-m-d'),
                    'end_date' => $endDate->format('Y-m-d'),
                    'group_by' => $groupBy,
                ]
            ],
            'meta' => [
                'timestamp' => now()->toISOString(),
                'request_id' => \Illuminate\Support\Str::uuid()->toString(),
            ]
        ]);
    }

    /**
     * Get detailed attendance report
     */
    public function getDetailedReport(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'user_id' => 'nullable|exists:users,id',
            'status' => 'nullable|in:present,late,absent,pending',
            'approval_status' => 'nullable|in:pending,approved,rejected',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $query = NonParamedisAttendance::with(['user', 'workLocation', 'approvedBy'])
            ->whereBetween('attendance_date', [$request->start_date, $request->end_date]);

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('approval_status')) {
            $query->where('approval_status', $request->approval_status);
        }

        $attendances = $query->orderBy('attendance_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return response()->json([
            'success' => true,
            'message' => 'Detailed attendance report retrieved',
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
     * Get employee performance analytics
     */
    public function getPerformanceAnalytics(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'user_id' => 'nullable|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);

        $query = User::with(['nonParamedisAttendances' => function($q) use ($startDate, $endDate) {
            $q->whereBetween('attendance_date', [$startDate, $endDate]);
        }])->byRole('non_paramedis');

        if ($request->filled('user_id')) {
            $query->where('id', $request->user_id);
        }

        $users = $query->get();

        $analytics = [];
        foreach ($users as $user) {
            $attendances = $user->nonParamedisAttendances;
            $totalDays = $attendances->count();
            $presentDays = $attendances->where('status', 'present')->count();
            $lateDays = $attendances->where('status', 'late')->count();
            $absentDays = $attendances->where('status', 'absent')->count();
            $totalWorkMinutes = $attendances->sum('total_work_minutes');
            
            $analytics[] = [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'nip' => $user->nip,
                ],
                'attendance_stats' => [
                    'total_days' => $totalDays,
                    'present_days' => $presentDays,
                    'late_days' => $lateDays,
                    'absent_days' => $absentDays,
                    'attendance_rate' => $totalDays > 0 ? round(($presentDays + $lateDays) / $totalDays * 100, 2) : 0,
                    'punctuality_rate' => $totalDays > 0 ? round($presentDays / $totalDays * 100, 2) : 0,
                ],
                'work_stats' => [
                    'total_work_hours' => round($totalWorkMinutes / 60, 2),
                    'average_work_hours_per_day' => $totalDays > 0 ? round($totalWorkMinutes / $totalDays / 60, 2) : 0,
                    'total_work_minutes' => $totalWorkMinutes,
                ],
                'performance_score' => $this->calculatePerformanceScore($attendances),
            ];
        }

        // Sort by performance score descending
        usort($analytics, function($a, $b) {
            return $b['performance_score'] <=> $a['performance_score'];
        });

        return response()->json([
            'success' => true,
            'message' => 'Performance analytics retrieved',
            'data' => [
                'analytics' => $analytics,
                'summary' => $this->calculateAnalyticsSummary($analytics),
                'period' => [
                    'start_date' => $startDate->format('Y-m-d'),
                    'end_date' => $endDate->format('Y-m-d'),
                    'days_count' => $startDate->diffInDays($endDate) + 1,
                ]
            ],
            'meta' => [
                'timestamp' => now()->toISOString(),
                'request_id' => \Illuminate\Support\Str::uuid()->toString(),
            ]
        ]);
    }

    /**
     * Export attendance data to CSV
     */
    public function exportAttendanceCSV(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'user_id' => 'nullable|exists:users,id',
            'format' => 'nullable|in:csv,excel',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $startDate = $request->start_date;
        $endDate = $request->end_date;
        $format = $request->get('format', 'csv');

        $query = NonParamedisAttendance::with(['user', 'workLocation', 'approvedBy'])
            ->whereBetween('attendance_date', [$startDate, $endDate]);

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $attendances = $query->orderBy('attendance_date', 'desc')
            ->orderBy('user_id')
            ->get();

        $csvData = [];
        $csvData[] = [
            'Date',
            'Employee Name',
            'NIP',
            'Check In Time',
            'Check Out Time',
            'Work Duration (Hours)',
            'Status',
            'Work Location',
            'Approval Status',
            'Approved By',
            'Approved At',
            'Notes',
        ];

        foreach ($attendances as $attendance) {
            $csvData[] = [
                $attendance->attendance_date->format('Y-m-d'),
                $attendance->user->name,
                $attendance->user->nip,
                $attendance->check_in_time ? $attendance->check_in_time->format('H:i:s') : '',
                $attendance->check_out_time ? $attendance->check_out_time->format('H:i:s') : '',
                $attendance->total_work_minutes ? round($attendance->total_work_minutes / 60, 2) : 0,
                $attendance->status,
                $attendance->workLocation->name ?? '',
                $attendance->approval_status,
                $attendance->approvedBy->name ?? '',
                $attendance->approved_at ? $attendance->approved_at->format('Y-m-d H:i:s') : '',
                $attendance->notes ?? '',
            ];
        }

        $filename = 'attendance_report_' . $startDate . '_to_' . $endDate . '.csv';
        $filePath = 'exports/' . $filename;

        // Create CSV content
        $csvContent = '';
        foreach ($csvData as $row) {
            $csvContent .= implode(',', array_map(function($field) {
                return '"' . str_replace('"', '""', $field) . '"';
            }, $row)) . "\n";
        }

        // Store the file
        Storage::disk('local')->put($filePath, $csvContent);

        return response()->json([
            'success' => true,
            'message' => 'Export completed successfully',
            'data' => [
                'filename' => $filename,
                'file_path' => $filePath,
                'download_url' => route('admin.reports.download', ['filename' => $filename]),
                'records_count' => count($csvData) - 1, // Exclude header
                'file_size' => Storage::disk('local')->size($filePath),
            ],
            'meta' => [
                'timestamp' => now()->toISOString(),
                'request_id' => \Illuminate\Support\Str::uuid()->toString(),
            ]
        ]);
    }

    /**
     * Download exported file
     */
    public function downloadExport($filename)
    {
        $filePath = 'exports/' . $filename;
        
        if (!Storage::disk('local')->exists($filePath)) {
            return response()->json([
                'success' => false,
                'message' => 'File not found',
            ], 404);
        }

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        return response()->stream(function() use ($filePath) {
            echo Storage::disk('local')->get($filePath);
        }, 200, $headers);
    }

    /**
     * Get trend analysis
     */
    public function getTrendAnalysis(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'period' => 'required|in:week,month,quarter,year',
            'metric' => 'required|in:attendance_rate,punctuality_rate,work_hours',
            'user_id' => 'nullable|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $period = $request->period;
        $metric = $request->metric;
        $userId = $request->user_id;

        $endDate = now();
        $startDate = match($period) {
            'week' => $endDate->copy()->subWeeks(12),
            'month' => $endDate->copy()->subMonths(12),
            'quarter' => $endDate->copy()->subMonths(36),
            'year' => $endDate->copy()->subYears(3),
        };

        $query = NonParamedisAttendance::with(['user'])
            ->whereBetween('attendance_date', [$startDate, $endDate]);

        if ($userId) {
            $query->where('user_id', $userId);
        }

        $attendances = $query->get();

        $trendData = $this->calculateTrendData($attendances, $period, $metric, $startDate, $endDate);

        return response()->json([
            'success' => true,
            'message' => 'Trend analysis retrieved',
            'data' => [
                'trend_data' => $trendData,
                'analysis' => $this->analyzeTrend($trendData),
                'period' => $period,
                'metric' => $metric,
                'date_range' => [
                    'start' => $startDate->format('Y-m-d'),
                    'end' => $endDate->format('Y-m-d'),
                ]
            ],
            'meta' => [
                'timestamp' => now()->toISOString(),
                'request_id' => \Illuminate\Support\Str::uuid()->toString(),
            ]
        ]);
    }

    /**
     * Calculate totals for summary
     */
    private function calculateTotals($attendances)
    {
        return [
            'total_records' => $attendances->count(),
            'total_present' => $attendances->where('status', 'present')->count(),
            'total_late' => $attendances->where('status', 'late')->count(),
            'total_absent' => $attendances->where('status', 'absent')->count(),
            'total_pending' => $attendances->where('approval_status', 'pending')->count(),
            'total_approved' => $attendances->where('approval_status', 'approved')->count(),
            'total_rejected' => $attendances->where('approval_status', 'rejected')->count(),
            'total_work_hours' => round($attendances->sum('total_work_minutes') / 60, 2),
            'overall_attendance_rate' => $attendances->count() > 0 ? 
                round(($attendances->whereIn('status', ['present', 'late'])->count() / $attendances->count()) * 100, 2) : 0,
        ];
    }

    /**
     * Calculate performance score
     */
    private function calculatePerformanceScore($attendances)
    {
        $totalDays = $attendances->count();
        if ($totalDays === 0) return 0;

        $presentDays = $attendances->where('status', 'present')->count();
        $lateDays = $attendances->where('status', 'late')->count();
        $absentDays = $attendances->where('status', 'absent')->count();

        // Performance score calculation (0-100)
        $attendanceScore = (($presentDays + $lateDays) / $totalDays) * 60; // 60% weight
        $punctualityScore = ($presentDays / $totalDays) * 30; // 30% weight
        $consistencyScore = (1 - ($absentDays / $totalDays)) * 10; // 10% weight

        return round($attendanceScore + $punctualityScore + $consistencyScore, 2);
    }

    /**
     * Calculate analytics summary
     */
    private function calculateAnalyticsSummary($analytics)
    {
        if (empty($analytics)) return [];

        $count = count($analytics);
        $avgAttendanceRate = array_sum(array_column(array_column($analytics, 'attendance_stats'), 'attendance_rate')) / $count;
        $avgPunctualityRate = array_sum(array_column(array_column($analytics, 'attendance_stats'), 'punctuality_rate')) / $count;
        $avgPerformanceScore = array_sum(array_column($analytics, 'performance_score')) / $count;

        return [
            'total_employees' => $count,
            'average_attendance_rate' => round($avgAttendanceRate, 2),
            'average_punctuality_rate' => round($avgPunctualityRate, 2),
            'average_performance_score' => round($avgPerformanceScore, 2),
            'top_performer' => $analytics[0] ?? null,
            'performance_distribution' => $this->getPerformanceDistribution($analytics),
        ];
    }

    /**
     * Get performance distribution
     */
    private function getPerformanceDistribution($analytics)
    {
        $excellent = 0; // 90-100
        $good = 0;      // 80-89
        $average = 0;   // 70-79
        $poor = 0;      // <70

        foreach ($analytics as $analytic) {
            $score = $analytic['performance_score'];
            if ($score >= 90) $excellent++;
            elseif ($score >= 80) $good++;
            elseif ($score >= 70) $average++;
            else $poor++;
        }

        return [
            'excellent' => $excellent,
            'good' => $good,
            'average' => $average,
            'poor' => $poor,
        ];
    }

    /**
     * Calculate trend data
     */
    private function calculateTrendData($attendances, $period, $metric, $startDate, $endDate)
    {
        $groupedData = $attendances->groupBy(function($item) use ($period) {
            return match($period) {
                'week' => $item->attendance_date->format('Y-W'),
                'month' => $item->attendance_date->format('Y-m'),
                'quarter' => $item->attendance_date->format('Y') . '-Q' . $item->attendance_date->quarter,
                'year' => $item->attendance_date->format('Y'),
            };
        });

        $trendData = [];
        foreach ($groupedData as $periodKey => $periodAttendances) {
            $value = match($metric) {
                'attendance_rate' => $periodAttendances->count() > 0 ? 
                    round(($periodAttendances->whereIn('status', ['present', 'late'])->count() / $periodAttendances->count()) * 100, 2) : 0,
                'punctuality_rate' => $periodAttendances->count() > 0 ? 
                    round(($periodAttendances->where('status', 'present')->count() / $periodAttendances->count()) * 100, 2) : 0,
                'work_hours' => round($periodAttendances->sum('total_work_minutes') / 60, 2),
            };

            $trendData[] = [
                'period' => $periodKey,
                'value' => $value,
                'records_count' => $periodAttendances->count(),
            ];
        }

        return $trendData;
    }

    /**
     * Analyze trend
     */
    private function analyzeTrend($trendData)
    {
        if (count($trendData) < 2) {
            return ['trend' => 'insufficient_data', 'change' => 0];
        }

        $firstValue = $trendData[0]['value'];
        $lastValue = end($trendData)['value'];
        $change = $lastValue - $firstValue;
        $changePercent = $firstValue > 0 ? round(($change / $firstValue) * 100, 2) : 0;

        $trend = 'stable';
        if ($change > 0) $trend = 'improving';
        elseif ($change < 0) $trend = 'declining';

        return [
            'trend' => $trend,
            'change' => $change,
            'change_percent' => $changePercent,
            'first_value' => $firstValue,
            'last_value' => $lastValue,
        ];
    }
}
