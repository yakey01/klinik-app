<?php

namespace App\Filament\Petugas\Widgets;

use App\Services\PetugasStatsService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Exception;

class PetugasStatsWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '60s';
    
    protected PetugasStatsService $statsService;
    
    public function __construct()
    {
        $this->statsService = new PetugasStatsService();
    }
    
    protected function getStats(): array
    {
        try {
            $userId = Auth::id();
            
            if (!$userId) {
                Log::warning('PetugasStatsWidget: No authenticated user');
                return $this->getEmptyStats();
            }
            
            $stats = $this->statsService->getDashboardStats($userId);
            
            return $this->formatStatsForWidget($stats);
            
        } catch (Exception $e) {
            Log::error('PetugasStatsWidget: Failed to get stats', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);
            
            return $this->getEmptyStats();
        }
    }
    
    protected function formatStatsForWidget(array $stats): array
    {
        $dailyStats = $stats['daily'];
        $validationSummary = $stats['validation_summary'];
        $performanceMetrics = $stats['performance_metrics'];
        
        $todayStats = $dailyStats['today'];
        $trends = $dailyStats['trends'];
        
        return [
            // Patient stats
            Stat::make('👥 Pasien Hari Ini', $todayStats['pasien_count'])
                ->description($this->getTrendDescription($trends['pasien_count']))
                ->descriptionIcon($this->getTrendIcon($trends['pasien_count']['direction']))
                ->color($this->getTrendColor($trends['pasien_count']['direction']))
                ->chart($this->getChartData($stats['trends']['charts']['daily_patients'])),
                
            // Income stats
            Stat::make('💰 Pendapatan Hari Ini', 'Rp ' . number_format($todayStats['pendapatan_sum'], 0, ',', '.'))
                ->description($this->getTrendDescription($trends['pendapatan_sum']))
                ->descriptionIcon($this->getTrendIcon($trends['pendapatan_sum']['direction']))
                ->color($this->getTrendColor($trends['pendapatan_sum']['direction']))
                ->chart($this->getChartData($stats['trends']['charts']['daily_income'])),
                
            // Expense stats
            Stat::make('💸 Pengeluaran Hari Ini', 'Rp ' . number_format($todayStats['pengeluaran_sum'], 0, ',', '.'))
                ->description($this->getTrendDescription($trends['pengeluaran_sum']))
                ->descriptionIcon($this->getTrendIcon($trends['pengeluaran_sum']['direction']))
                ->color($this->getExpenseTrendColor($trends['pengeluaran_sum']['direction']))
                ->chart($this->getChartData($stats['trends']['charts']['daily_income'])),
                
            // Treatment stats
            Stat::make('🏥 Tindakan Hari Ini', $todayStats['tindakan_count'])
                ->description($this->getTrendDescription($trends['tindakan_count']))
                ->descriptionIcon($this->getTrendIcon($trends['tindakan_count']['direction']))
                ->color($this->getTrendColor($trends['tindakan_count']['direction']))
                ->chart($this->getChartData($stats['trends']['charts']['daily_treatments'])),
                
            // Net income
            Stat::make('📊 Net Hari Ini', 'Rp ' . number_format($todayStats['net_income'], 0, ',', '.'))
                ->description($this->getTrendDescription($trends['net_income']))
                ->descriptionIcon($todayStats['net_income'] >= 0 ? 'heroicon-m-currency-dollar' : 'heroicon-m-exclamation-triangle')
                ->color($todayStats['net_income'] >= 0 ? 'success' : 'danger')
                ->chart($this->getChartData($stats['trends']['charts']['daily_income'])),
                
            // Validation summary
            Stat::make('📋 Validasi Pending', $validationSummary['pending_validations'])
                ->description($validationSummary['approval_rate'] . '% approval rate')
                ->descriptionIcon('heroicon-m-clock')
                ->color($validationSummary['pending_validations'] > 10 ? 'warning' : 'info')
                ->chart([$validationSummary['rejected_today'], $validationSummary['approved_today']]),
        ];
    }
    
    protected function getTrendDescription(array $trend): string
    {
        $percentage = abs($trend['percentage']);
        $direction = $trend['direction'];
        
        if ($direction === 'stable') {
            return 'Tidak ada perubahan';
        }
        
        $sign = $direction === 'up' ? '+' : '-';
        return $sign . number_format($percentage, 1) . '% dari kemarin';
    }
    
    protected function getTrendIcon(string $direction): string
    {
        return match ($direction) {
            'up' => 'heroicon-m-arrow-trending-up',
            'down' => 'heroicon-m-arrow-trending-down',
            'stable' => 'heroicon-m-minus',
            default => 'heroicon-m-minus',
        };
    }
    
    protected function getTrendColor(string $direction): string
    {
        return match ($direction) {
            'up' => 'success',
            'down' => 'warning',
            'stable' => 'gray',
            default => 'gray',
        };
    }
    
    protected function getExpenseTrendColor(string $direction): string
    {
        // For expenses, down is good (less spending)
        return match ($direction) {
            'up' => 'danger',
            'down' => 'success',
            'stable' => 'gray',
            default => 'gray',
        };
    }
    
    protected function getChartData(array $data): array
    {
        // Ensure we have at least 2 data points for chart
        if (empty($data)) {
            return [0, 0];
        }
        
        if (count($data) === 1) {
            return [0, $data[0]];
        }
        
        return array_slice($data, -7); // Last 7 days
    }
    
    protected function getEmptyStats(): array
    {
        return [
            Stat::make('👥 Pasien Hari Ini', 0)
                ->description('Tidak ada perubahan')
                ->descriptionIcon('heroicon-m-minus')
                ->color('gray')
                ->chart([0, 0]),
                
            Stat::make('💰 Pendapatan Hari Ini', 'Rp 0')
                ->description('Tidak ada perubahan')
                ->descriptionIcon('heroicon-m-minus')
                ->color('gray')
                ->chart([0, 0]),
                
            Stat::make('💸 Pengeluaran Hari Ini', 'Rp 0')
                ->description('Tidak ada perubahan')
                ->descriptionIcon('heroicon-m-minus')
                ->color('gray')
                ->chart([0, 0]),
                
            Stat::make('🏥 Tindakan Hari Ini', 0)
                ->description('Tidak ada perubahan')
                ->descriptionIcon('heroicon-m-minus')
                ->color('gray')
                ->chart([0, 0]),
                
            Stat::make('📊 Net Hari Ini', 'Rp 0')
                ->description('Tidak ada perubahan')
                ->descriptionIcon('heroicon-m-minus')
                ->color('gray')
                ->chart([0, 0]),
                
            Stat::make('📋 Validasi Pending', 0)
                ->description('0% approval rate')
                ->descriptionIcon('heroicon-m-clock')
                ->color('gray')
                ->chart([0, 0]),
        ];
    }
}