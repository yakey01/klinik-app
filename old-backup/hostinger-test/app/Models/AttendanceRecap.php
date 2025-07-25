<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AttendanceRecap extends Model
{
    // This is a virtual model - no actual table
    public $timestamps = false;
    protected $table = 'attendance_recaps'; // Dummy table name for Filament

    protected $fillable = [
        'staff_id',
        'staff_name',
        'staff_type',
        'position',
        'total_working_days',
        'days_present',
        'average_check_in',
        'average_check_out',
        'total_working_hours',
        'attendance_percentage',
        'status',
        'rank'
    ];

    protected $casts = [
        'total_working_days' => 'integer',
        'days_present' => 'integer',
        'total_working_hours' => 'decimal:2',
        'attendance_percentage' => 'decimal:2',
        'rank' => 'integer',
        'average_check_in' => 'datetime',
        'average_check_out' => 'datetime',
    ];

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