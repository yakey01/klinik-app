<?php

namespace App\Filament\Petugas\Widgets;

use App\Models\PendapatanHarian;
use App\Models\PengeluaranHarian;
use App\Models\JumlahPasienHarian;
use App\Models\Tindakan;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;

class PremiumStatsWidget extends Widget
{
    protected static string $view = 'filament.petugas.widgets.premium-stats-widget';
    
    protected int | string | array $columnSpan = 'full';
    
    protected static ?int $sort = 1;
    
    public function getStatsData(): array
    {
        $currentMonth = now();
        $lastMonth = now()->subMonth();
        
        // Current month data
        $currentPendapatan = PendapatanHarian::whereMonth('tanggal_input', $currentMonth->month)
            ->whereYear('tanggal_input', $currentMonth->year)
            ->sum('nominal');
            
        $currentPengeluaran = PengeluaranHarian::whereMonth('tanggal_input', $currentMonth->month)
            ->whereYear('tanggal_input', $currentMonth->year)
            ->sum('nominal');
            
        $currentPasien = JumlahPasienHarian::whereMonth('tanggal', $currentMonth->month)
            ->whereYear('tanggal', $currentMonth->year)
            ->sum(DB::raw('jumlah_pasien_umum + jumlah_pasien_bpjs'));
            
        $currentTindakan = Tindakan::whereMonth('tanggal_tindakan', $currentMonth->month)
            ->whereYear('tanggal_tindakan', $currentMonth->year)
            ->count();
        
        // Last month data for comparison
        $lastPendapatan = PendapatanHarian::whereMonth('tanggal_input', $lastMonth->month)
            ->whereYear('tanggal_input', $lastMonth->year)
            ->sum('nominal');
            
        $lastPengeluaran = PengeluaranHarian::whereMonth('tanggal_input', $lastMonth->month)
            ->whereYear('tanggal_input', $lastMonth->year)
            ->sum('nominal');
            
        $lastPasien = JumlahPasienHarian::whereMonth('tanggal', $lastMonth->month)
            ->whereYear('tanggal', $lastMonth->year)
            ->sum(DB::raw('jumlah_pasien_umum + jumlah_pasien_bpjs'));
            
        $lastTindakan = Tindakan::whereMonth('tanggal_tindakan', $lastMonth->month)
            ->whereYear('tanggal_tindakan', $lastMonth->year)
            ->count();
        
        return [
            'pendapatan' => [
                'current' => $currentPendapatan,
                'previous' => $lastPendapatan,
                'change' => $this->calculatePercentageChange($currentPendapatan, $lastPendapatan),
                'icon' => 'heroicon-o-banknotes',
                'color' => 'emerald'
            ],
            'pengeluaran' => [
                'current' => $currentPengeluaran,
                'previous' => $lastPengeluaran,
                'change' => $this->calculatePercentageChange($currentPengeluaran, $lastPengeluaran),
                'icon' => 'heroicon-o-arrow-trending-down',
                'color' => 'red'
            ],
            'pasien' => [
                'current' => $currentPasien,
                'previous' => $lastPasien,
                'change' => $this->calculatePercentageChange($currentPasien, $lastPasien),
                'icon' => 'heroicon-o-users',
                'color' => 'blue'
            ],
            'net_income' => [
                'current' => $currentPendapatan - $currentPengeluaran,
                'previous' => $lastPendapatan - $lastPengeluaran,
                'change' => $this->calculatePercentageChange(
                    $currentPendapatan - $currentPengeluaran,
                    $lastPendapatan - $lastPengeluaran
                ),
                'icon' => 'heroicon-o-chart-bar',
                'color' => ($currentPendapatan - $currentPengeluaran) >= 0 ? 'emerald' : 'red'
            ]
        ];
    }
    
    private function calculatePercentageChange($current, $previous): float
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }
        
        return round((($current - $previous) / $previous) * 100, 2);
    }
}