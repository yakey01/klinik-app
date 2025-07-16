<?php

namespace App\Filament\Bendahara\Pages;

use App\Models\Pendapatan;
use App\Models\Pengeluaran;
use App\Models\Jaspel;
use App\Models\User;
use App\Models\Tindakan;
use Filament\Pages\Page;
use Filament\Support\Colors\Color;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\View\View;
use Filament\Actions\Action;
use Filament\Support\Enums\ActionSize;

class BendaharaDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';
    
    protected static string $view = 'filament.bendahara.pages.bendahara-dashboard';
    
    protected static ?string $title = 'Dashboard Bendahara';
    
    protected static ?string $navigationLabel = 'Dashboard';
    
    protected static ?int $navigationSort = 1;
    
    protected static ?string $navigationGroup = '📊 Dashboard';
    
    public function mount(): void
    {
        // Initialize dashboard data
    }
    
    public function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label('Refresh Data')
                ->icon('heroicon-o-arrow-path')
                ->color('primary')
                ->size(ActionSize::Small)
                ->action(fn () => redirect()->to(request()->url())),
                
            Action::make('export')
                ->label('Export Report')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->size(ActionSize::Small)
                ->action(fn () => $this->exportReport()),
        ];
    }
    
    public function getFinancialSummary(): array
    {
        $currentMonth = now();
        $lastMonth = now()->subMonth();
        
        // Current month data
        $currentPendapatan = Pendapatan::whereMonth('tanggal', $currentMonth->month)
            ->whereYear('tanggal', $currentMonth->year)
            ->where('status_validasi', 'disetujui')
            ->sum('nominal');
            
        $currentPengeluaran = Pengeluaran::whereMonth('tanggal', $currentMonth->month)
            ->whereYear('tanggal', $currentMonth->year)
            ->sum('nominal');
            
        $currentJaspel = Jaspel::whereMonth('tanggal', $currentMonth->month)
            ->whereYear('tanggal', $currentMonth->year)
            ->sum('nominal');
        
        // Last month data for comparison
        $lastPendapatan = Pendapatan::whereMonth('tanggal', $lastMonth->month)
            ->whereYear('tanggal', $lastMonth->year)
            ->where('status_validasi', 'disetujui')
            ->sum('nominal');
            
        $lastPengeluaran = Pengeluaran::whereMonth('tanggal', $lastMonth->month)
            ->whereYear('tanggal', $lastMonth->year)
            ->sum('nominal');
            
        $lastJaspel = Jaspel::whereMonth('tanggal', $lastMonth->month)
            ->whereYear('tanggal', $lastMonth->year)
            ->sum('nominal');
        
        return [
            'current' => [
                'pendapatan' => $currentPendapatan,
                'pengeluaran' => $currentPengeluaran,
                'jaspel' => $currentJaspel,
                'net_profit' => $currentPendapatan - $currentPengeluaran - $currentJaspel,
            ],
            'last_month' => [
                'pendapatan' => $lastPendapatan,
                'pengeluaran' => $lastPengeluaran,
                'jaspel' => $lastJaspel,
                'net_profit' => $lastPendapatan - $lastPengeluaran - $lastJaspel,
            ],
            'changes' => [
                'pendapatan' => $this->calculatePercentageChange($currentPendapatan, $lastPendapatan),
                'pengeluaran' => $this->calculatePercentageChange($currentPengeluaran, $lastPengeluaran),
                'jaspel' => $this->calculatePercentageChange($currentJaspel, $lastJaspel),
                'net_profit' => $this->calculatePercentageChange(
                    $currentPendapatan - $currentPengeluaran - $currentJaspel,
                    $lastPendapatan - $lastPengeluaran - $lastJaspel
                ),
            ],
        ];
    }
    
    public function getValidationStats(): array
    {
        $pending = [
            'pendapatan' => Pendapatan::where('status_validasi', 'pending')->count(),
            'pengeluaran' => Pengeluaran::where('status_validasi', 'pending')->count(),
            'jaspel' => Jaspel::where('status_validasi', 'pending')->count(),
        ];
        
        $approved = [
            'pendapatan' => Pendapatan::where('status_validasi', 'disetujui')->count(),
            'pengeluaran' => Pengeluaran::where('status_validasi', 'disetujui')->count(),
            'jaspel' => Jaspel::where('status_validasi', 'disetujui')->count(),
        ];
        
        $rejected = [
            'pendapatan' => Pendapatan::where('status_validasi', 'ditolak')->count(),
            'pengeluaran' => Pengeluaran::where('status_validasi', 'ditolak')->count(),
            'jaspel' => Jaspel::where('status_validasi', 'ditolak')->count(),
        ];
        
        return [
            'pending' => $pending,
            'approved' => $approved,
            'rejected' => $rejected,
            'total_pending' => array_sum($pending),
            'total_approved' => array_sum($approved),
            'total_rejected' => array_sum($rejected),
        ];
    }
    
    public function getRecentTransactions(): array
    {
        $recentPendapatan = Pendapatan::with(['inputBy', 'validasiBy'])
            ->latest()
            ->limit(5)
            ->get()
            ->map(function ($item) {
                return [
                    'type' => 'pendapatan',
                    'code' => $item->kode_pendapatan,
                    'description' => $item->nama_pendapatan,
                    'amount' => $item->nominal,
                    'status' => $item->status_validasi,
                    'date' => $item->tanggal,
                    'created_by' => $item->inputBy->name ?? 'Unknown',
                ];
            });
            
        $recentPengeluaran = Pengeluaran::with(['inputBy', 'validasiBy'])
            ->latest()
            ->limit(5)
            ->get()
            ->map(function ($item) {
                return [
                    'type' => 'pengeluaran',
                    'code' => $item->kode_pengeluaran,
                    'description' => $item->nama_pengeluaran,
                    'amount' => $item->nominal,
                    'status' => $item->status_validasi ?? 'approved',
                    'date' => $item->tanggal,
                    'created_by' => $item->inputBy->name ?? 'Unknown',
                ];
            });
            
        $recentJaspel = Jaspel::with(['inputBy', 'validasiBy', 'tindakan.jenisTindakan'])
            ->latest()
            ->limit(5)
            ->get()
            ->map(function ($item) {
                return [
                    'type' => 'jaspel',
                    'code' => $item->kode_jaspel ?? 'JSP-' . str_pad($item->id, 4, '0', STR_PAD_LEFT),
                    'description' => $item->tindakan->jenisTindakan->nama ?? 'Jaspel',
                    'amount' => $item->nominal,
                    'status' => $item->status_validasi ?? 'approved',
                    'date' => $item->tanggal,
                    'created_by' => $item->inputBy->name ?? 'Unknown',
                ];
            });
        
        return $recentPendapatan->merge($recentPengeluaran)
            ->merge($recentJaspel)
            ->sortByDesc('date')
            ->take(10)
            ->values()
            ->toArray();
    }
    
    public function getMonthlyTrends(): array
    {
        $months = [];
        $pendapatan = [];
        $pengeluaran = [];
        $jaspel = [];
        
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $months[] = $date->format('M Y');
            
            $monthlyPendapatan = Pendapatan::whereMonth('tanggal', $date->month)
                ->whereYear('tanggal', $date->year)
                ->where('status_validasi', 'disetujui')
                ->sum('nominal');
                
            $monthlyPengeluaran = Pengeluaran::whereMonth('tanggal', $date->month)
                ->whereYear('tanggal', $date->year)
                ->sum('nominal');
                
            $monthlyJaspel = Jaspel::whereMonth('tanggal', $date->month)
                ->whereYear('tanggal', $date->year)
                ->sum('nominal');
            
            $pendapatan[] = $monthlyPendapatan;
            $pengeluaran[] = $monthlyPengeluaran;
            $jaspel[] = $monthlyJaspel;
        }
        
        return [
            'months' => $months,
            'pendapatan' => $pendapatan,
            'pengeluaran' => $pengeluaran,
            'jaspel' => $jaspel,
        ];
    }
    
    public function getTopPerformers(): array
    {
        $topDoctors = Jaspel::select('dokter_id', DB::raw('SUM(nominal) as total_jaspel'))
            ->with('dokter')
            ->groupBy('dokter_id')
            ->orderBy('total_jaspel', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($item) {
                return [
                    'name' => $item->dokter->name ?? 'Unknown',
                    'total' => $item->total_jaspel,
                    'type' => 'doctor',
                ];
            });
            
        $topProcedures = Tindakan::select('tindakan.id', 'jenis_tindakan.nama', DB::raw('COUNT(jaspel.id) as total_count'))
            ->leftJoin('jaspel', 'tindakan.id', '=', 'jaspel.tindakan_id')
            ->leftJoin('jenis_tindakan', 'tindakan.jenis_tindakan_id', '=', 'jenis_tindakan.id')
            ->groupBy('tindakan.id', 'jenis_tindakan.nama')
            ->orderBy('total_count', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($item) {
                return [
                    'name' => $item->nama,
                    'total' => $item->total_count,
                    'type' => 'procedure',
                ];
            });
        
        return [
            'doctors' => $topDoctors,
            'procedures' => $topProcedures,
        ];
    }
    
    private function calculatePercentageChange($current, $previous): float
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }
        
        return round((($current - $previous) / $previous) * 100, 2);
    }
    
    private function exportReport(): void
    {
        // TODO: Implement export functionality
        $this->notify('success', 'Export functionality will be implemented soon');
    }
    
    protected function notify(string $type, string $message): void
    {
        session()->flash('filament.notification', [
            'type' => $type,
            'message' => $message,
        ]);
    }
}