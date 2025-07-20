<?php

namespace App\Filament\Paramedis\Widgets;

use Filament\Widgets\Widget;
use Filament\Forms\Components\Grid;
use Filament\Forms\Form;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Dotswan\MapPicker\Fields\Map;
use App\Models\WorkLocation;

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
    
    // Work location properties
    public $currentWorkLocation = null;
    public $clinic_location = [];
    
    public function mount()
    {
        $this->loadWorkLocation();
        $clinic = $this->getClinicCoordinates();
        $this->clinic_location = [
            'lat' => $clinic['lat'],
            'lng' => $clinic['lng'],
        ];
    }
    
    /**
     * Load primary work location
     */
    private function loadWorkLocation()
    {
        $this->currentWorkLocation = WorkLocation::active()
            ->where('location_type', 'main_office')
            ->first() ?? WorkLocation::active()->first();
    }
    
    /**
     * Get clinic coordinates from WorkLocation
     */
    private function getClinicCoordinates()
    {
        if ($this->currentWorkLocation) {
            return [
                'lat' => (float) $this->currentWorkLocation->latitude,
                'lng' => (float) $this->currentWorkLocation->longitude,
                'radius' => $this->currentWorkLocation->radius_meters,
                'name' => $this->currentWorkLocation->name,
            ];
        }
        
        return ['lat' => -6.2088, 'lng' => 106.8456, 'radius' => 100, 'name' => 'Klinik Dokterku'];
    }
    
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(1)
                    ->schema([
                        Map::make('clinic_location')
                            ->label('📍 ' . $this->getClinicCoordinates()['name'])
                            ->columnSpanFull()
                            ->defaultLocation($this->getClinicCoordinates()['lat'], $this->getClinicCoordinates()['lng'])
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
        $clinic = $this->getClinicCoordinates();
        return [
            'clinic' => $clinic,
            'clinicLat' => $clinic['lat'],
            'clinicLng' => $clinic['lng'],
            'clinicRadius' => $clinic['radius'],
            'clinicName' => $clinic['name'],
        ];
    }
}