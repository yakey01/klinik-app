<?php

namespace App\Filament\Paramedis\Widgets;

use Filament\Widgets\Widget;

class LocationStatusWidget extends Widget
{
    protected static string $view = 'filament.paramedis.widgets.location-compact';
    
    protected static ?string $pollingInterval = null;
    
    public static function canView(): bool
    {
        return auth()->check() && auth()->user()->role?->name === 'paramedis';
    }
    
    protected int | string | array $columnSpan = 'full';
    
    public $showDetails = false;
    
    public function toggleDetails()
    {
        $this->showDetails = !$this->showDetails;
    }
    
    public function getViewData(): array
    {
        // Simulated location status
        $withinRadius = true; // Mock data
        $distance = 45; // meters
        $accuracy = 15; // meters
        
        return [
            'withinRadius' => $withinRadius,
            'distance' => $distance,
            'accuracy' => $accuracy,
            'statusText' => $withinRadius ? 'Dalam Area Klinik' : 'Di Luar Area Klinik',
            'statusIcon' => $withinRadius ? '✅' : '❌',
            'statusColor' => $withinRadius ? 'text-green-600' : 'text-red-600',
            'showDetails' => $this->showDetails,
        ];
    }
}