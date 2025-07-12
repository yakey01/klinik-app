<?php

namespace App\Filament\Widgets;

use App\Models\Pendapatan;
use App\Models\Pengeluaran;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class FinancialChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Analisis Keuangan - 12 Bulan Terakhir';
    
    protected static string $color = 'info';
    
    protected static ?string $pollingInterval = '30s';
    
    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $months = [];
        $incomeData = [];
        $expenseData = [];
        $profitData = [];
        
        for ($i = 11; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $months[] = $month->format('M Y');
            
            $income = Pendapatan::whereMonth('tanggal', $month->month)
                ->whereYear('tanggal', $month->year)
                ->where('status_validasi', 'disetujui')
                ->sum('nominal');
                
            $expense = Pengeluaran::whereMonth('tanggal', $month->month)
                ->whereYear('tanggal', $month->year)
                ->where('status_validasi', 'disetujui')
                ->sum('nominal');
                
            $incomeData[] = $income / 1000000; // Convert to millions
            $expenseData[] = $expense / 1000000; // Convert to millions
            $profitData[] = ($income - $expense) / 1000000; // Convert to millions
        }
        
        return [
            'datasets' => [
                [
                    'label' => 'Pendapatan (Juta Rupiah)',
                    'data' => $incomeData,
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                    'borderColor' => 'rgb(34, 197, 94)',
                    'borderWidth' => 2,
                    'fill' => true,
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Pengeluaran (Juta Rupiah)',
                    'data' => $expenseData,
                    'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
                    'borderColor' => 'rgb(239, 68, 68)',
                    'borderWidth' => 2,
                    'fill' => true,
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Laba Bersih (Juta Rupiah)',
                    'data' => $profitData,
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'borderColor' => 'rgb(59, 130, 246)',
                    'borderWidth' => 2,
                    'fill' => true,
                    'tension' => 0.4,
                ],
            ],
            'labels' => $months,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
    
    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'callback' => 'function(value) { return "Rp " + value + "M"; }',
                    ],
                ],
            ],
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'top',
                ],
                'tooltip' => [
                    'callbacks' => [
                        'label' => 'function(context) { return context.dataset.label + ": Rp " + context.parsed.y.toFixed(1) + "M"; }',
                    ],
                ],
            ],
        ];
    }
}