<?php

namespace App\Filament\Paramedis\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\Support\Htmlable;
use Carbon\Carbon;

class PresensiPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-clock';
    protected static string $view = 'paramedis.presensi.dashboard';
    protected static ?string $slug = 'presensi';
    
    protected static ?string $title = 'Presensi Harian - Daily Attendance';
    protected static ?string $navigationLabel = 'Presensi';
    protected static ?int $navigationSort = 2;
    protected static bool $shouldRegisterNavigation = true;
    
    // Security: Only accessible by paramedis role
    public static function canAccess(): bool
    {
        return auth()->check() && auth()->user()->role?->name === 'paramedis';
    }
    
    public function getTitle(): string|Htmlable
    {
        return 'Presensi Harian - Daily Attendance';
    }
    
    public function getHeading(): string|Htmlable
    {
        return '';
    }
    
    protected function getViewData(): array
    {
        $user = Auth::user();
        
        // Sample attendance data for demonstration
        $attendanceStats = [
            'monthlyAttendance' => 22,
            'totalHoursThisMonth' => 176,
            'onTimePercentage' => 95,
            'overtimeHours' => 8,
            'perfectAttendanceDays' => 15,
            'averageCheckInTime' => '07:45',
        ];
        
        // Current day attendance status
        $today = Carbon::now();
        $todayAttendance = [
            'date' => $today->format('d M Y'),
            'day_name' => $this->getDayNameIndonesian($today->format('l')),
            'has_checked_in' => rand(0, 1) === 1, // Random for demo
            'has_checked_out' => false, // Usually false during work hours
            'check_in_time' => '07:30',
            'check_out_time' => null,
            'work_duration' => '8h 30m',
            'is_late' => false,
            'location_valid' => true,
        ];
        
        // Current GPS location (will be updated by JavaScript)
        $currentLocation = [
            'latitude' => null, // Will be set by GPS detection
            'longitude' => null, // Will be set by GPS detection
            'accuracy' => null, // Will be set by GPS detection
            'address' => 'Mendeteksi lokasi...', // Will be updated by reverse geocoding
            'is_within_geofence' => false, // Will be calculated after GPS detection
            'distance_from_clinic' => null, // Will be calculated after GPS detection
            'clinic_radius' => 0.1, // km allowed radius
        ];
        
        // Geofencing validation (will be updated by JavaScript)
        $geofenceStatus = [
            'is_valid' => false, // Will be calculated after GPS detection
            'message' => 'Mendeteksi lokasi GPS...', // Will be updated after GPS detection
            'status_color' => 'yellow', // Will be updated after GPS detection
        ];
        
        // Recent attendance history (last 10 days)
        $attendanceHistory = collect();
        for ($i = 9; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            
            // Skip weekends for medical staff (optional)
            if ($date->isWeekend() && rand(0, 1) === 0) {
                continue;
            }
            
            $checkInTime = $date->setTime(7, rand(15, 45)); // 07:15 to 07:45
            $checkOutTime = $date->setTime(16, rand(0, 30)); // 16:00 to 16:30
            $isLate = $checkInTime->minute > 30;
            
            $attendanceHistory->push([
                'date' => $date->format('d M Y'),
                'day_name' => $this->getDayNameIndonesian($date->format('l')),
                'check_in' => $checkInTime->format('H:i'),
                'check_out' => $checkOutTime->format('H:i'),
                'work_hours' => $checkInTime->diffInHours($checkOutTime) . 'h ' . 
                              ($checkInTime->diffInMinutes($checkOutTime) % 60) . 'm',
                'status' => $isLate ? 'late' : 'on_time',
                'is_today' => $date->isToday(),
                'overtime' => rand(0, 3) > 2 ? rand(1, 3) . 'h' : null,
            ]);
        }
        
        // Get active work location from database
        $workLocation = \App\Models\WorkLocation::where('is_active', true)
            ->where('location_type', 'main_office')
            ->first();
            
        if (!$workLocation) {
            // Fallback if no work location configured
            $workLocationData = [
                'name' => 'Klinik Dokterku',
                'latitude' => -6.2088,
                'longitude' => 106.8456,
                'address' => 'Jakarta, Indonesia',
                'radius' => 100, // meters
            ];
        } else {
            $workLocationData = [
                'name' => $workLocation->name,
                'latitude' => (float) $workLocation->latitude,
                'longitude' => (float) $workLocation->longitude,
                'address' => $workLocation->address,
                'radius' => $workLocation->radius_meters,
            ];
        }
        
        return [
            'user' => $user,
            'attendanceStats' => $attendanceStats,
            'todayAttendance' => $todayAttendance,
            'currentLocation' => $currentLocation,
            'geofenceStatus' => $geofenceStatus,
            'attendanceHistory' => $attendanceHistory,
            'workLocation' => $workLocationData,
            'currentTime' => Carbon::now()->format('H:i:s'),
            'currentDate' => Carbon::now()->format('l, d F Y'),
        ];
    }
    
    private function getDayNameIndonesian(string $dayName): string
    {
        $days = [
            'Monday' => 'Senin',
            'Tuesday' => 'Selasa', 
            'Wednesday' => 'Rabu',
            'Thursday' => 'Kamis',
            'Friday' => 'Jumat',
            'Saturday' => 'Sabtu',
            'Sunday' => 'Minggu',
        ];
        
        return $days[$dayName] ?? $dayName;
    }
}