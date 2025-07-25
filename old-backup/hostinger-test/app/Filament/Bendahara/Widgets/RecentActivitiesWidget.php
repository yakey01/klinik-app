<?php

namespace App\Filament\Bendahara\Widgets;

use App\Models\Pendapatan;
use App\Models\Pengeluaran;
use App\Models\Jaspel;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Cache;

class RecentActivitiesWidget extends Widget
{
    protected static string $view = 'filament.bendahara.widgets.recent-activities';
    
    protected static ?int $sort = 3;
    
    protected int | string | array $columnSpan = 'full';

    public function getViewData(): array
    {
        return Cache::remember('bendahara_recent_activities', now()->addMinutes(5), function () {
            return [
                'activities' => $this->getRecentActivities()
            ];
        });
    }
    
    protected function getRecentActivities(): array
    {
        $activities = [];
        
        // Get recent pendapatan
        $pendapatan = Pendapatan::with(['inputBy', 'validasiBy'])
            ->latest('updated_at')
            ->limit(5)
            ->get()
            ->map(function ($item) {
                return [
                    'type' => 'pendapatan',
                    'description' => $item->nama_pendapatan,
                    'amount' => $item->nominal,
                    'status' => $item->status_validasi,
                    'updated_at' => $item->updated_at,
                    'validated_by' => $item->validasiBy->name ?? 'Menunggu',
                ];
            });
            
        // Get recent pengeluaran
        $pengeluaran = Pengeluaran::with(['inputBy', 'validasiBy'])
            ->latest('updated_at')
            ->limit(5)
            ->get()
            ->map(function ($item) {
                return [
                    'type' => 'pengeluaran',
                    'description' => $item->nama_pengeluaran,
                    'amount' => $item->nominal,
                    'status' => $item->status_validasi ?? 'approved',
                    'updated_at' => $item->updated_at,
                    'validated_by' => $item->validasiBy->name ?? 'Auto-approved',
                ];
            });
        
        return $pendapatan->merge($pengeluaran)
            ->sortByDesc('updated_at')
            ->take(8)
            ->values()
            ->toArray();
    }
}