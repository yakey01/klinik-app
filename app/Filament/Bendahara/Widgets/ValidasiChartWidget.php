<?php

namespace App\Filament\Bendahara\Widgets;

use App\Models\PendapatanHarian;
use App\Models\PengeluaranHarian;
use App\Models\Tindakan;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class ValidasiChartWidget extends ChartWidget
{
    protected static ?string $heading = '📊 Status Validasi Bulanan';

    protected function getData(): array
    {
        $currentMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        // Count validations by status for current month
        $pendapatanApproved = PendapatanHarian::whereBetween('tanggal_input', [$currentMonth, $endOfMonth])
            ->where('status_validasi', 'disetujui')
            ->count();

        $pendapatanPending = PendapatanHarian::whereBetween('tanggal_input', [$currentMonth, $endOfMonth])
            ->where('status_validasi', 'pending')
            ->count();

        $pengeluaranApproved = PengeluaranHarian::whereBetween('tanggal_input', [$currentMonth, $endOfMonth])
            ->where('status_validasi', 'disetujui')
            ->count();

        $pengeluaranPending = PengeluaranHarian::whereBetween('tanggal_input', [$currentMonth, $endOfMonth])
            ->where('status_validasi', 'pending')
            ->count();

        $tindakanSelesai = Tindakan::whereBetween('created_at', [$currentMonth, $endOfMonth])
            ->where('status', 'selesai')
            ->count();

        $tindakanPending = Tindakan::whereBetween('created_at', [$currentMonth, $endOfMonth])
            ->where('status', 'pending')
            ->count();

        return [
            'datasets' => [
                [
                    'label' => 'Transaksi Bulan Ini',
                    'data' => [
                        $pendapatanApproved + $pengeluaranApproved + $tindakanSelesai,
                        $pendapatanPending + $pengeluaranPending + $tindakanPending,
                    ],
                    'backgroundColor' => [
                        'rgb(34, 197, 94)',
                        'rgb(251, 146, 60)',
                    ],
                ],
            ],
            'labels' => ['Disetujui', 'Menunggu Validasi'],
        ];
    }

    protected function getType(): string
    {
        return 'pie';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
            ],
            'maintainAspectRatio' => false,
        ];
    }
}