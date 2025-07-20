<?php

namespace App\Filament\Petugas\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class PremiumDashboardWidgetSimple extends Widget
{
    protected static string $view = 'filament.petugas.widgets.premium-dashboard-widget-simple';
    
    protected static ?int $sort = 1;
    
    protected int | string | array $columnSpan = 'full';
    
    public function getViewData(): array
    {
        return [
            'user_name' => Auth::user()->name ?? 'Petugas',
            'stats' => [
                [
                    'title' => 'Pasien Hari Ini',
                    'value' => '15',
                    'trend' => '+12.5%',
                    'icon' => 'users',
                    'color' => 'blue'
                ],
                [
                    'title' => 'Pendapatan Hari Ini', 
                    'value' => 'Rp 2.750.000',
                    'trend' => '+15.2%',
                    'icon' => 'currency-dollar',
                    'color' => 'green'
                ],
                [
                    'title' => 'Tindakan Hari Ini',
                    'value' => '23',
                    'trend' => '+8.3%',
                    'icon' => 'clipboard-list',
                    'color' => 'amber'
                ],
                [
                    'title' => 'Net Income',
                    'value' => 'Rp 2.170.000',
                    'trend' => '+18.7%',
                    'icon' => 'banknotes',
                    'color' => 'emerald'
                ]
            ]
        ];
    }
}