<?php

namespace App\Filament\Petugas\Widgets;

use App\Services\PetugasStatsService;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Exception;

class PetugasPerformanceWidget extends Widget
{
    protected static string $view = 'filament.petugas.widgets.performance-widget';
    
    protected static ?int $sort = 2;
    
    // protected static ?string $pollingInterval = null; // DISABLED - emergency polling removal
    
    protected int | string | array $columnSpan = [
        'sm' => 1,
        'md' => 1,
        'lg' => 2,
        'xl' => 2,
    ];
    
    protected PetugasStatsService $statsService;
    
    public function __construct()
    {
        $this->statsService = new PetugasStatsService();
    }
    
    public function getViewData(): array
    {
        try {
            $userId = Auth::id();
            
            if (!$userId) {
                Log::warning('PetugasPerformanceWidget: No authenticated user');
                return $this->getEmptyViewData('Tidak ada user yang terautentikasi');
            }
            
            return [
                'work_metrics' => $this->getWorkMetrics($userId),
                'shift_info' => $this->getShiftInfo(),
                'last_updated' => now()->format('H:i'),
                'user_id' => $userId,
            ];
            
        } catch (Exception $e) {
            Log::error('PetugasPerformanceWidget: Failed to get view data', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return $this->getEmptyViewData('Terjadi kesalahan saat memuat data');
        }
    }
    
    protected function getWorkMetrics(int $userId): array
    {
        $cacheKey = "performance_metrics_{$userId}";
        
        return Cache::remember($cacheKey, now()->addMinutes(15), function () use ($userId) {
            $stats = $this->statsService->getDashboardStats($userId);
            $todayStats = $stats['daily']['today'];
            $performanceMetrics = $stats['performance_metrics'];
            $validationSummary = $stats['validation_summary'];
            
            // Use demo data if no real data exists
            $demoMode = $todayStats['pasien_count'] === 0 && $todayStats['tindakan_count'] === 0;
            
            if ($demoMode) {
                return $this->getDemoWorkMetrics();
            }
            
            // Calculate work hours (simplified)
            $workStartTime = Carbon::today()->setHour(8);
            $currentTime = Carbon::now();
            $workHours = max(0, min(8, $currentTime->diffInHours($workStartTime)));
            
            // Calculate productivity metrics
            $totalInputs = $todayStats['pasien_count'] + $todayStats['tindakan_count'];
            $productivityScore = $workHours > 0 ? min(100, ($totalInputs / ($workHours * 3)) * 100) : 0;
            
            return [
                'work_hours' => $workHours,
                'productivity_score' => round($productivityScore, 1),
                'efficiency_score' => $performanceMetrics['efficiency_score'] ?? 85,
                'quality_score' => $performanceMetrics['quality_score'] ?? 90,
                'approval_rate' => $validationSummary['approval_rate'],
                'total_inputs' => $totalInputs,
                'inputs_per_hour' => $workHours > 0 ? round($totalInputs / $workHours, 1) : 0,
                'pending_tasks' => $validationSummary['pending_validations'],
            ];
        });
    }
    
    protected function getDemoWorkMetrics(): array
    {
        $workStartTime = Carbon::today()->setHour(8);
        $currentTime = Carbon::now();
        $workHours = max(0, min(8, $currentTime->diffInHours($workStartTime)));
        
        return [
            'work_hours' => $workHours,
            'productivity_score' => 84.5,
            'efficiency_score' => 88,
            'quality_score' => 92,
            'approval_rate' => 95,
            'total_inputs' => 38,
            'inputs_per_hour' => $workHours > 0 ? round(38 / max(1, $workHours), 1) : 4.8,
            'pending_tasks' => 2,
        ];
    }
    
    protected function getShiftInfo(): array
    {
        $currentTime = Carbon::now();
        $workStartTime = Carbon::today()->setHour(8);
        $workEndTime = Carbon::today()->setHour(17);
        
        $totalWorkMinutes = $workEndTime->diffInMinutes($workStartTime);
        $elapsedMinutes = max(0, $currentTime->diffInMinutes($workStartTime));
        $progressPercentage = min(100, ($elapsedMinutes / $totalWorkMinutes) * 100);
        
        if ($currentTime->isBefore($workStartTime)) {
            $status = [
                'label' => 'Belum Mulai',
                'color' => 'gray',
                'icon' => '⏰',
            ];
        } elseif ($currentTime->isAfter($workEndTime)) {
            $status = [
                'label' => 'Selesai Kerja',
                'color' => 'green',
                'icon' => '✅',
            ];
        } else {
            $status = [
                'label' => 'Sedang Bekerja',
                'color' => 'blue',
                'icon' => '💼',
            ];
        }
        
        return [
            'current_time' => $currentTime->format('H:i'),
            'work_start' => $workStartTime->format('H:i'),
            'work_end' => $workEndTime->format('H:i'),
            'elapsed_hours' => round($elapsedMinutes / 60, 1),
            'progress_percentage' => round($progressPercentage, 1),
            'status' => $status,
        ];
    }
    
    protected function getEmptyViewData(string $error = ''): array
    {
        return [
            'work_metrics' => [
                'work_hours' => 0,
                'productivity_score' => 0,
                'efficiency_score' => 0,
                'quality_score' => 0,
                'approval_rate' => 0,
                'total_inputs' => 0,
                'inputs_per_hour' => 0,
                'pending_tasks' => 0,
            ],
            'shift_info' => [
                'current_time' => now()->format('H:i'),
                'work_start' => '08:00',
                'work_end' => '17:00',
                'elapsed_hours' => 0,
                'progress_percentage' => 0,
                'status' => [
                    'label' => 'Tidak Diketahui',
                    'color' => 'gray',
                    'icon' => '❓',
                ],
            ],
            'last_updated' => now()->format('H:i'),
            'user_id' => Auth::id(),
            'error' => $error,
        ];
    }
}