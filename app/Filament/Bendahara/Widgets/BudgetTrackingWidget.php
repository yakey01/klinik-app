<?php

namespace App\Filament\Bendahara\Widgets;

use App\Models\Pendapatan;
use App\Models\Pengeluaran;
use App\Models\BudgetPlan;
use Filament\Widgets\Widget;
use Filament\Support\Colors\Color;
use Illuminate\Support\Facades\DB;

class BudgetTrackingWidget extends Widget
{
    protected static string $view = 'filament.bendahara.widgets.budget-tracking-widget';
    
    protected int | string | array $columnSpan = 'full';
    
    public function getBudgetData(): array
    {
        $currentMonth = now();
        $budget = $this->getCurrentBudget();
        
        // Get actual values
        $actualPendapatan = Pendapatan::whereMonth('tanggal', $currentMonth->month)
            ->whereYear('tanggal', $currentMonth->year)
            ->where('status_validasi', 'disetujui')
            ->sum('nominal');
            
        $actualPengeluaran = Pengeluaran::whereMonth('tanggal', $currentMonth->month)
            ->whereYear('tanggal', $currentMonth->year)
            ->sum('nominal');
        
        // Calculate percentages
        $pendapatanPercentage = $budget['target_pendapatan'] > 0 
            ? min(($actualPendapatan / $budget['target_pendapatan']) * 100, 100) 
            : 0;
            
        $pengeluaranPercentage = $budget['target_pengeluaran'] > 0 
            ? min(($actualPengeluaran / $budget['target_pengeluaran']) * 100, 100) 
            : 0;
        
        return [
            'budget' => $budget,
            'actual' => [
                'pendapatan' => $actualPendapatan,
                'pengeluaran' => $actualPengeluaran,
                'net' => $actualPendapatan - $actualPengeluaran,
            ],
            'percentages' => [
                'pendapatan' => $pendapatanPercentage,
                'pengeluaran' => $pengeluaranPercentage,
            ],
            'status' => $this->getBudgetStatus($pendapatanPercentage, $pengeluaranPercentage),
            'alerts' => $this->getBudgetAlerts($pendapatanPercentage, $pengeluaranPercentage),
        ];
    }
    
    public function getQuarterlyComparison(): array
    {
        $quarters = [];
        $currentYear = now()->year;
        
        for ($q = 1; $q <= 4; $q++) {
            $startMonth = ($q - 1) * 3 + 1;
            $endMonth = $q * 3;
            
            $pendapatan = Pendapatan::whereYear('tanggal', $currentYear)
                ->whereMonth('tanggal', '>=', $startMonth)
                ->whereMonth('tanggal', '<=', $endMonth)
                ->where('status_validasi', 'disetujui')
                ->sum('nominal');
                
            $pengeluaran = Pengeluaran::whereYear('tanggal', $currentYear)
                ->whereMonth('tanggal', '>=', $startMonth)
                ->whereMonth('tanggal', '<=', $endMonth)
                ->sum('nominal');
            
            $quarters[] = [
                'quarter' => "Q{$q}",
                'pendapatan' => $pendapatan,
                'pengeluaran' => $pengeluaran,
                'net' => $pendapatan - $pengeluaran,
            ];
        }
        
        return $quarters;
    }
    
