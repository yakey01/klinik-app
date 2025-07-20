<?php

namespace App\Filament\Dokter\Resources\JaspelResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Dokter;
use App\Models\Tindakan;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class JaspelOverviewWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $user = Auth::user();
        $dokter = Dokter::where('user_id', $user->id)->first();
        
        if (!$dokter) {
            return [];
        }

        $thisMonth = Carbon::now()->startOfMonth();
        $lastMonth = Carbon::now()->subMonth()->startOfMonth();
        $lastMonthEnd = Carbon::now()->subMonth()->endOfMonth();
        $thisYear = Carbon::now()->startOfYear();
        
        // This month approved jaspel
        $thisMonthApproved = Tindakan::where('dokter_id', $dokter->id)
            ->where('tanggal_tindakan', '>=', $thisMonth)
            ->where('status_validasi', 'disetujui')
            ->sum('jasa_dokter');
            
        // This month pending jaspel
        $thisMonthPending = Tindakan::where('dokter_id', $dokter->id)
            ->where('tanggal_tindakan', '>=', $thisMonth)
            ->where('status_validasi', 'pending')
            ->sum('jasa_dokter');
            
        // Last month for comparison
        $lastMonthApproved = Tindakan::where('dokter_id', $dokter->id)
            ->whereBetween('tanggal_tindakan', [$lastMonth, $lastMonthEnd])
            ->where('status_validasi', 'disetujui')
            ->sum('jasa_dokter');
            
        // Year total
        $yearTotal = Tindakan::where('dokter_id', $dokter->id)
            ->where('tanggal_tindakan', '>=', $thisYear)
            ->where('status_validasi', 'disetujui')
            ->sum('jasa_dokter');
        
        // Calculate trend
        $trend = $this->calculateTrend($thisMonthApproved, $lastMonthApproved);

        return [
            Stat::make('Jaspel Disetujui Bulan Ini', 'Rp ' . number_format($thisMonthApproved, 0, ',', '.'))
                ->description($trend['description'])
                ->descriptionIcon($trend['icon'])
                ->color($trend['color'])
                ->icon('heroicon-o-check-circle'),
                
            Stat::make('Jaspel Pending Bulan Ini', 'Rp ' . number_format($thisMonthPending, 0, ',', '.'))
                ->description('Menunggu validasi')
                ->color($thisMonthPending > 0 ? 'warning' : 'success')
                ->icon('heroicon-o-clock'),
                
            Stat::make('Total Tahun Ini', 'Rp ' . number_format($yearTotal, 0, ',', '.'))
                ->description('Jaspel disetujui ' . Carbon::now()->year)
                ->color('primary')
                ->icon('heroicon-o-chart-bar-square'),
                
            Stat::make('Rata-rata Bulanan', 'Rp ' . number_format($yearTotal / max(1, now()->month), 0, ',', '.'))
                ->description('Berdasarkan ' . now()->month . ' bulan terakhir')
                ->color('info')
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