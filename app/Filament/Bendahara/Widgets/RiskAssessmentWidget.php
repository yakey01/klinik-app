<?php

namespace App\Filament\Bendahara\Widgets;

use App\Models\Pendapatan;
use App\Models\Pengeluaran;
use App\Models\Jaspel;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class RiskAssessmentWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '600s';
    
    protected function getStats(): array
    {
        return Cache::remember('bendahara_risk_assessment', now()->addMinutes(30), function () {
            // High-value transactions (above 5M)
            $highValueThreshold = 5000000;
            $highValueTransactions = Pendapatan::where('nominal', '>', $highValueThreshold)
                ->whereMonth('tanggal', now()->month)
                ->count() +
                Pengeluaran::where('nominal', '>', $highValueThreshold)
                ->whereMonth('tanggal', now()->month)
                ->count();
            
            // Unusual patterns detection
            $duplicateAmounts = Pendapatan::whereDate('tanggal', now())
                ->select('nominal')
                ->groupBy('nominal')
                ->havingRaw('COUNT(*) > 3')
                ->count();
            
            // Compliance score
            $totalTransactions = Pendapatan::whereMonth('tanggal', now()->month)->count();
            $compliantTransactions = Pendapatan::whereMonth('tanggal', now()->month)
                ->whereNotNull('nama_pendapatan')
                ->whereNotNull('jenis_pendapatan')
                ->count();
            
            $complianceScore = $totalTransactions > 0 ? round(($compliantTransactions / $totalTransactions) * 100) : 100;
            
            // Risk level calculation
            $riskLevel = $this->calculateRiskLevel($highValueTransactions, $duplicateAmounts, $complianceScore);
            
            return [
                Stat::make('Risk Level', ucfirst($riskLevel))
                    ->description($this->getRiskDescription($riskLevel))
                    ->descriptionIcon($this->getRiskIcon($riskLevel))
                    ->color($this->getRiskColor($riskLevel)),
                    
                Stat::make('High Value Transactions', $highValueTransactions)
                    ->description('Transaksi > Rp 5M bulan ini')
                    ->descriptionIcon('heroicon-m-exclamation-triangle')
                    ->color($highValueTransactions > 10 ? 'warning' : 'info'),
                    
                Stat::make('Compliance Score', $complianceScore . '%')
                    ->description($complianceScore >= 95 ? 'Excellent compliance' : 'Review required')
                    ->descriptionIcon('heroicon-m-shield-check')
                    ->color($complianceScore >= 95 ? 'success' : ($complianceScore >= 85 ? 'warning' : 'danger')),
            ];
        });
    }
    
    private function calculateRiskLevel($highValueCount, $unusualPatterns, $complianceScore): string
    {
        $riskPoints = 0;
        
        if ($highValueCount > 20) $riskPoints += 3;
        elseif ($highValueCount > 10) $riskPoints += 2;
        elseif ($highValueCount > 5) $riskPoints += 1;
        
        if ($unusualPatterns > 5) $riskPoints += 3;
        elseif ($unusualPatterns > 2) $riskPoints += 2;
        elseif ($unusualPatterns > 0) $riskPoints += 1;
        
        if ($complianceScore < 70) $riskPoints += 3;
        elseif ($complianceScore < 85) $riskPoints += 2;
        elseif ($complianceScore < 95) $riskPoints += 1;
        
        if ($riskPoints >= 6) return 'high';
        if ($riskPoints >= 3) return 'medium';
        return 'low';
    }
    
    private function getRiskDescription($level): string
    {
        return match($level) {
            'high' => 'Requires immediate attention',
            'medium' => 'Monitor closely',
            'low' => 'Normal operations',
            default => 'Unknown'
        };
    }
    
    private function getRiskIcon($level): string
    {
        return match($level) {
            'high' => 'heroicon-m-exclamation-triangle',
            'medium' => 'heroicon-m-exclamation-circle',
            'low' => 'heroicon-m-check-circle',
            default => 'heroicon-m-question-mark-circle'
        };
    }
    
    private function getRiskColor($level): string
    {
        return match($level) {
            'high' => 'danger',
            'medium' => 'warning',
            'low' => 'success',
            default => 'gray'
        };
    }
}