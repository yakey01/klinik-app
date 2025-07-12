<?php

namespace App\Filament\Widgets;

use App\Models\Tindakan;
use Filament\Widgets\ChartWidget;

class ProcedureTypesChart extends ChartWidget
{
    protected static ?string $heading = 'Tindakan Berdasarkan Jenis';

    protected static ?int $sort = 3;

    protected function getData(): array
    {
        $data = Tindakan::with('jenisTindakan')
            ->selectRaw('jenis_tindakan_id, COUNT(*) as count')
            ->groupBy('jenis_tindakan_id')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                return [
                    'label' => $item->jenisTindakan->nama ?? 'Unknown',
                    'value' => $item->count,
                ];
            });

        return [
            'datasets' => [
                [
                    'data' => $data->pluck('value')->toArray(),
                    'backgroundColor' => [
                        '#3b82f6',
                        '#10b981',
                        '#f59e0b',
                        '#ef4444',
                        '#8b5cf6',
                        '#f97316',
                        '#06b6d4',
                        '#84cc16',
                        '#ec4899',
                        '#64748b',
                    ],
                ],
            ],
            'labels' => $data->pluck('label')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}