    public function getCategoryBreakdown(): array
    {
        $currentMonth = now();
        
        $pengeluaranByCategory = Pengeluaran::whereMonth('tanggal', $currentMonth->month)
            ->whereYear('tanggal', $currentMonth->year)
            ->select('kategori', DB::raw('SUM(nominal) as total'))
            ->groupBy('kategori')
            ->orderBy('total', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'category' => $this->getCategoryLabel($item->kategori),
                    'amount' => $item->total,
                    'color' => $this->getCategoryColor($item->kategori),
                ];
            });
            
        $pendapatanBySource = Pendapatan::whereMonth('tanggal', $currentMonth->month)
            ->whereYear('tanggal', $currentMonth->year)
            ->where('status_validasi', 'disetujui')
            ->select('sumber_pendapatan', DB::raw('SUM(nominal) as total'))
            ->groupBy('sumber_pendapatan')
            ->orderBy('total', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'source' => $item->sumber_pendapatan,
                    'amount' => $item->total,
                    'color' => $this->getSourceColor($item->sumber_pendapatan),
                ];
            });
        
        return [
            'pengeluaran' => $pengeluaranByCategory,
            'pendapatan' => $pendapatanBySource,
        ];
    }
    
    private function getCurrentBudget(): array
    {
        $currentBudget = BudgetPlan::getCurrentBudget();
        
        if ($currentBudget) {
            return [
                'target_pendapatan' => $currentBudget->target_pendapatan,
                'target_pengeluaran' => $currentBudget->target_pengeluaran,
                'target_net' => $currentBudget->target_net_profit,
            ];
        }
        
        // Fallback to default budget if no budget plan exists
        return [
            'target_pendapatan' => 50000000, // 50 juta
            'target_pengeluaran' => 35000000, // 35 juta
            'target_net' => 15000000, // 15 juta
        ];
    }
    
    private function getBudgetStatus(float $pendapatanPercentage, float $pengeluaranPercentage): array
    {
        $pendapatanStatus = match(true) {
            $pendapatanPercentage >= 90 => ['label' => 'Excellent', 'color' => 'success'],
            $pendapatanPercentage >= 70 => ['label' => 'Good', 'color' => 'success'],
            $pendapatanPercentage >= 50 => ['label' => 'Average', 'color' => 'warning'],
            default => ['label' => 'Poor', 'color' => 'danger'],
        };
        
        $pengeluaranStatus = match(true) {
            $pengeluaranPercentage >= 90 => ['label' => 'Critical', 'color' => 'danger'],
            $pengeluaranPercentage >= 70 => ['label' => 'High', 'color' => 'warning'],
            $pengeluaranPercentage >= 50 => ['label' => 'Moderate', 'color' => 'warning'],
            default => ['label' => 'Low', 'color' => 'success'],
        };
        
        return [
            'pendapatan' => $pendapatanStatus,
            'pengeluaran' => $pengeluaranStatus,
        ];
    }
    
    private function getBudgetAlerts(float $pendapatanPercentage, float $pengeluaranPercentage): array
    {
        $alerts = [];
        
        if ($pendapatanPercentage < 50) {
            $alerts[] = [
                'type' => 'danger',
                'message' => 'Pendapatan bulan ini masih di bawah 50% target',
                'action' => 'Perlu strategi peningkatan pendapatan',
            ];
        }
        
        if ($pengeluaranPercentage > 85) {
            $alerts[] = [
                'type' => 'warning',
                'message' => 'Pengeluaran sudah mencapai 85% dari budget',
                'action' => 'Perlu kontrol pengeluaran yang lebih ketat',
            ];
        }
        
        if ($pendapatanPercentage > 90) {
            $alerts[] = [
                'type' => 'success',
                'message' => 'Pendapatan sudah mencapai 90% target',
                'action' => 'Performa sangat baik, pertahankan!',
            ];
        }
        
        return $alerts;
    }
    
    private function getCategoryLabel(string $category): string
    {
        return match($category) {
            'konsumsi' => 'Konsumsi',
            'alat_bahan' => 'Alat & Bahan',
            'akomodasi' => 'Akomodasi',
            'medis' => 'Obat & Alkes',
            'honor' => 'Honor & Fee',
            'promosi' => 'Promosi',
            'operasional' => 'Operasional',
            'maintenance' => 'Maintenance',
            'administrasi' => 'Administrasi',
            default => ucfirst($category),
        };
    }
    
    private function getCategoryColor(string $category): string
    {
        return match($category) {
            'konsumsi' => 'success',
            'alat_bahan' => 'warning',
            'akomodasi' => 'info',
            'medis' => 'danger',
            'honor' => 'primary',
            'promosi' => 'secondary',
            'operasional' => 'success',
            'maintenance' => 'warning',
            'administrasi' => 'info',
            default => 'gray',
        };
    }
    
    private function getSourceColor(string $source): string
    {
        return match($source) {
            'Umum' => 'primary',
            'Gigi' => 'success',
            default => 'gray',
        };
    }
}