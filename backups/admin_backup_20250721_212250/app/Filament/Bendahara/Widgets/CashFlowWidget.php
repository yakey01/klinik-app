<?php

namespace App\Filament\Bendahara\Widgets;

use App\Models\Pendapatan;
use App\Models\Pengeluaran;
use App\Models\Jaspel;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class CashFlowWidget extends BaseWidget
{
    // protected static ?string $pollingInterval = null; // DISABLED - emergency polling removal
    
    protected function getStats(): array
    {
        return Cache::remember('bendahara_cash_flow', now()->addMinutes(10), function () {
            $currentMonth = now();
            
            // Current month cash flow
            $inflow = Pendapatan::whereMonth('tanggal', $currentMonth->month)
                ->whereYear('tanggal', $currentMonth->year)
                ->where('status_validasi', 'disetujui')
                ->sum('nominal');
                
            $outflow = Pengeluaran::whereMonth('tanggal', $currentMonth->month)
                ->whereYear('tanggal', $currentMonth->year)
                ->sum('nominal') +
                Jaspel::whereMonth('tanggal', $currentMonth->month)
                ->whereYear('tanggal', $currentMonth->year)
                ->sum('nominal');
            
            $netCashFlow = $inflow - $outflow;
            $liquidityRatio = $outflow > 0 ? round($inflow / $outflow, 2) : 0;
            
            return [
                Stat::make('Cash Inflow', 'Rp ' . number_format($inflow / 1000000, 1) . 'M')
                    ->description('Pendapatan tervalidasi')
                    ->descriptionIcon('heroicon-m-arrow-up-circle')
                    ->color('success'),
                    
                Stat::make('Cash Outflow', 'Rp ' . number_format($outflow / 1000000, 1) . 'M')
                    ->description('Pengeluaran + Jaspel')
                    ->descriptionIcon('heroicon-m-arrow-down-circle')
                    ->color('warning'),
                    
                Stat::make('Net Cash Flow', 'Rp ' . number_format($netCashFlow / 1000000, 1) . 'M')
                    ->description($netCashFlow >= 0 ? 'Surplus' : 'Defisit')
                    ->descriptionIcon($netCashFlow >= 0 ? 'heroicon-m-plus-circle' : 'heroicon-m-minus-circle')
                    ->color($netCashFlow >= 0 ? 'success' : 'danger'),
            ];
        });
    }
}