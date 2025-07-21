<?php

namespace App\Filament\Bendahara\Widgets;

use App\Models\Pendapatan;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class RevenueQualityWidget extends BaseWidget
{
    // protected static ?string $pollingInterval = null; // DISABLED - emergency polling removal
    
    protected function getStats(): array
    {
        return Cache::remember('bendahara_revenue_quality', now()->addMinutes(20), function () {
            $currentMonth = now();
            
            // Revenue quality metrics
            $approvedRevenue = Pendapatan::whereMonth('tanggal', $currentMonth->month)
                ->whereYear('tanggal', $currentMonth->year)
                ->where('status_validasi', 'disetujui')
                ->sum('nominal');
                
            $rejectedRevenue = Pendapatan::whereMonth('tanggal', $currentMonth->month)
                ->whereYear('tanggal', $currentMonth->year)
                ->where('status_validasi', 'ditolak')
                ->sum('nominal');
                
            $totalSubmitted = $approvedRevenue + $rejectedRevenue;
            
            $approvalRate = $totalSubmitted > 0 ? round(($approvedRevenue / $totalSubmitted) * 100, 1) : 0;
            $rejectionRate = $totalSubmitted > 0 ? round(($rejectedRevenue / $totalSubmitted) * 100, 1) : 0;
            
            // Count transactions
            $approvedCount = Pendapatan::whereMonth('tanggal', $currentMonth->month)
                ->whereYear('tanggal', $currentMonth->year)
                ->where('status_validasi', 'disetujui')
                ->count();
                
            $rejectedCount = Pendapatan::whereMonth('tanggal', $currentMonth->month)
                ->whereYear('tanggal', $currentMonth->year)
                ->where('status_validasi', 'ditolak')
                ->count();
            
            return [
                Stat::make('Approval Rate', $approvalRate . '%')
                    ->description($approvedCount . ' transaksi disetujui')
                    ->descriptionIcon('heroicon-m-check-circle')
                    ->color($approvalRate >= 95 ? 'success' : ($approvalRate >= 85 ? 'warning' : 'danger')),
                    
                Stat::make('Rejection Rate', $rejectionRate . '%')
                    ->description($rejectedCount . ' transaksi ditolak')
                    ->descriptionIcon('heroicon-m-x-circle')
                    ->color($rejectionRate <= 5 ? 'success' : ($rejectionRate <= 15 ? 'warning' : 'danger')),
                    
                Stat::make('Revenue Quality Score', $this->calculateQualityScore($approvalRate))
                    ->description($this->getQualityDescription($this->calculateQualityScore($approvalRate)))
                    ->descriptionIcon('heroicon-m-star')
                    ->color($this->getQualityColor($this->calculateQualityScore($approvalRate))),
            ];
        });
    }
    
    private function calculateQualityScore($approvalRate): int
    {
        if ($approvalRate >= 95) return 100;
        if ($approvalRate >= 90) return 90;
        if ($approvalRate >= 85) return 80;
        if ($approvalRate >= 75) return 70;
        return 60;
    }
    
    private function getQualityDescription($score): string
    {
        if ($score >= 95) return 'Excellent';
        if ($score >= 85) return 'Good';
        if ($score >= 75) return 'Fair';
        return 'Needs Improvement';
    }
    
    private function getQualityColor($score): string
    {
        if ($score >= 95) return 'success';
        if ($score >= 85) return 'info';
        if ($score >= 75) return 'warning';
        return 'danger';
    }
}