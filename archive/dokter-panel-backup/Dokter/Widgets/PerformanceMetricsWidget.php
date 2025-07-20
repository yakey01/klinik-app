<?php

namespace App\Filament\Dokter\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Dokter;
use App\Models\Tindakan;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class PerformanceMetricsWidget extends BaseWidget
{
    protected static ?int $sort = 4;
    
    protected function getStats(): array
    {
        $user = Auth::user();
        $dokter = Dokter::where('user_id', $user->id)->first();
        
        if (!$dokter) {
            return [
                Stat::make('Error', 'Data dokter tidak ditemukan')
                    ->color('danger')
                    ->icon('heroicon-o-exclamation-triangle'),
            ];
        }

        // Cache performance metrics for 15 minutes
        $cacheKey = "dokter_performance_{$dokter->id}";
        $metrics = Cache::remember($cacheKey, 900, function () use ($dokter) {
            $thisMonth = Carbon::now()->startOfMonth();
            $lastMonth = Carbon::now()->subMonth()->startOfMonth();
            $lastMonthEnd = Carbon::now()->subMonth()->endOfMonth();
            
            // This month stats
            $thisMonthPatients = Tindakan::where('dokter_id', $dokter->id)
                ->where('tanggal_tindakan', '>=', $thisMonth)
                ->distinct('pasien_id')
                ->count();
            
            $thisMonthProcedures = Tindakan::where('dokter_id', $dokter->id)
                ->where('tanggal_tindakan', '>=', $thisMonth)
                ->count();
            
            $thisMonthJaspel = Tindakan::where('dokter_id', $dokter->id)
                ->where('tanggal_tindakan', '>=', $thisMonth)
                ->where('status_validasi', 'disetujui')
                ->sum('jasa_dokter');
            
            // Last month stats for comparison
            $lastMonthPatients = Tindakan::where('dokter_id', $dokter->id)
                ->whereBetween('tanggal_tindakan', [$lastMonth, $lastMonthEnd])
                ->distinct('pasien_id')
                ->count();
            
            $lastMonthProcedures = Tindakan::where('dokter_id', $dokter->id)
                ->whereBetween('tanggal_tindakan', [$lastMonth, $lastMonthEnd])
                ->count();
            
            $lastMonthJaspel = Tindakan::where('dokter_id', $dokter->id)
                ->whereBetween('tanggal_tindakan', [$lastMonth, $lastMonthEnd])
                ->where('status_validasi', 'disetujui')
                ->sum('jasa_dokter');
            
            // Calculate averages
            $avgPatientsPerDay = $thisMonthProcedures > 0 ? round($thisMonthPatients / now()->day, 1) : 0;
            $avgJaspelPerProcedure = $thisMonthProcedures > 0 ? round($thisMonthJaspel / $thisMonthProcedures, 0) : 0;
            
            // Calculate approval rate
            $totalProceduresThisMonth = Tindakan::where('dokter_id', $dokter->id)
                ->where('tanggal_tindakan', '>=', $thisMonth)
                ->count();
            
            $approvedProceduresThisMonth = Tindakan::where('dokter_id', $dokter->id)
                ->where('tanggal_tindakan', '>=', $thisMonth)
                ->where('status_validasi', 'disetujui')
                ->count();
            
            $approvalRate = $totalProceduresThisMonth > 0 ? 
                round(($approvedProceduresThisMonth / $totalProceduresThisMonth) * 100, 1) : 0;
            
            return [
                'this_month_patients' => $thisMonthPatients,
                'this_month_procedures' => $thisMonthProcedures,
                'this_month_jaspel' => $thisMonthJaspel,
                'last_month_patients' => $lastMonthPatients,
                'last_month_procedures' => $lastMonthProcedures,
                'last_month_jaspel' => $lastMonthJaspel,
                'avg_patients_per_day' => $avgPatientsPerDay,
                'avg_jaspel_per_procedure' => $avgJaspelPerProcedure,
                'approval_rate' => $approvalRate,
            ];
        });
        
        // Calculate trends
        $patientTrend = $this->calculateTrend($metrics['this_month_patients'], $metrics['last_month_patients']);
        $procedureTrend = $this->calculateTrend($metrics['this_month_procedures'], $metrics['last_month_procedures']);
        $jaspelTrend = $this->calculateTrend($metrics['this_month_jaspel'], $metrics['last_month_jaspel']);

        return [
            Stat::make('Total Pasien Bulan Ini', $metrics['this_month_patients'])
                ->description($patientTrend['description'])
                ->descriptionIcon($patientTrend['icon'])
                ->color($patientTrend['color'])
                ->icon('heroicon-o-user-group'),
                
            Stat::make('Rata-rata Pasien/Hari', $metrics['avg_patients_per_day'])
                ->description('Berdasarkan hari kerja bulan ini')
                ->color('info')
                ->icon('heroicon-o-chart-bar'),
                
            Stat::make('Tingkat Persetujuan', $metrics['approval_rate'] . '%')
                ->description('Tindakan yang disetujui bulan ini')
                ->color($metrics['approval_rate'] >= 90 ? 'success' : ($metrics['approval_rate'] >= 70 ? 'warning' : 'danger'))
                ->icon('heroicon-o-check-badge'),
                
            Stat::make('Rata-rata Jaspel/Tindakan', 'Rp ' . number_format($metrics['avg_jaspel_per_procedure'], 0, ',', '.'))
                ->description('Jaspel per tindakan bulan ini')
                ->color('success')
                ->icon('heroicon-o-calculator'),
        ];
    }
    
    private function calculateTrend($current, $previous): array
    {
        if ($previous == 0) {
            if ($current > 0) {
                return [
                    'description' => 'Naik dari bulan lalu',
                    'icon' => 'heroicon-m-arrow-trending-up',
                    'color' => 'success'
                ];
            } else {
                return [
                    'description' => 'Sama dengan bulan lalu',
                    'icon' => 'heroicon-m-minus',
                    'color' => 'gray'
                ];
            }
        }
        
        $change = (($current - $previous) / $previous) * 100;
        
        if ($change > 0) {
            return [
                'description' => sprintf('+%.1f%% dari bulan lalu', $change),
                'icon' => 'heroicon-m-arrow-trending-up',
                'color' => 'success'
            ];
        } elseif ($change < 0) {
            return [
                'description' => sprintf('%.1f%% dari bulan lalu', $change),
                'icon' => 'heroicon-m-arrow-trending-down',
                'color' => 'danger'
            ];
        } else {
            return [
                'description' => 'Sama dengan bulan lalu',
                'icon' => 'heroicon-m-minus',
                'color' => 'gray'
            ];
        }
    }
}