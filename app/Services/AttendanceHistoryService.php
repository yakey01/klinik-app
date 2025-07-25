<?php

namespace App\Services;

use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class AttendanceHistoryService
{
    /**
     * Get optimized attendance history for user with pagination
     */
    public function getUserAttendanceHistory(
        int $userId,
        array $filters = [],
        int $perPage = 25,
        int $page = 1
    ): LengthAwarePaginator {
        $query = $this->buildOptimizedQuery($userId, $filters);
        
        return $query->paginate($perPage, [
            'id', 'user_id', 'date', 'time_in', 'time_out', 
            'status', 'latitude', 'longitude', 'location_name_in',
            'location_name_out', 'notes', 'created_at'
        ], 'page', $page);
    }

    /**
     * Get attendance summary statistics for user
     */
    public function getUserAttendanceSummary(int $userId, string $period = 'this_month'): array
    {
        $query = $this->getBasePeriodQuery($userId, $period);
        
        $summary = $query->selectRaw('
            COUNT(*) as total_days,
            SUM(CASE WHEN time_in IS NOT NULL AND time_out IS NOT NULL THEN 1 ELSE 0 END) as complete_days,
            SUM(CASE WHEN time_in IS NOT NULL AND time_out IS NULL THEN 1 ELSE 0 END) as incomplete_days,
            SUM(CASE WHEN status = "present" THEN 1 ELSE 0 END) as present_days,
            SUM(CASE WHEN status = "late" THEN 1 ELSE 0 END) as late_days,
            SUM(CASE WHEN status = "absent" THEN 1 ELSE 0 END) as absent_days,
            SUM(CASE WHEN status = "sick" THEN 1 ELSE 0 END) as sick_days,
            SUM(CASE WHEN status = "permission" THEN 1 ELSE 0 END) as permission_days
        ')->first();

        // Calculate total working hours for completed days
        $totalMinutes = $query->whereNotNull('time_in')
            ->whereNotNull('time_out')
            ->get(['time_in', 'time_out'])
            ->sum(function ($attendance) {
                $timeIn = Carbon::parse($attendance->time_in);
                $timeOut = Carbon::parse($attendance->time_out);
                return $timeOut->diffInMinutes($timeIn);
            });

        $totalHours = intval($totalMinutes / 60);
        $remainingMinutes = $totalMinutes % 60;

        return [
            'total_days' => $summary->total_days ?? 0,
            'complete_days' => $summary->complete_days ?? 0,
            'incomplete_days' => $summary->incomplete_days ?? 0,
            'present_days' => $summary->present_days ?? 0,
            'late_days' => $summary->late_days ?? 0,
            'absent_days' => $summary->absent_days ?? 0,
            'sick_days' => $summary->sick_days ?? 0,
            'permission_days' => $summary->permission_days ?? 0,
            'total_working_hours' => $totalHours,
            'total_working_minutes' => $remainingMinutes,
            'total_working_time_formatted' => sprintf('%d jam %d menit', $totalHours, $remainingMinutes),
            'attendance_rate' => $summary->total_days > 0 ? 
                round(($summary->present_days + $summary->late_days) / $summary->total_days * 100, 1) : 0,
        ];
    }

    /**
     * Get attendance data for calendar view
     */
    public function getUserAttendanceCalendar(int $userId, Carbon $month): Collection
    {
        $startDate = $month->copy()->startOfMonth();
        $endDate = $month->copy()->endOfMonth();

        return Attendance::where('user_id', $userId)
            ->whereBetween('date', [$startDate, $endDate])
            ->select([
                'date', 'time_in', 'time_out', 'status',
                'latitude', 'longitude', 'location_name_in'
            ])
            ->orderBy('date')
            ->get()
            ->mapWithKeys(function ($attendance) {
                return [
                    $attendance->date->format('Y-m-d') => [
                        'date' => $attendance->date,
                        'time_in' => $attendance->time_in,
                        'time_out' => $attendance->time_out,
                        'status' => $attendance->status,
                        'has_location' => !empty($attendance->location_name_in) || 
                                       (!empty($attendance->latitude) && !empty($attendance->longitude)),
                        'is_complete' => !empty($attendance->time_in) && !empty($attendance->time_out),
                    ]
                ];
            });
    }

    /**
     * Build optimized query with filters
     */
    protected function buildOptimizedQuery(int $userId, array $filters = []): Builder
    {
        $query = Attendance::where('user_id', $userId)
            ->with(['user:id,name'])
            ->orderBy('date', 'desc');

        // Apply filters
        if (!empty($filters['status'])) {
            if (is_array($filters['status'])) {
                $query->whereIn('status', $filters['status']);
            } else {
                $query->where('status', $filters['status']);
            }
        }

        if (!empty($filters['date_from'])) {
            $query->where('date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('date', '<=', $filters['date_to']);
        }

        if (!empty($filters['period'])) {
            $query = $this->applyPeriodFilter($query, $filters['period']);
        }

        if (!empty($filters['incomplete_only'])) {
            $query->whereNotNull('time_in')->whereNull('time_out');
        }

        return $query;
    }

    /**
     * Apply period-based filters
     */
    protected function applyPeriodFilter(Builder $query, string $period): Builder
    {
        return match ($period) {
            'today' => $query->where('date', Carbon::today()),
            'yesterday' => $query->where('date', Carbon::yesterday()),
            'this_week' => $query->whereBetween('date', [
                Carbon::now()->startOfWeek(),
                Carbon::now()->endOfWeek()
            ]),
            'last_week' => $query->whereBetween('date', [
                Carbon::now()->subWeek()->startOfWeek(),
                Carbon::now()->subWeek()->endOfWeek()
            ]),
            'this_month' => $query->whereYear('date', Carbon::now()->year)
                ->whereMonth('date', Carbon::now()->month),
            'last_month' => $query->whereYear('date', Carbon::now()->subMonth()->year)
                ->whereMonth('date', Carbon::now()->subMonth()->month),
            'this_year' => $query->whereYear('date', Carbon::now()->year),
            'last_90_days' => $query->where('date', '>=', Carbon::now()->subDays(90)),
            default => $query,
        };
    }

    /**
     * Get base query for period
     */
    protected function getBasePeriodQuery(int $userId, string $period): Builder
    {
        $query = Attendance::where('user_id', $userId);
        return $this->applyPeriodFilter($query, $period);
    }

    /**
     * Get incomplete check-outs for user
     */
    public function getIncompleteCheckouts(int $userId): Collection
    {
        return Attendance::where('user_id', $userId)
            ->whereNotNull('time_in')
            ->whereNull('time_out')
            ->orderBy('date', 'desc')
            ->get(['id', 'date', 'time_in', 'location_name_in']);
    }

    /**
     * Get attendance streaks (consecutive working days)
     */
    public function getAttendanceStreaks(int $userId, int $limit = 5): array
    {
        $attendances = Attendance::where('user_id', $userId)
            ->whereIn('status', ['present', 'late'])
            ->orderBy('date', 'desc')
            ->take(30) // Look at last 30 records for performance
            ->get(['date', 'status']);

        $streaks = [];
        $currentStreak = 0;
        $maxStreak = 0;
        $previousDate = null;

        foreach ($attendances as $attendance) {
            if ($previousDate === null || $attendance->date->diffInDays($previousDate) === 1) {
                $currentStreak++;
                $maxStreak = max($maxStreak, $currentStreak);
            } else {
                if ($currentStreak > 0) {
                    $streaks[] = $currentStreak;
                }
                $currentStreak = 1;
            }
            $previousDate = $attendance->date;
        }

        if ($currentStreak > 0) {
            $streaks[] = $currentStreak;
        }

        return [
            'current_streak' => $attendances->isNotEmpty() && 
                $attendances->first()->date->isToday() ? $currentStreak : 0,
            'max_streak' => $maxStreak,
            'recent_streaks' => array_slice(array_reverse($streaks), 0, $limit),
        ];
    }
}