<?php

namespace App\Filament\Paramedis\Widgets;

use App\Models\Jaspel;
use App\Models\Tindakan;
use App\Models\Attendance;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MobilePerformanceWidget extends Widget
{
    protected static string $view = 'filament.paramedis.widgets.mobile-performance-widget';
    protected static ?int $sort = 3;
    
    public function getViewData(): array
    {
        $user = Auth::user();
        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();
        
        // Performance metrics
        $metrics = [
            'procedures' => Tindakan::where('user_id', $user->id)
                ->whereBetween('tanggal', [$startOfMonth, $endOfMonth])
                ->count(),
                
            'patients' => Tindakan::where('user_id', $user->id)
                ->whereBetween('tanggal', [$startOfMonth, $endOfMonth])
                ->distinct('pasien_id')
                ->count('pasien_id'),
                
            'jaspel' => Jaspel::where('user_id', $user->id)
                ->whereBetween('tanggal', [$startOfMonth, $endOfMonth])
                ->where('status', 'approved')
                ->sum('jumlah'),
                
            'attendance' => Attendance::where('user_id', $user->id)
                ->whereBetween('date', [$startOfMonth, $endOfMonth])
                ->where('status', 'present')
                ->count(),
        ];
        
        // Calculate attendance percentage
        $workDays = now()->startOfMonth()->diffInWeekdays(now()) + 1;
        $attendancePercentage = $workDays > 0 ? round(($metrics['attendance'] / $workDays) * 100) : 0;
        
        // Daily trend for the last 7 days
        $dailyTrend = Tindakan::where('user_id', $user->id)
            ->where('tanggal', '>=', now()->subDays(6))
            ->groupBy('tanggal')
            ->select('tanggal', DB::raw('COUNT(*) as count'))
            ->orderBy('tanggal')
            ->get()
            ->pluck('count', 'tanggal');
            
        // Fill missing days with 0
        $trend = collect();
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $trend->push($dailyTrend->get($date, 0));
        }
        
        return [
            'metrics' => $metrics,
            'attendancePercentage' => $attendancePercentage,
            'trend' => $trend->toArray(),
            'monthName' => now()->format('F'),
        ];
    }
}