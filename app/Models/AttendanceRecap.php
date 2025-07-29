<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AttendanceRecap extends Model
{
    protected $table = 'attendance_recaps';

    protected $fillable = [
        'user_id',
        'year',
        'month',
        'total_working_days',
        'present_days',
        'late_days',
        'absent_days',
        'attendance_percentage',
        'total_work_minutes',
        'ranking',
        // Legacy virtual fields for compatibility
        'staff_id',
        'staff_name',
        'staff_type',
        'position',
        'days_present',
        'average_check_in',
        'average_check_out',
        'total_working_hours',
        'status',
        'rank'
    ];

    protected $casts = [
        'user_id' => 'integer',
        'year' => 'integer',
        'month' => 'integer',
        'total_working_days' => 'integer',
        'present_days' => 'integer',
        'late_days' => 'integer',
        'absent_days' => 'integer',
        'attendance_percentage' => 'decimal:2',
        'total_work_minutes' => 'integer',
        'ranking' => 'integer',
        // Legacy compatibility
        'days_present' => 'integer',
        'total_working_hours' => 'decimal:2',
        'rank' => 'integer',
        'average_check_in' => 'datetime',
        'average_check_out' => 'datetime',
    ];

    /**
     * Get the user that owns the attendance recap
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Generate or update attendance recap for a user and period
     */
    public static function generateForUser($userId, $month = null, $year = null)
    {
        $month = $month ?? now()->month;
        $year = $year ?? now()->year;
        
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();
        $totalWorkingDays = self::getWorkingDaysInMonth($month, $year);
        
        // Get attendance data for the user
        $attendances = DB::table('attendances')
            ->where('user_id', $userId)
            ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->get();
        
        $presentDays = $attendances->count();
        $lateDays = $attendances->where('status', 'late')->count();
        $absentDays = max(0, $totalWorkingDays - $presentDays);
        $attendancePercentage = $totalWorkingDays > 0 ? round(($presentDays / $totalWorkingDays) * 100, 2) : 0;
        
        // Calculate total work minutes
        $totalWorkMinutes = $attendances->sum(function ($attendance) {
            if ($attendance->time_in && $attendance->time_out) {
                $timeIn = Carbon::parse($attendance->time_in);
                $timeOut = Carbon::parse($attendance->time_out);
                return $timeOut->diffInMinutes($timeIn);
            }
            return 0;
        });
        
        // Create or update recap
        return self::updateOrCreate(
            ['user_id' => $userId, 'year' => $year, 'month' => $month],
            [
                'total_working_days' => $totalWorkingDays,
                'present_days' => $presentDays,
                'late_days' => $lateDays,
                'absent_days' => $absentDays,
                'attendance_percentage' => $attendancePercentage,
                'total_work_minutes' => $totalWorkMinutes,
            ]
        );
    }

    /**
     * Calculate rankings for all users in a period
     */
    public static function calculateRankings($month = null, $year = null, $staffType = null)
    {
        $month = $month ?? now()->month;
        $year = $year ?? now()->year;
        
        $query = self::where('year', $year)
                    ->where('month', $month)
                    ->with('user.pegawai');
        
        if ($staffType) {
            $query->whereHas('user.pegawai', function ($q) use ($staffType) {
                $q->where('jenis_pegawai', $staffType);
            });
        }
        
        $recaps = $query->orderByDesc('attendance_percentage')->get();
        
        $rank = 1;
        foreach ($recaps as $recap) {
            $recap->update(['ranking' => $rank]);
            $rank++;
        }
        
        return $recaps;
    }

    /**
     * Get attendance recap data for all staff types
     */
    public static function getRecapData($month = null, $year = null, $staffType = null, $statusFilter = null)
    {
        $month = $month ?? now()->month;
        $year = $year ?? now()->year;
        
        $recapData = collect();

        // Get data from all three attendance systems
        $doctorData = self::getDoctorAttendanceData($month, $year);
        $paramedisData = self::getParamedisAttendanceData($month, $year);
        $nonParamedisData = self::getNonParamedisAttendanceData($month, $year);

        // Merge all data
        $allData = $doctorData->merge($paramedisData)->merge($nonParamedisData);

        // Filter by staff type if specified
        if ($staffType) {
            $allData = $allData->where('staff_type', $staffType);
        }

        // Filter by status if specified
        if ($statusFilter) {
            $allData = $allData->where('status', $statusFilter);
        }

        // Calculate ranking based on attendance percentage (highest first)
        $rankedData = $allData->sortByDesc('attendance_percentage')->values();
        
        // Assign ranking numbers
        $rankedData = $rankedData->map(function ($item, $index) {
            $item['rank'] = $index + 1;
            return $item;
        });

        return $rankedData;
    }

    /**
     * Get doctor attendance data
     */
    private static function getDoctorAttendanceData($month, $year)
    {
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();
        $workingDays = self::getWorkingDaysInMonth($month, $year);

        return DB::table('dokter_presensis as dp')
            ->join('dokters as d', 'dp.dokter_id', '=', 'd.id')
            ->join('users as u', 'd.user_id', '=', 'u.id')
            ->select([
                'u.id as staff_id',
                DB::raw("COALESCE(d.nama_lengkap, u.name) as staff_name"),
                DB::raw("'Dokter' as staff_type"),
                DB::raw("COALESCE(d.jabatan, 'Dokter Umum') as position"),
                DB::raw("$workingDays as total_working_days"),
                DB::raw('COUNT(DISTINCT dp.tanggal) as days_present'),
                DB::raw('AVG(TIME(dp.jam_masuk)) as average_check_in'),
                DB::raw('AVG(TIME(dp.jam_pulang)) as average_check_out'),
                DB::raw('SUM(CASE 
                    WHEN dp.jam_pulang IS NOT NULL 
                    THEN (julianday(dp.jam_pulang) - julianday(dp.jam_masuk)) * 24 
                    ELSE 0 
                END) as total_working_hours'),
                DB::raw("ROUND((CAST(COUNT(DISTINCT dp.tanggal) AS REAL) / CAST($workingDays AS REAL)) * 100, 2) as attendance_percentage")
            ])
            ->whereBetween('dp.tanggal', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->groupBy('u.id', 'u.name', 'd.nama_lengkap', 'd.jabatan')
            ->get()
            ->map(function ($item) {
                $item->status = self::getAttendanceStatus($item->attendance_percentage);
                return (array) $item;
            });
    }

    /**
     * Get paramedis attendance data
     */
    private static function getParamedisAttendanceData($month, $year)
    {
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();
        $workingDays = self::getWorkingDaysInMonth($month, $year);

        return DB::table('attendances as a')
            ->join('users as u', 'a.user_id', '=', 'u.id')
            ->join('pegawais as p', 'u.id', '=', 'p.user_id')
            ->select([
                'u.id as staff_id',
                'u.name as staff_name',
                DB::raw("'Paramedis' as staff_type"),
                'p.jabatan as position',
                DB::raw("$workingDays as total_working_days"),
                DB::raw('COUNT(DISTINCT a.date) as days_present'),
                DB::raw('AVG(TIME(a.time_in)) as average_check_in'),
                DB::raw('AVG(TIME(a.time_out)) as average_check_out'),
                DB::raw('SUM(CASE 
                    WHEN a.time_out IS NOT NULL 
                    THEN (julianday(a.time_out) - julianday(a.time_in)) * 24 
                    ELSE 0 
                END) as total_working_hours'),
                DB::raw("ROUND((CAST(COUNT(DISTINCT a.date) AS REAL) / CAST($workingDays AS REAL)) * 100, 2) as attendance_percentage")
            ])
            ->where('p.jenis_pegawai', 'Paramedis')
            ->whereBetween('a.date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->groupBy('u.id', 'u.name', 'p.jabatan')
            ->get()
            ->map(function ($item) {
                $item->status = self::getAttendanceStatus($item->attendance_percentage);
                return (array) $item;
            });
    }

    /**
     * Get non-paramedis attendance data
     */
    private static function getNonParamedisAttendanceData($month, $year)
    {
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();
        $workingDays = self::getWorkingDaysInMonth($month, $year);

        return DB::table('non_paramedis_attendances as npa')
            ->join('users as u', 'npa.user_id', '=', 'u.id')
            ->join('pegawais as p', 'u.id', '=', 'p.user_id')
            ->select([
                'u.id as staff_id',
                'u.name as staff_name',
                DB::raw("'Non-Paramedis' as staff_type"),
                'p.jabatan as position',
                DB::raw("$workingDays as total_working_days"),
                DB::raw('COUNT(DISTINCT npa.attendance_date) as days_present'),
                DB::raw('AVG(TIME(npa.check_in_time)) as average_check_in'),
                DB::raw('AVG(TIME(npa.check_out_time)) as average_check_out'),
                DB::raw('SUM(CASE 
                    WHEN npa.check_out_time IS NOT NULL 
                    THEN (julianday(npa.check_out_time) - julianday(npa.check_in_time)) * 24 
                    ELSE 0 
                END) as total_working_hours'),
                DB::raw("ROUND((CAST(COUNT(DISTINCT npa.attendance_date) AS REAL) / CAST($workingDays AS REAL)) * 100, 2) as attendance_percentage")
            ])
            ->where('p.jenis_pegawai', 'non_paramedis')
            ->whereBetween('npa.attendance_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->groupBy('u.id', 'u.name', 'p.jabatan')
            ->get()
            ->map(function ($item) {
                $item->status = self::getAttendanceStatus($item->attendance_percentage);
                return (array) $item;
            });
    }

    /**
     * Calculate working days in a month (exclude weekends)
     */
    private static function getWorkingDaysInMonth($month, $year)
    {
        $startDate = Carbon::create($year, $month, 1);
        $endDate = $startDate->copy()->endOfMonth();
        $workingDays = 0;

        while ($startDate->lte($endDate)) {
            // Count Monday to Saturday as working days (exclude Sunday)
            if ($startDate->dayOfWeek !== Carbon::SUNDAY) {
                $workingDays++;
            }
            $startDate->addDay();
        }

        return $workingDays;
    }

    /**
     * Get attendance status based on percentage
     */
    private static function getAttendanceStatus($percentage)
    {
        if ($percentage >= 95) {
            return 'excellent';
        } elseif ($percentage >= 85) {
            return 'good';
        } elseif ($percentage >= 75) {
            return 'average';
        } else {
            return 'poor';
        }
    }

    /**
     * Get status badge color
     */
    public function getStatusBadgeColor()
    {
        return match($this->status) {
            'excellent' => 'success',
            'good' => 'info',
            'average' => 'warning',
            'poor' => 'danger',
            default => 'secondary'
        };
    }

    /**
     * Get status label
     */
    public function getStatusLabel()
    {
        return match($this->status) {
            'excellent' => 'Excellent (≥95%)',
            'good' => 'Good (85-94%)',
            'average' => 'Average (75-84%)',
            'poor' => 'Poor (<75%)',
            default => 'Unknown'
        };
    }

    /**
     * Get formatted working hours
     */
    public function getFormattedWorkingHours()
    {
        $hours = floor($this->total_working_hours);
        $minutes = round(($this->total_working_hours - $hours) * 60);
        
        return sprintf('%d jam %d menit', $hours, $minutes);
    }
}