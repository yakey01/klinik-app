<?php

namespace App\Filament\Paramedis\Widgets;

use Filament\Widgets\Widget;
use Filament\Notifications\Notification;
use DiogoGPinto\GeolocateMe\Data\Coordinates;
use DiogoGPinto\GeolocateMe\Concerns\HasGeolocation;
use App\Models\WorkLocation;
use Livewire\Attributes\On;

class LocationDetectionWidget extends Widget
{
    use HasGeolocation;
    
    protected static string $view = 'filament.paramedis.widgets.location-detection-simple';
    
    protected static ?string $pollingInterval = null;
    
    public static function canView(): bool
    {
        return auth()->check() && auth()->user()->role?->name === 'paramedis';
    }
    
    protected int | string | array $columnSpan = 'full';
    
    public $latitude = null;
    public $longitude = null;
    public $accuracy = null;
    public $locationDetected = false;
    public $withinRadius = false;
    public $distanceToClinic = null;
    public $browserInfo = null;
    public $deviceInfo = null;
    public $ipAddress = null;
    
    // Work location properties  
    public $currentWorkLocation = null;
    
    public function mount()
    {
        $this->detectDeviceInfo();
        $this->loadWorkLocation();
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
            ];
        }
        
        return ['lat' => -6.2088, 'lng' => 106.8456, 'radius' => 100];
    }
    
    public function detectLocation()
    {
        // This will trigger the geolocation through the JavaScript
        $this->dispatch('request-location');
    }
    
    #[On('location-received')]
    public function handleLocationReceived($latitude, $longitude, $accuracy = null)
    {
        $this->handleLocationUpdate($latitude, $longitude, $accuracy);
    }
    
    #[On('location-error')]
    public function handleLocationError($error)
    {
        Notification::make()
            ->title('❌ Location Error')
            ->body($error)
            ->danger()
            ->send();
    }
    
    public function handleLocationUpdate($latitude, $longitude, $accuracy = null)
    {
        $this->latitude = $latitude;
        $this->longitude = $longitude;
        $this->accuracy = $accuracy;
        $this->locationDetected = true;
        
        // Calculate distance to clinic using WorkLocation
        $clinic = $this->getClinicCoordinates();
        $this->distanceToClinic = $this->calculateDistance(
            $latitude, 
            $longitude, 
            $clinic['lat'], 
            $clinic['lng']
        );
        
        $this->withinRadius = $this->distanceToClinic <= $clinic['radius'];
        
        // Send notification
        Notification::make()
            ->title('📍 Lokasi Terdeteksi')
            ->body("Jarak ke klinik: " . round($this->distanceToClinic) . " meter" . 
                   ($this->withinRadius ? " ✅ Dalam radius" : " ❌ Di luar radius"))
            ->success($this->withinRadius)
            ->warning(!$this->withinRadius)
            ->send();
    }
    
    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371000; // Earth radius in meters
        
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        
        $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2) * sin($dLon/2);
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        
        return $earthRadius * $c;
    }
    
    private function detectDeviceInfo()
    {
        // Get device info from request
        $userAgent = request()->header('User-Agent', 'Unknown');
        
        // Simple browser detection
        $this->browserInfo = $this->getBrowserFromUserAgent($userAgent);
        $this->deviceInfo = $this->getDeviceFromUserAgent($userAgent);
        $this->ipAddress = request()->ip();
    }
    
    private function getBrowserFromUserAgent($userAgent)
    {
        if (strpos($userAgent, 'Chrome') !== false) return 'Chrome';
        if (strpos($userAgent, 'Firefox') !== false) return 'Firefox';
        if (strpos($userAgent, 'Safari') !== false) return 'Safari';
        if (strpos($userAgent, 'Edge') !== false) return 'Edge';
        return 'Unknown';
    }
    
    private function getDeviceFromUserAgent($userAgent)
    {
        if (preg_match('/Android|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i', $userAgent)) {
            return 'Mobile';
        }
        if (preg_match('/iPad/i', $userAgent)) {
            return 'Tablet';
        }
        return 'Desktop';
    }
    
    public function getViewData(): array
    {
        return [
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'accuracy' => $this->accuracy,
            'locationDetected' => $this->locationDetected,
            'withinRadius' => $this->withinRadius,
            'distanceToClinic' => $this->distanceToClinic,
            'browserInfo' => $this->browserInfo ?? 'Unknown',
            'deviceInfo' => $this->deviceInfo ?? 'Unknown',
            'ipAddress' => $this->ipAddress ?? 'Unknown',
            'clinic' => $this->getClinicCoordinates(),
            'clinicLat' => $this->getClinicCoordinates()['lat'],
            'clinicLng' => $this->getClinicCoordinates()['lng'],
            'clinicRadius' => $this->getClinicCoordinates()['radius'],
        ];
    }
}