<?php

namespace App\Filament\Petugas\Widgets;

use App\Models\PendapatanHarian;
use App\Models\PengeluaranHarian;
use App\Models\JumlahPasienHarian;
use App\Models\Tindakan;
use Filament\Widgets\Widget;

class PremiumProgressWidget extends Widget
{
    protected static string $view = 'filament.petugas.widgets.premium-progress-widget';
    
    protected int | string | array $columnSpan = 'full';
    
    protected static ?int $sort = 2;
    
    public function getProgressData(): array
    {
        $today = now()->toDateString();
        
        // Today's completed entries
        $completed = [
            'pendapatan' => PendapatanHarian::whereDate('tanggal_input', $today)->count(),
            'pengeluaran' => PengeluaranHarian::whereDate('tanggal_input', $today)->count(),
            'pasien' => JumlahPasienHarian::whereDate('tanggal', $today)->count(),
            'tindakan' => Tindakan::whereDate('tanggal_tindakan', $today)->count(),
        ];
        
        // Dynamic targets based on day of week and historical data
        $dayOfWeek = now()->dayOfWeek;
        $baseTargets = [
            'pendapatan' => $dayOfWeek >= 1 && $dayOfWeek <= 5 ? 8 : 3, // Higher on weekdays
            'pengeluaran' => $dayOfWeek >= 1 && $dayOfWeek <= 5 ? 5 : 2,
            'pasien' => $dayOfWeek >= 1 && $dayOfWeek <= 5 ? 15 : 8,
            'tindakan' => $dayOfWeek >= 1 && $dayOfWeek <= 5 ? 25 : 10,
        ];
        
        return [
            'pendapatan' => [
                'completed' => $completed['pendapatan'],
                'target' => $baseTargets['pendapatan'],
                'percentage' => min(100, ($completed['pendapatan'] / $baseTargets['pendapatan']) * 100),
                'status' => $this->getProgressStatus($completed['pendapatan'], $baseTargets['pendapatan']),
                'icon' => 'heroicon-o-banknotes',
                'color' => 'emerald',
                'label' => 'Pendapatan Harian'
            ],
            'pengeluaran' => [
                'completed' => $completed['pengeluaran'],
                'target' => $baseTargets['pengeluaran'],
                'percentage' => min(100, ($completed['pengeluaran'] / $baseTargets['pengeluaran']) * 100),
                'status' => $this->getProgressStatus($completed['pengeluaran'], $baseTargets['pengeluaran']),
                'icon' => 'heroicon-o-arrow-trending-down',
                'color' => 'red',
                'label' => 'Pengeluaran Harian'
            ],
            'pasien' => [
                'completed' => $completed['pasien'],
                'target' => $baseTargets['pasien'],
                'percentage' => min(100, ($completed['pasien'] / $baseTargets['pasien']) * 100),
                'status' => $this->getProgressStatus($completed['pasien'], $baseTargets['pasien']),
                'icon' => 'heroicon-o-users',
                'color' => 'blue',
                'label' => 'Data Pasien'
            ],
            'tindakan' => [
                'completed' => $completed['tindakan'],
                'target' => $baseTargets['tindakan'],
                'percentage' => min(100, ($completed['tindakan'] / $baseTargets['tindakan']) * 100),
                'status' => $this->getProgressStatus($completed['tindakan'], $baseTargets['tindakan']),
                'icon' => 'heroicon-o-clipboard-document-list',
                'color' => 'purple',
                'label' => 'Tindakan Medis'
            ]
        ];
    }
    
    private function getProgressStatus($completed, $target): string
    {
        $percentage = ($completed / $target) * 100;
        
        if ($percentage >= 100) return 'excellent';
        if ($percentage >= 80) return 'good';
        if ($percentage >= 50) return 'average';
        return 'needs_attention';
    }
    
    public function getTotalProgress(): array
    {
        $data = $this->getProgressData();
        $totalCompleted = array_sum(array_column($data, 'completed'));
        $totalTarget = array_sum(array_column($data, 'target'));
        $overallPercentage = $totalTarget > 0 ? ($totalCompleted / $totalTarget) * 100 : 0;
        
        return [
            'completed' => $totalCompleted,
            'target' => $totalTarget,
            'percentage' => min(100, $overallPercentage),
            'status' => $this->getProgressStatus($totalCompleted, $totalTarget)
        ];
    }
}