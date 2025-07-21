<?php

namespace App\Filament\Widgets;

use App\Models\Pendapatan;
use App\Models\Pengeluaran;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class FinancialSummaryWidget extends BaseWidget
{
    // protected static ?string $pollingInterval = null; // DISABLED - emergency polling removal
    
    protected function getStats(): array
    {
        $currentMonth = Carbon::now()->format('Y-m');
        $lastMonth = Carbon::now()->subMonth()->format('Y-m');
        
        // Current month income
        $currentIncome = Pendapatan::whereMonth('tanggal', Carbon::now()->month)
            ->whereYear('tanggal', Carbon::now()->year)
            ->where('status_validasi', 'disetujui')
            ->sum('nominal');
            
        // Last month income
        $lastIncome = Pendapatan::whereMonth('tanggal', Carbon::now()->subMonth()->month)
            ->whereYear('tanggal', Carbon::now()->subMonth()->year)
            ->where('status_validasi', 'disetujui')
            ->sum('nominal');
            
        // Current month expenses
        $currentExpenses = Pengeluaran::whereMonth('tanggal', Carbon::now()->month)
            ->whereYear('tanggal', Carbon::now()->year)
            ->where('status_validasi', 'disetujui')
            ->sum('nominal');
            
        // Last month expenses
        $lastExpenses = Pengeluaran::whereMonth('tanggal', Carbon::now()->subMonth()->month)
            ->whereYear('tanggal', Carbon::now()->subMonth()->year)
            ->where('status_validasi', 'disetujui')
            ->sum('nominal');
            
        // Calculate profit
        $currentProfit = $currentIncome - $currentExpenses;
        $lastProfit = $lastIncome - $lastExpenses;
        
        // Pending approvals
        $pendingIncome = Pendapatan::where('status_validasi', 'pending')->count();
        $pendingExpenses = Pengeluaran::where('status_validasi', 'pending')->count();
        
        // Calculate trends
        $incomeChange = $lastIncome > 0 ? (($currentIncome - $lastIncome) / $lastIncome) * 100 : 0;
        $expenseChange = $lastExpenses > 0 ? (($currentExpenses - $lastExpenses) / $lastExpenses) * 100 : 0;
        $profitChange = $lastProfit > 0 ? (($currentProfit - $lastProfit) / $lastProfit) * 100 : 0;
        
        return [
            Stat::make('Pendapatan Bulan Ini', 'Rp ' . number_format($currentIncome, 0, ',', '.'))
                ->description(($incomeChange >= 0 ? '+' : '') . number_format($incomeChange, 1) . '% dari bulan lalu')
                ->descriptionIcon($incomeChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($incomeChange >= 0 ? 'success' : 'danger')
                ->chart([
                    $lastIncome / 1000000,
                    $currentIncome / 1000000,
                ]),
                
            Stat::make('Pengeluaran Bulan Ini', 'Rp ' . number_format($currentExpenses, 0, ',', '.'))
                ->description(($expenseChange >= 0 ? '+' : '') . number_format($expenseChange, 1) . '% dari bulan lalu')
                ->descriptionIcon($expenseChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($expenseChange >= 0 ? 'danger' : 'success')
                ->chart([
                    $lastExpenses / 1000000,
                    $currentExpenses / 1000000,
                ]),
                
            Stat::make('Laba Bersih', 'Rp ' . number_format($currentProfit, 0, ',', '.'))
                ->description(($profitChange >= 0 ? '+' : '') . number_format($profitChange, 1) . '% dari bulan lalu')
                ->descriptionIcon($profitChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($currentProfit >= 0 ? 'success' : 'danger')
                ->chart([
                    $lastProfit / 1000000,
                    $currentProfit / 1000000,
                ]),
                
            Stat::make('Menunggu Persetujuan', $pendingIncome + $pendingExpenses)
                ->description($pendingIncome . ' pendapatan, ' . $pendingExpenses . ' pengeluaran')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),
        ];
    }
}