<?php

namespace App\Filament\Paramedis\Widgets;

use Filament\Widgets\Widget;

class QuickAccessWidget extends Widget
{
    protected static string $view = 'filament.paramedis.widgets.quick-access';
    
    protected static ?int $sort = 0;
    
    protected int | string | array $columnSpan = 'full';
    
    public function getViewData(): array
    {
        $user = auth()->user();
        
        return [
            'user_name' => $user->name,
            'attendance_count' => \App\Models\Attendance::where('user_id', $user->id)->count(),
            'this_month_count' => \App\Models\Attendance::where('user_id', $user->id)
                ->whereYear('date', now()->year)
                ->whereMonth('date', now()->month)
                ->count(),
        ];
    }
}