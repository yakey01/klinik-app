<?php

namespace App\Filament\Paramedis\Widgets;

use Filament\Widgets\Widget;
use App\Models\Jaspel;
use Carbon\Carbon;

class JaspelHighlightWidget extends Widget
{
    protected static string $view = 'filament.paramedis.widgets.jaspel-compact';
    
    protected static ?string $pollingInterval = null;
    
    public static function canView(): bool
    {
        return auth()->check() && auth()->user()->role?->name === 'paramedis';
    }
    
    protected int | string | array $columnSpan = 'full';
    
    public function getViewData(): array
    {
        $user = auth()->user();
        $today = Carbon::now('Asia/Jakarta')->startOfDay();
        $startOfMonth = Carbon::now('Asia/Jakarta')->startOfMonth();
        $endOfMonth = Carbon::now('Asia/Jakarta')->endOfMonth();
        
        // Jaspel hari ini
        $todayJaspel = Jaspel::where('user_id', $user->id)
            ->whereDate('tanggal', $today)
            ->sum('nominal') ?? 0;
            
        // Jaspel bulan ini
        $monthlyJaspel = Jaspel::where('user_id', $user->id)
            ->whereBetween('tanggal', [$startOfMonth, $endOfMonth])
            ->sum('nominal') ?? 0;
            
        // Dummy fallback untuk demo
        if ($todayJaspel == 0) {
            $todayJaspel = rand(50000, 150000);
        }
        if ($monthlyJaspel == 0) {
            $monthlyJaspel = rand(800000, 2500000);
        }
        
        // Target bulanan (dummy)
        $monthlyTarget = 2000000;
        $progress = min(100, ($monthlyJaspel / $monthlyTarget) * 100);
        
        return [
            'todayJaspel' => $todayJaspel,
            'monthlyJaspel' => $monthlyJaspel,
            'monthlyTarget' => $monthlyTarget,
            'progress' => $progress,
            'remainingDays' => Carbon::now('Asia/Jakarta')->daysInMonth - Carbon::now('Asia/Jakarta')->day,
            'formattedToday' => 'Rp ' . number_format($todayJaspel, 0, ',', '.'),
            'formattedMonthly' => 'Rp ' . number_format($monthlyJaspel, 0, ',', '.'),
            'formattedTarget' => 'Rp ' . number_format($monthlyTarget, 0, ',', '.'),
        ];
    }
}