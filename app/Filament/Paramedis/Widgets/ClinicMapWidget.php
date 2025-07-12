<?php

namespace App\Filament\Paramedis\Widgets;

use Filament\Widgets\Widget;
use Filament\Forms\Components\Grid;
use Filament\Forms\Form;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Dotswan\MapPicker\Fields\Map;

class ClinicMapWidget extends Widget implements HasForms
{
    use InteractsWithForms;
    
    protected static string $view = 'filament.paramedis.widgets.clinic-map';
    
    protected static ?string $pollingInterval = null;
    
    public static function canView(): bool
    {
        return auth()->check() && auth()->user()->role?->name === 'paramedis';
    }
    
    protected int | string | array $columnSpan = 'full';
    
    // Clinic coordinates
    private const CLINIC_LAT = -6.2088;
    private const CLINIC_LNG = 106.8456;
    private const CLINIC_RADIUS = 100; // meters
    
    public $clinic_location = [];
    
    public function mount()
    {
        $this->clinic_location = [
            'lat' => self::CLINIC_LAT,
            'lng' => self::CLINIC_LNG,
        ];
    }
    
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(1)
                    ->schema([
                        Map::make('clinic_location')
                            ->label('📍 Klinik Dokterku Location')
                            ->columnSpanFull()
                            ->defaultLocation(self::CLINIC_LAT, self::CLINIC_LNG)
                            ->extraStyles([
                                'min-height: 50vh',
                                'border-radius: 10px',
                            ])
                            ->showMarker()
                            ->markerColor("#22c55e")
                            ->showFullscreenControl()
                            ->showZoomControl()
                            ->draggable(false)
                            ->tilesUrl("https://tile.openstreetmap.de/{z}/{x}/{y}.png")
                            ->zoom(16)
                            ->detectRetina()
                            ->showMyLocationButton(true),
                    ]),
            ]);
    }
    
    public function getViewData(): array
    {
        return [
            'clinicLat' => self::CLINIC_LAT,
            'clinicLng' => self::CLINIC_LNG,
            'clinicRadius' => self::CLINIC_RADIUS,
        ];
    }
}