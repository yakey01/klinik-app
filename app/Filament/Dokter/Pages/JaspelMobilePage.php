<?php

namespace App\Filament\Dokter\Pages;

use Filament\Pages\Page;
use App\Models\User;
use App\Models\Jaspel;
use App\Models\Tindakan;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class JaspelMobilePage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    
    protected static ?string $title = 'Jaspel Mobile';
    
    protected static ?string $navigationLabel = 'Jaspel';
    
    protected static ?int $navigationSort = 3;
    
    protected static string $view = 'filament.dokter.pages.jaspel-mobile';
    
    protected static string $routePath = '/jaspel-mobile';
    
    public function mount(): void
    {
        // Get current user data
        $user = Auth::user();
        $this->user = $user;
        
        // Calculate Jaspel statistics
        $this->jaspelBulanIni = $this->getJaspelBulanIni();
        $this->jaspelMingguIni = $this->getJaspelMingguIni();
        $this->menitJagaBulanIni = $this->getMenitJagaBulanIni();
        $this->jaspelPending = $this->getJaspelPending();
        $this->jaspelDisetujui = $this->getJaspelDisetujui();
        $this->recentJaspel = $this->getRecentJaspel();
    }
    
    public $user;
    public $jaspelBulanIni;
    public $jaspelMingguIni;
    public $menitJagaBulanIni;
    public $jaspelPending;
    public $jaspelDisetujui;
    public $recentJaspel;
    
    private function getJaspelBulanIni(): float
    {
        // Total jaspel bulan ini yang sudah disetujui
        return Jaspel::where('user_id', Auth::id())
            ->where('status_validasi', 'disetujui')
            ->whereMonth('tanggal', Carbon::now()->month)
            ->whereYear('tanggal', Carbon::now()->year)
            ->sum('nominal') ?? 0;
    }
    
    private function getJaspelMingguIni(): float
    {
        // Total jaspel minggu ini yang sudah disetujui
        return Jaspel::where('user_id', Auth::id())
            ->where('status_validasi', 'disetujui')
            ->whereBetween('tanggal', [
                Carbon::now()->startOfWeek(),
                Carbon::now()->endOfWeek()
            ])
            ->sum('nominal') ?? 0;
    }
    
    private function getMenitJagaBulanIni(): int
    {
        // Hitung total menit dari jaspel bulan ini
        // Asumsi: setiap jaspel = 30 menit kerja
        $totalJaspel = Jaspel::where('user_id', Auth::id())
            ->where('status_validasi', 'disetujui')
            ->whereMonth('tanggal', Carbon::now()->month)
            ->whereYear('tanggal', Carbon::now()->year)
            ->count();
            
        return $totalJaspel * 30; // 30 menit per jaspel
    }
    
    private function getJaspelPending(): int
    {
        return Jaspel::where('user_id', Auth::id())
            ->where('status_validasi', 'pending')
            ->count();
    }
    
    private function getJaspelDisetujui(): int
    {
        return Jaspel::where('user_id', Auth::id())
            ->where('status_validasi', 'disetujui')
            ->whereMonth('tanggal', Carbon::now()->month)
            ->whereYear('tanggal', Carbon::now()->year)
            ->count();
    }
    
    private function getRecentJaspel(): \Illuminate\Database\Eloquent\Collection
    {
        return Jaspel::where('user_id', Auth::id())
            ->with(['tindakan.jenisTindakan', 'tindakan.pasien'])
            ->orderBy('tanggal', 'desc')
            ->limit(5)
            ->get();
    }
}