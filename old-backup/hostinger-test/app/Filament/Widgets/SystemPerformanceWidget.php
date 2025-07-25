<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class SystemPerformanceWidget extends ChartWidget
{
    protected static ?string $heading = 'System Performance';
    
    protected static ?int $sort = 2;
    
    protected static ?string $pollingInterval = '15s';
    
    protected static ?string $maxHeight = '300px';
    
    protected int | string | array $columnSpan = 'full';

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $data = Cache::remember('system_performance_data', 60, function () {
            // Get performance metrics for the last 24 hours
            $hours = collect(range(0, 23))->map(function ($hour) {
                $timestamp = now()->subHours(23 - $hour);
                
                return [
                    'hour' => $timestamp->format('H:i'),
                    'users_online' => $this->getUsersOnline($timestamp),
                    'transactions' => $this->getTransactionsCount($timestamp),
                    'response_time' => $this->getAverageResponseTime($timestamp),
                ];
            });

            return $hours;
        });

        return [
            'datasets' => [
                [
                    'label' => 'Users Online',
                    'data' => $data->pluck('users_online')->toArray(),
                    'borderColor' => 'rgb(59, 130, 246)',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'tension' => 0.1,
                ],
                [
                    'label' => 'Transactions/Hour',
                    'data' => $data->pluck('transactions')->toArray(),
                    'borderColor' => 'rgb(34, 197, 94)',
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                    'tension' => 0.1,
                ],
                [
                    'label' => 'Response Time (ms)',
                    'data' => $data->pluck('response_time')->toArray(),
                    'borderColor' => 'rgb(251, 146, 60)',
                    'backgroundColor' => 'rgba(251, 146, 60, 0.1)',
                    'tension' => 0.1,
                ],
            ],
            'labels' => $data->pluck('hour')->toArray(),
        ];
    }

    private function getUsersOnline($timestamp)
    {
        // Simulate users online based on time of day
        $hour = (int) $timestamp->format('H');
        
        // Peak hours: 9-11 AM and 2-4 PM
        if (($hour >= 9 && $hour <= 11) || ($hour >= 14 && $hour <= 16)) {
            return rand(15, 25);
        }
        
        // Normal hours
        if ($hour >= 8 && $hour <= 17) {
            return rand(8, 15);
        }
        
        // Off hours
        return rand(1, 5);
    }

    private function getTransactionsCount($timestamp)
    {
        // Get actual transaction count from database
        return DB::table('pendapatan')
            ->where('created_at', '>=', $timestamp->startOfHour())
            ->where('created_at', '<', $timestamp->copy()->addHour())
            ->count() + 
            DB::table('pengeluaran')
            ->where('created_at', '>=', $timestamp->startOfHour())
            ->where('created_at', '<', $timestamp->copy()->addHour())
            ->count();
    }

    private function getAverageResponseTime($timestamp)
    {
        // Simulate response time based on load
        $hour = (int) $timestamp->format('H');
        
        // Peak hours have higher response times
        if (($hour >= 9 && $hour <= 11) || ($hour >= 14 && $hour <= 16)) {
            return rand(150, 300);
        }
        
        // Normal hours
        if ($hour >= 8 && $hour <= 17) {
            return rand(80, 150);
        }
        
        // Off hours - faster response
        return rand(50, 100);
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
                    'grid' => [
                        'display' => true,
                        'color' => 'rgba(0, 0, 0, 0.1)',
                    ],
                ],
                'x' => [
                    'grid' => [
                        'display' => false,
                    ],
                ],
            ],
        ];
    }
    
    public static function canView(): bool
    {
        return auth()->user()?->hasRole(['admin', 'manajer']) ?? false;
    }
}