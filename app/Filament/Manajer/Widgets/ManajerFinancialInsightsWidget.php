<?php

namespace App\Filament\Manajer\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Pendapatan;
use App\Models\Pengeluaran;
use App\Models\Pasien;
use App\Models\Tindakan;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ManajerFinancialInsightsWidget extends ChartWidget
{
    protected static ?string $heading = 'Financial Insights';
    protected static ?string $description = 'Revenue trends and financial performance metrics';
    
    protected int | string | array $columnSpan = 'full';
    
    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $months = [];
        $revenues = [];
        $expenses = [];
        $profits = [];
        
        // Get last 6 months data
        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $months[] = $date->format('M Y');
            
            $monthlyRevenue = Pendapatan::whereMonth('created_at', $date->month)
                ->whereYear('created_at', $date->year)
                ->sum('jumlah');
                
            $monthlyExpense = Pengeluaran::whereMonth('created_at', $date->month)
                ->whereYear('created_at', $date->year)
                ->sum('jumlah');
                
            $revenues[] = $monthlyRevenue;
            $expenses[] = $monthlyExpense;
            $profits[] = $monthlyRevenue - $monthlyExpense;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Revenue',
                    'data' => $revenues,
                    'borderColor' => '#10B981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'fill' => true,
                ],
                [
                    'label' => 'Expenses',
                    'data' => $expenses,
                    'borderColor' => '#EF4444',
                    'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
                    'fill' => true,
                ],
                [
                    'label' => 'Profit',
                    'data' => $profits,
                    'borderColor' => '#6366F1',
                    'backgroundColor' => 'rgba(99, 102, 241, 0.1)',
                    'fill' => true,
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
            'plugins' => [
                'legend' => [
                    'position' => 'top',
                    'labels' => [
                        'usePointStyle' => true,
                        'padding' => 20,
                    ],
                ],
                'tooltip' => [
                    'mode' => 'index',
                    'intersect' => false,
                    'callbacks' => [
                        'label' => 'function(context) {
                            return context.dataset.label + ": Rp " + new Intl.NumberFormat("id-ID").format(context.parsed.y);
                        }'
                    ],
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'callback' => 'function(value) {
                            return "Rp " + new Intl.NumberFormat("id-ID", {notation: "compact"}).format(value);
                        }',
                    ],
                ],
            ],
            'elements' => [
                'line' => [
                    'tension' => 0.4,
                ],
                'point' => [
                    'radius' => 4,
                    'hoverRadius' => 6,
                ],
            ],
        ];
    }

    public function getDescription(): ?string
    {
        $currentMonth = Carbon::now();
        $currentRevenue = Pendapatan::whereMonth('created_at', $currentMonth->month)
            ->whereYear('created_at', $currentMonth->year)
            ->sum('jumlah');
        
        $currentExpenses = Pengeluaran::whereMonth('created_at', $currentMonth->month)
            ->whereYear('created_at', $currentMonth->year)
            ->sum('jumlah');
            
        $currentProfit = $currentRevenue - $currentExpenses;
        $profitMargin = $currentRevenue > 0 ? ($currentProfit / $currentRevenue) * 100 : 0;
        
        return "Current month profit margin: " . round($profitMargin, 1) . "%";
    }

    public function getFinancialMetrics(): array
    {
        $currentMonth = Carbon::now();
        $previousMonth = Carbon::now()->subMonth();
        
        // Current Month Metrics
        $currentRevenue = Pendapatan::whereMonth('created_at', $currentMonth->month)
            ->whereYear('created_at', $currentMonth->year)
            ->sum('jumlah');
            
        $currentExpenses = Pengeluaran::whereMonth('created_at', $currentMonth->month)
            ->whereYear('created_at', $currentMonth->year)
            ->sum('jumlah');
            
        $currentProfit = $currentRevenue - $currentExpenses;
        
        // Previous Month Metrics
        $previousRevenue = Pendapatan::whereMonth('created_at', $previousMonth->month)
            ->whereYear('created_at', $previousMonth->year)
            ->sum('jumlah');
            
        $previousExpenses = Pengeluaran::whereMonth('created_at', $previousMonth->month)
            ->whereYear('created_at', $previousMonth->year)
            ->sum('jumlah');
            
        $previousProfit = $previousRevenue - $previousExpenses;
        
        // Growth Calculations
        $revenueGrowth = $previousRevenue > 0 ? (($currentRevenue - $previousRevenue) / $previousRevenue) * 100 : 0;
        $expenseGrowth = $previousExpenses > 0 ? (($currentExpenses - $previousExpenses) / $previousExpenses) * 100 : 0;
        $profitGrowth = $previousProfit > 0 ? (($currentProfit - $previousProfit) / $previousProfit) * 100 : 0;
        
        // Average per patient
        $currentPatients = Pasien::whereMonth('created_at', $currentMonth->month)
            ->whereYear('created_at', $currentMonth->year)
            ->count();
            
        $avgRevenuePerPatient = $currentPatients > 0 ? $currentRevenue / $currentPatients : 0;
        
        // Revenue by service type
        $revenueByService = Tindakan::join('jenis_tindakan', 'tindakan.jenis_tindakan_id', '=', 'jenis_tindakan.id')
            ->whereMonth('tindakan.created_at', $currentMonth->month)
            ->whereYear('tindakan.created_at', $currentMonth->year)
            ->select('jenis_tindakan.kategori', DB::raw('SUM(tindakan.harga) as total'))
            ->groupBy('jenis_tindakan.kategori')
            ->orderBy('total', 'desc')
            ->get();
        
        return [
            'current_metrics' => [
                'revenue' => $currentRevenue,
                'expenses' => $currentExpenses,
                'profit' => $currentProfit,
                'profit_margin' => $currentRevenue > 0 ? ($currentProfit / $currentRevenue) * 100 : 0,
                'avg_revenue_per_patient' => $avgRevenuePerPatient,
            ],
            'growth_metrics' => [
                'revenue_growth' => $revenueGrowth,
                'expense_growth' => $expenseGrowth,
                'profit_growth' => $profitGrowth,
            ],
            'revenue_by_service' => $revenueByService,
            'formatted' => [
                'revenue' => 'Rp ' . number_format($currentRevenue, 0, ',', '.'),
                'expenses' => 'Rp ' . number_format($currentExpenses, 0, ',', '.'),
                'profit' => 'Rp ' . number_format($currentProfit, 0, ',', '.'),
                'avg_revenue_per_patient' => 'Rp ' . number_format($avgRevenuePerPatient, 0, ',', '.'),
            ],
        ];
    }

    public static function canView(): bool
    {
        return auth()->user()?->hasRole('manajer') ?? false;
    }
}