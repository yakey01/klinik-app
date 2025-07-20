<?php

namespace App\Filament\Paramedis\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\Support\Htmlable;
use Carbon\Carbon;

class JadwalJagaPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-calendar';
    protected static string $view = 'paramedis.jadwal-jaga';
    protected static ?string $slug = 'jadwal-jaga';
    
    protected static ?string $title = 'Jadwal Jaga - Schedule Management';
    protected static ?string $navigationLabel = 'Jadwal Jaga';
    protected static ?int $navigationSort = 3;
    protected static bool $shouldRegisterNavigation = true;
    
    // Security: Only accessible by paramedis role
    public static function canAccess(): bool
    {
        return auth()->check() && auth()->user()->role?->name === 'paramedis';
    }
    
    public function getTitle(): string|Htmlable
    {
        return 'Jadwal Jaga - Schedule Management';
    }
    
    public function getHeading(): string|Htmlable
    {
        return '';
    }
    
    protected function getViewData(): array
    {
        $user = Auth::user();
        
        // Sample schedule data for demonstration
        $scheduleStats = [
            'totalShiftsThisMonth' => 22,
            'totalHoursThisMonth' => 176,
            'upcomingShifts' => 5,
            'completedShifts' => 17,
            'averageHoursPerWeek' => 44,
            'overtimeHours' => 8,
        ];
        
        // Generate sample weekly schedule data
        $weeklySchedule = collect();
        $startOfWeek = Carbon::now()->startOfWeek();
        
        for ($i = 0; $i < 7; $i++) {
            $date = $startOfWeek->copy()->addDays($i);
            $isToday = $date->isToday();
            
            // Sample shift patterns
            $shifts = [
                ['type' => 'Pagi', 'time' => '06:00-14:00', 'unit' => 'IGD', 'color' => 'blue'],
                ['type' => 'Siang', 'time' => '14:00-22:00', 'unit' => 'Rawat Inap', 'color' => 'green'],
                ['type' => 'Malam', 'time' => '22:00-06:00', 'unit' => 'ICU', 'color' => 'purple'],
                ['type' => 'Off', 'time' => 'Libur', 'unit' => '', 'color' => 'gray'],
            ];
            
            // Assign shifts with some variation
            $dayShift = $shifts[($i + $date->day) % 4];
            
            $weeklySchedule->push([
                'date' => $date,
                'day_name' => $date->format('l'),
                'day_name_id' => $this->getDayNameIndonesian($date->format('l')),
                'formatted_date' => $date->format('d M Y'),
                'is_today' => $isToday,
                'shift' => $dayShift,
                'has_shift' => $dayShift['type'] !== 'Off',
            ]);
        }
        
        // Sample monthly overview
        $monthlyOverview = collect();
        $startOfMonth = Carbon::now()->startOfMonth();
        
        for ($week = 0; $week < 4; $week++) {
            $weekStart = $startOfMonth->copy()->addWeeks($week);
            $weekData = [];
            
            for ($day = 0; $day < 7; $day++) {
                $date = $weekStart->copy()->addDays($day);
                if ($date->month === Carbon::now()->month) {
                    $shiftType = ['Pagi', 'Siang', 'Malam', 'Off'][($day + $week) % 4];
                    $weekData[] = [
                        'date' => $date->day,
                        'full_date' => $date,
                        'shift_type' => $shiftType,
                        'is_today' => $date->isToday(),
                        'is_past' => $date->isPast(),
                    ];
                }
            }
            
            if (!empty($weekData)) {
                $monthlyOverview->push($weekData);
            }
        }
        
        // Upcoming shifts detail
        $upcomingShifts = collect([
            [
                'date' => Carbon::tomorrow(),
                'day_name' => $this->getDayNameIndonesian(Carbon::tomorrow()->format('l')),
                'formatted_date' => Carbon::tomorrow()->format('d M Y'),
                'shift_type' => 'Pagi',
                'time' => '06:00-14:00',
                'unit' => 'IGD',
                'status' => 'confirmed',
                'notes' => 'Shift normal'
            ],
            [
                'date' => Carbon::tomorrow()->addDay(),
                'day_name' => $this->getDayNameIndonesian(Carbon::tomorrow()->addDay()->format('l')),
                'formatted_date' => Carbon::tomorrow()->addDay()->format('d M Y'),
                'shift_type' => 'Siang', 
                'time' => '14:00-22:00',
                'unit' => 'Rawat Inap',
                'status' => 'pending',
                'notes' => 'Menunggu konfirmasi'
            ],
            [
                'date' => Carbon::tomorrow()->addDays(2),
                'day_name' => $this->getDayNameIndonesian(Carbon::tomorrow()->addDays(2)->format('l')),
                'formatted_date' => Carbon::tomorrow()->addDays(2)->format('d M Y'),
                'shift_type' => 'Malam',
                'time' => '22:00-06:00', 
                'unit' => 'ICU',
                'status' => 'confirmed',
                'notes' => 'Shift overtime'
            ],
        ]);
        
        return [
            'user' => $user,
            'scheduleStats' => $scheduleStats,
            'weeklySchedule' => $weeklySchedule,
            'monthlyOverview' => $monthlyOverview,
            'upcomingShifts' => $upcomingShifts,
            'currentWeek' => $startOfWeek->format('d M') . ' - ' . $startOfWeek->copy()->endOfWeek()->format('d M Y'),
            'currentMonth' => Carbon::now()->format('F Y'),
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