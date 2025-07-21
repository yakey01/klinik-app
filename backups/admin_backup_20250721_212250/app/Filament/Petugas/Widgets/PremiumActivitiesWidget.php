<?php

namespace App\Filament\Petugas\Widgets;

use App\Models\PendapatanHarian;
use App\Models\PengeluaranHarian;
use App\Models\JumlahPasienHarian;
use App\Models\Tindakan;
use App\Models\User;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;

class PremiumActivitiesWidget extends Widget
{
    protected static string $view = 'filament.petugas.widgets.premium-activities-widget';
    
    protected int | string | array $columnSpan = 'full';
    
    protected static ?int $sort = 3;
    
    public function getRecentActivities(): array
    {
        $activities = collect();
        
        // Recent Pendapatan entries
        $recentPendapatan = PendapatanHarian::with('user')
            ->latest('tanggal_input')
            ->limit(3)
            ->get()
            ->map(function ($item) {
                return [
                    'type' => 'pendapatan',
                    'title' => 'Input Pendapatan Harian',
                    'description' => 'Rp ' . number_format($item->nominal, 0, ',', '.'),
                    'amount' => $item->nominal,
                    'date' => $item->tanggal_input,
                    'time' => $item->created_at,
                    'created_by' => $item->user->name ?? 'Unknown',
                    'avatar' => 'https://ui-avatars.com/api/?name=' . urlencode($item->user->name ?? 'U') . '&background=10b981&color=fff',
                    'icon' => 'heroicon-o-banknotes',
                    'color' => 'emerald'
                ];
            });
            
        // Recent Pengeluaran entries
        $recentPengeluaran = PengeluaranHarian::with('user')
            ->latest('tanggal_input')
            ->limit(3)
            ->get()
            ->map(function ($item) {
                return [
                    'type' => 'pengeluaran',
                    'title' => 'Input Pengeluaran Harian',
                    'description' => 'Rp ' . number_format($item->nominal, 0, ',', '.'),
                    'amount' => $item->nominal,
                    'date' => $item->tanggal_input,
                    'time' => $item->created_at,
                    'created_by' => $item->user->name ?? 'Unknown',
                    'avatar' => 'https://ui-avatars.com/api/?name=' . urlencode($item->user->name ?? 'U') . '&background=ef4444&color=fff',
                    'icon' => 'heroicon-o-arrow-trending-down',
                    'color' => 'red'
                ];
            });
        
        // Recent Tindakan entries
        $recentTindakan = Tindakan::with(['inputBy', 'jenisTindakan'])
            ->latest('tanggal_tindakan')
            ->limit(4)
            ->get()
            ->map(function ($item) {
                return [
                    'type' => 'tindakan',
                    'title' => 'Tindakan Medis',
                    'description' => $item->jenisTindakan->nama ?? 'Tindakan',
                    'amount' => 0,
                    'date' => $item->tanggal_tindakan,
                    'time' => $item->created_at,
                    'created_by' => $item->inputBy->name ?? 'Unknown',
                    'avatar' => 'https://ui-avatars.com/api/?name=' . urlencode($item->inputBy->name ?? 'U') . '&background=3b82f6&color=fff',
                    'icon' => 'heroicon-o-clipboard-document-list',
                    'color' => 'blue'
                ];
            });
        
        return $activities->merge($recentPendapatan)
            ->merge($recentPengeluaran)
            ->merge($recentTindakan)
            ->sortByDesc('time')
            ->take(10)
            ->values()
            ->toArray();
    }
    
    public function getTopPerformers(): array
    {
        // Top staff by data entry
        $topStaff = User::whereHas('roles', function($q) {
                $q->where('name', 'petugas');
            })
            ->withCount([
                'pendapatanHarian as pendapatan_count',
                'pengeluaranHarian as pengeluaran_count',
                'tindakanInput as tindakan_count'
            ])
            ->get()
            ->map(function ($user) {
                $totalEntries = $user->pendapatan_count + $user->pengeluaran_count + $user->tindakan_count;
                return [
                    'name' => $user->name,
                    'total' => $totalEntries,
                    'avatar' => 'https://ui-avatars.com/api/?name=' . urlencode($user->name) . '&background=6366f1&color=fff',
                    'breakdown' => [
                        'pendapatan' => $user->pendapatan_count,
                        'pengeluaran' => $user->pengeluaran_count,
                        'tindakan' => $user->tindakan_count,
                    ]
                ];
            })
            ->sortByDesc('total')
            ->take(5)
            ->values()
            ->toArray();
            
        // Top procedures
        $topProcedures = Tindakan::select('jenis_tindakan_id', DB::raw('COUNT(*) as total_count'))
            ->with('jenisTindakan')
            ->groupBy('jenis_tindakan_id')
            ->orderBy('total_count', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($item) {
                return [
                    'name' => $item->jenisTindakan->nama ?? 'Unknown',
                    'total' => $item->total_count,
                    'percentage' => 0 // Will be calculated in view
                ];
            })
            ->toArray();
        
        // Calculate percentages for procedures
        $totalProcedures = array_sum(array_column($topProcedures, 'total'));
        foreach ($topProcedures as $key => $procedure) {
            $topProcedures[$key]['percentage'] = $totalProcedures > 0 ? 
                round(($procedure['total'] / $totalProcedures) * 100, 1) : 0;
        }
        
        return [
            'staff' => $topStaff,
            'procedures' => $topProcedures,
        ];
    }
    
    public function getMonthlyTrends(): array
    {
        $months = [];
        $pendapatan = [];
        $pengeluaran = [];
        $pasien = [];
        
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $months[] = $date->format('M Y');
            
            $monthlyPendapatan = PendapatanHarian::whereMonth('tanggal_input', $date->month)
                ->whereYear('tanggal_input', $date->year)
                ->sum('nominal');
                
            $monthlyPengeluaran = PengeluaranHarian::whereMonth('tanggal_input', $date->month)
                ->whereYear('tanggal_input', $date->year)
                ->sum('nominal');
                
            $monthlyPasien = JumlahPasienHarian::whereMonth('tanggal', $date->month)
                ->whereYear('tanggal', $date->year)
                ->sum(DB::raw('jumlah_pasien_umum + jumlah_pasien_bpjs'));
            
            $pendapatan[] = $monthlyPendapatan;
            $pengeluaran[] = $monthlyPengeluaran;
            $pasien[] = $monthlyPasien;
        }
        
        return [
            'months' => $months,
            'pendapatan' => $pendapatan,
            'pengeluaran' => $pengeluaran,
            'pasien' => $pasien,
        ];
    }
}