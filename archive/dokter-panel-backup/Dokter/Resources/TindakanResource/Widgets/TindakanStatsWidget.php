<?php

namespace App\Filament\Dokter\Resources\TindakanResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Dokter;
use App\Models\Tindakan;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class TindakanStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $user = Auth::user();
        $dokter = Dokter::where('user_id', $user->id)->first();
        
        if (!$dokter) {
            return [];
        }

        $thisMonth = Carbon::now()->startOfMonth();
        $thisYear = Carbon::now()->startOfYear();
        
        $monthlyCount = Tindakan::where('dokter_id', $dokter->id)
            ->where('tanggal_tindakan', '>=', $thisMonth)
            ->count();
            
        $yearlyCount = Tindakan::where('dokter_id', $dokter->id)
            ->where('tanggal_tindakan', '>=', $thisYear)
            ->count();
            
        $pendingCount = Tindakan::where('dokter_id', $dokter->id)
            ->where('status_validasi', 'pending')
            ->count();
            
        $approvedThisMonth = Tindakan::where('dokter_id', $dokter->id)
            ->where('tanggal_tindakan', '>=', $thisMonth)
            ->where('status_validasi', 'disetujui')
            ->count();

        return [
            Stat::make('Tindakan Bulan Ini', $monthlyCount)
                ->color('primary')
                ->icon('heroicon-o-clipboard-document-list'),
                
            Stat::make('Tindakan Tahun Ini', $yearlyCount)
                ->color('success')
                ->icon('heroicon-o-chart-bar-square'),
                
            Stat::make('Menunggu Validasi', $pendingCount)
                ->color($pendingCount > 0 ? 'warning' : 'success')
                ->icon('heroicon-o-clock'),
                
            Stat::make('Disetujui Bulan Ini', $approvedThisMonth)
                ->color('success')
                ->icon('heroicon-o-check-circle'),
        ];
    }
}