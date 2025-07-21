<?php

namespace App\Filament\Bendahara\Widgets;

use App\Models\Pendapatan;
use App\Models\Pengeluaran;
use App\Models\Jaspel;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Cache;

class FinancialTrendsChart extends ChartWidget
{
    protected static ?string $heading = 'Tren Keuangan 6 Bulan Terakhir';
    
    // protected static ?string $pollingInterval = null; // DISABLED - emergency polling removal
    
    protected static ?int $sort = 2;

    protected function getData(): array
    {
        return Cache::remember('bendahara_financial_trends', now()->addHours(1), function () {
            $months = [];
            $revenue = [];
            $expenses = [];
            $jaspel = [];
            
            for ($i = 5; $i >= 0; $i--) {
                $date = now()->subMonths($i);
                $months[] = $date->format('M Y');
                
                // Monthly revenue
                $monthlyRevenue = Pendapatan::whereMonth('tanggal', $date->month)
                    ->whereYear('tanggal', $date->year)
                    ->where('status_validasi', 'disetujui')
                    ->sum('nominal');
                
                // Monthly expenses
                $monthlyExpenses = Pengeluaran::whereMonth('tanggal', $date->month)
                    ->whereYear('tanggal', $date->year)
                    ->sum('nominal');
                
                // Monthly jaspel
                $monthlyJaspel = Jaspel::whereMonth('tanggal', $date->month)
                    ->whereYear('tanggal', $date->year)
                    ->sum('nominal');
                
                $revenue[] = $monthlyRevenue / 1000000; // Convert to millions
                $expenses[] = $monthlyExpenses / 1000000;
                $jaspel[] = $monthlyJaspel / 1000000;
            }
            
            return [
                'datasets' => [
                    [
                        'label' => 'Pendapatan',
                        'data' => $revenue,
                        'borderColor' => '#10b981',
                        'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                        'fill' => true,
                    ],
                    [
                        'label' => 'Pengeluaran',
                        'data' => $expenses,
                        'borderColor' => '#ef4444',
                        'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
                        'fill' => true,
                    ],
                    [
                        'label' => 'Jaspel',
                        'data' => $jaspel,
                        'borderColor' => '#3b82f6',
                        'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                        'fill' => true,
                    ],
                ],
                'labels' => $months,
            ];
        });
    }

    protected function getType(): string
    {
        return 'line';
    }
    
    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'top',
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'title' => [
                        'display' => true,
                        'text' => 'Nilai (Juta Rupiah)',
                    ],
                ],
                'x' => [
                    'title' => [
                        'display' => true,
                        'text' => 'Bulan',
                    ],
                ],
            ],
            'responsive' => true,
            'maintainAspectRatio' => false,
        ];
    }
}