<?php

namespace App\Filament\Dokter\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Dokter;
use App\Models\Tindakan;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class MonthlyJaspelWidget extends ChartWidget
{
    protected static ?string $heading = 'Jaspel Bulanan';
    protected static ?string $description = 'Pendapatan jaspel dari tindakan yang disetujui';
    protected static ?int $sort = 2;
    
    protected function getData(): array
    {
        $user = Auth::user();
        $dokter = Dokter::where('user_id', $user->id)->first();
        
        if (!$dokter) {
            return [
                'datasets' => [],
                'labels' => [],
            ];
        }

        // Cache data for 10 minutes
        $cacheKey = "dokter_monthly_jaspel_{$dokter->id}";
        $data = Cache::remember($cacheKey, 600, function () use ($dokter) {
            $currentMonth = Carbon::now()->startOfMonth();
            $sixMonthsAgo = $currentMonth->copy()->subMonths(5);
            
            $monthlyData = [];
            $labels = [];
            
            for ($i = 0; $i < 6; $i++) {
                $month = $sixMonthsAgo->copy()->addMonths($i);
                $nextMonth = $month->copy()->addMonth();
                
                $jaspel = Tindakan::where('dokter_id', $dokter->id)
                    ->where('tanggal_tindakan', '>=', $month)
                    ->where('tanggal_tindakan', '<', $nextMonth)
                    ->where('status_validasi', 'disetujui')
                    ->sum('jasa_dokter');
                
                $monthlyData[] = floatval($jaspel);
                $labels[] = $month->translatedFormat('M Y');
            }
            
            return [
                'data' => $monthlyData,
                'labels' => $labels,
            ];
        });

        return [
            'datasets' => [
                [
                    'label' => 'Jaspel (Rp)',
                    'data' => $data['data'],
                    'backgroundColor' => [
                        'rgba(59, 130, 246, 0.1)',
                    ],
                    'borderColor' => [
                        'rgb(59, 130, 246)',
                    ],
                    'borderWidth' => 2,
                    'fill' => true,
                ],
            ],
            'labels' => $data['labels'],
        ];
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
                    'display' => false,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'callback' => 'function(value) { return "Rp " + value.toLocaleString("id-ID"); }',
                    ],
                ],
            ],
            'elements' => [
                'point' => [
                    'radius' => 4,
                    'hoverRadius' => 6,
                ],
            ],
        ];
    }
}