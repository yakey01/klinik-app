<?php

namespace App\Filament\Dokter\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Dokter;
use App\Models\Tindakan;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class TodayOverviewWidget extends BaseWidget
{
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

        // Cache stats for 5 minutes
        $cacheKey = "dokter_today_stats_{$dokter->id}";
        $stats = Cache::remember($cacheKey, 300, function () use ($dokter) {
            $today = Carbon::today();
            $yesterday = Carbon::yesterday();
            
            // Today's patients
            $patientsToday = Tindakan::where('dokter_id', $dokter->id)
                ->whereDate('tanggal_tindakan', $today)
                ->distinct('pasien_id')
                ->count();
                
            $patientsYesterday = Tindakan::where('dokter_id', $dokter->id)
                ->whereDate('tanggal_tindakan', $yesterday)
                ->distinct('pasien_id')
                ->count();
            
            // Today's procedures
            $proceduresToday = Tindakan::where('dokter_id', $dokter->id)
                ->whereDate('tanggal_tindakan', $today)
                ->count();
                
            $proceduresYesterday = Tindakan::where('dokter_id', $dokter->id)
                ->whereDate('tanggal_tindakan', $yesterday)
                ->count();
            
            // Today's earnings (estimated from pending tindakan)
            $earningsToday = Tindakan::where('dokter_id', $dokter->id)
                ->whereDate('tanggal_tindakan', $today)
                ->sum('jasa_dokter');
                
            $earningsYesterday = Tindakan::where('dokter_id', $dokter->id)
                ->whereDate('tanggal_tindakan', $yesterday)
                ->sum('jasa_dokter');
            
            // Today's approved earnings
            $approvedEarningsToday = Tindakan::where('dokter_id', $dokter->id)
                ->whereDate('tanggal_tindakan', $today)
                ->where('status_validasi', 'disetujui')
                ->sum('jasa_dokter');
            
            return [
                'patients_today' => $patientsToday,
                'patients_yesterday' => $patientsYesterday,
                'procedures_today' => $proceduresToday,
                'procedures_yesterday' => $proceduresYesterday,
                'earnings_today' => $earningsToday,
                'earnings_yesterday' => $earningsYesterday,
                'approved_earnings_today' => $approvedEarningsToday,
            ];
        });
        
        // Calculate trends
        $patientTrend = $this->calculateTrend($stats['patients_today'], $stats['patients_yesterday']);
        $procedureTrend = $this->calculateTrend($stats['procedures_today'], $stats['procedures_yesterday']);
        $earningsTrend = $this->calculateTrend($stats['earnings_today'], $stats['earnings_yesterday']);

        return [
            Stat::make('Pasien Hari Ini', $stats['patients_today'])
                ->description($patientTrend['description'])
                ->descriptionIcon($patientTrend['icon'])
                ->color($patientTrend['color'])
                ->icon('heroicon-o-users'),
                
            Stat::make('Tindakan Hari Ini', $stats['procedures_today'])
                ->description($procedureTrend['description'])
                ->descriptionIcon($procedureTrend['icon'])
                ->color($procedureTrend['color'])
                ->icon('heroicon-o-clipboard-document-list'),
                
            Stat::make('Estimasi Jaspel Hari Ini', 'Rp ' . number_format($stats['earnings_today'], 0, ',', '.'))
                ->description($earningsTrend['description'])
                ->descriptionIcon($earningsTrend['icon'])
                ->color($earningsTrend['color'])
                ->icon('heroicon-o-banknotes'),
                
            Stat::make('Jaspel Disetujui Hari Ini', 'Rp ' . number_format($stats['approved_earnings_today'], 0, ',', '.'))
                ->description('Jaspel yang sudah divalidasi')
                ->color('success')
                ->icon('heroicon-o-check-circle'),
        ];
    }
    
    private function calculateTrend($current, $previous): array
    {
        if ($previous == 0) {
            if ($current > 0) {
                return [
                    'description' => 'Naik dari kemarin',
                    'icon' => 'heroicon-m-arrow-trending-up',
                    'color' => 'success'
                ];
            } else {
                return [
                    'description' => 'Sama dengan kemarin',
                    'icon' => 'heroicon-m-minus',
                    'color' => 'gray'
                ];
            }
        }
        
        $change = (($current - $previous) / $previous) * 100;
        
        if ($change > 0) {
            return [
                'description' => sprintf('+%.1f%% dari kemarin', $change),
                'icon' => 'heroicon-m-arrow-trending-up',
                'color' => 'success'
            ];
        } elseif ($change < 0) {
            return [
                'description' => sprintf('%.1f%% dari kemarin', $change),
                'icon' => 'heroicon-m-arrow-trending-down',
                'color' => 'danger'
            ];
        } else {
            return [
                'description' => 'Sama dengan kemarin',
                'icon' => 'heroicon-m-minus',
                'color' => 'gray'
            ];
        }
    }
}