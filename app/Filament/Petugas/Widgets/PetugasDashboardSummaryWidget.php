<?php

namespace App\Filament\Petugas\Widgets;

use App\Models\Pendapatan;
use App\Models\PendapatanHarian;
use App\Models\PengeluaranHarian;
use App\Models\JumlahPasienHarian;
use App\Models\Tindakan;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Exception;

class PetugasDashboardSummaryWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '60s';
    
    protected static ?int $sort = 1;
    
    protected function getStats(): array
    {
        try {
            $currentUser = Auth::id();
            
            if (!$currentUser) {
                Log::warning('PetugasDashboardSummaryWidget: No authenticated user');
                return $this->getEmptyStats();
            }
            
            $summary = $this->getDailySummary($currentUser);
            
            return [
                // Today's Patients
                Stat::make('👥 Pasien Hari Ini', $summary['today']['pasien'])
                    ->description($this->getTrendDescription($summary['changes']['pasien']))
                    ->descriptionIcon($this->getTrendIcon($summary['changes']['pasien']))
                    ->color($this->getTrendColor($summary['changes']['pasien']))
                    ->chart($this->getRandomChart()),
                    
                // Today's Income
                Stat::make('💰 Pendapatan Hari Ini', 'Rp ' . number_format($summary['today']['pendapatan'], 0, ',', '.'))
                    ->description($this->getTrendDescription($summary['changes']['pendapatan']))
                    ->descriptionIcon($this->getTrendIcon($summary['changes']['pendapatan']))
                    ->color($this->getTrendColor($summary['changes']['pendapatan']))
                    ->chart($this->getRandomChart()),
                    
                // Today's Expenses
                Stat::make('💸 Pengeluaran Hari Ini', 'Rp ' . number_format($summary['today']['pengeluaran'], 0, ',', '.'))
                    ->description($this->getTrendDescription($summary['changes']['pengeluaran']))
                    ->descriptionIcon($this->getTrendIcon($summary['changes']['pengeluaran']))
                    ->color($this->getExpenseTrendColor($summary['changes']['pengeluaran']))
                    ->chart($this->getRandomChart()),
                    
                // Today's Procedures
                Stat::make('🏥 Tindakan Hari Ini', $summary['today']['tindakan'])
                    ->description($this->getTrendDescription($summary['changes']['tindakan']))
                    ->descriptionIcon($this->getTrendIcon($summary['changes']['tindakan']))
                    ->color($this->getTrendColor($summary['changes']['tindakan']))
                    ->chart($this->getRandomChart()),
                    
                // Net Income
                Stat::make('📊 Net Hari Ini', 'Rp ' . number_format($summary['today']['net_income'], 0, ',', '.'))
                    ->description($this->getTrendDescription($summary['changes']['net_income']))
                    ->descriptionIcon($summary['today']['net_income'] >= 0 ? 'heroicon-m-currency-dollar' : 'heroicon-m-exclamation-triangle')
                    ->color($summary['today']['net_income'] >= 0 ? 'success' : 'danger')
                    ->chart($this->getRandomChart()),
                    
                // Input Progress
                Stat::make('📝 Total Input Hari Ini', $summary['today']['total_input'])
                    ->description('Data yang diinput hari ini')
                    ->descriptionIcon('heroicon-m-document-text')
                    ->color('info')
                    ->chart($this->getRandomChart()),
            ];
            
        } catch (Exception $e) {
            Log::error('PetugasDashboardSummaryWidget: Failed to get stats', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return $this->getEmptyStats();
        }
    }
    
    private function getDailySummary(int $userId): array
    {
        $today = now()->toDateString();
        $yesterday = now()->subDay()->toDateString();
        
        // Today's data
        $todayPendapatan = PendapatanHarian::whereDate('tanggal', $today)
            ->where('input_by', $userId)
            ->sum('nominal');
            
        $todayPengeluaran = PengeluaranHarian::whereDate('tanggal', $today)
            ->where('input_by', $userId)
            ->sum('nominal');
            
        $todayPasien = JumlahPasienHarian::whereDate('tanggal', $today)
            ->where('input_by', $userId)
            ->sum('jumlah_pasien');
            
        $todayTindakan = Tindakan::whereDate('tanggal', $today)
            ->where('input_by', $userId)
            ->count();
            
        // Total input count
        $totalInput = PendapatanHarian::whereDate('tanggal', $today)->where('input_by', $userId)->count() +
                     PengeluaranHarian::whereDate('tanggal', $today)->where('input_by', $userId)->count() +
                     JumlahPasienHarian::whereDate('tanggal', $today)->where('input_by', $userId)->count() +
                     $todayTindakan;
        
        // Yesterday's data for comparison
        $yesterdayPendapatan = PendapatanHarian::whereDate('tanggal', $yesterday)
            ->where('input_by', $userId)
            ->sum('nominal');
            
        $yesterdayPengeluaran = PengeluaranHarian::whereDate('tanggal', $yesterday)
            ->where('input_by', $userId)
            ->sum('nominal');
            
        $yesterdayPasien = JumlahPasienHarian::whereDate('tanggal', $yesterday)
            ->where('input_by', $userId)
            ->sum('jumlah_pasien');
            
        $yesterdayTindakan = Tindakan::whereDate('tanggal', $yesterday)
            ->where('input_by', $userId)
            ->count();
        
        return [
            'today' => [
                'pendapatan' => $todayPendapatan,
                'pengeluaran' => $todayPengeluaran,
                'pasien' => $todayPasien,
                'tindakan' => $todayTindakan,
                'net_income' => $todayPendapatan - $todayPengeluaran,
                'total_input' => $totalInput,
            ],
            'yesterday' => [
                'pendapatan' => $yesterdayPendapatan,
                'pengeluaran' => $yesterdayPengeluaran,
                'pasien' => $yesterdayPasien,
                'tindakan' => $yesterdayTindakan,
                'net_income' => $yesterdayPendapatan - $yesterdayPengeluaran,
            ],
            'changes' => [
                'pendapatan' => $this->calculatePercentageChange($todayPendapatan, $yesterdayPendapatan),
                'pengeluaran' => $this->calculatePercentageChange($todayPengeluaran, $yesterdayPengeluaran),
                'pasien' => $this->calculatePercentageChange($todayPasien, $yesterdayPasien),
                'tindakan' => $this->calculatePercentageChange($todayTindakan, $yesterdayTindakan),
                'net_income' => $this->calculatePercentageChange(
                    $todayPendapatan - $todayPengeluaran,
                    $yesterdayPendapatan - $yesterdayPengeluaran
                ),
            ],
        ];
    }
    
    private function calculatePercentageChange($current, $previous): float
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }
        
        return round((($current - $previous) / $previous) * 100, 2);
    }
    
    private function getTrendDescription(float $change): string
    {
        $percentage = abs($change);
        
        if ($change == 0) {
            return 'Tidak ada perubahan';
        }
        
        $sign = $change > 0 ? '+' : '-';
        return $sign . number_format($percentage, 1) . '% dari kemarin';
    }
    
    private function getTrendIcon(float $change): string
    {
        return match (true) {
            $change > 0 => 'heroicon-m-arrow-trending-up',
            $change < 0 => 'heroicon-m-arrow-trending-down',
            default => 'heroicon-m-minus',
        };
    }
    
    private function getTrendColor(float $change): string
    {
        return match (true) {
            $change > 0 => 'success',
            $change < 0 => 'warning',
            default => 'gray',
        };
    }
    
    private function getExpenseTrendColor(float $change): string
    {
        // For expenses, down is good (less spending)
        return match (true) {
            $change > 0 => 'danger',
            $change < 0 => 'success',
            default => 'gray',
        };
    }
    
    private function getRandomChart(): array
    {
        return [
            rand(40, 100),
            rand(40, 100),
            rand(40, 100),
            rand(40, 100),
            rand(40, 100),
            rand(40, 100),
            rand(40, 100),
        ];
    }
    
    private function getEmptyStats(): array
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
                
            Stat::make('📝 Total Input Hari Ini', 0)
                ->description('Tidak ada perubahan')
                ->descriptionIcon('heroicon-m-minus')
                ->color('gray')
                ->chart([0, 0]),
        ];
    }
}