<?php

namespace App\Filament\Paramedis\Pages;

use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use App\Models\Attendance;
use Carbon\Carbon;
use DiogoGPinto\GeolocateMe\Concerns\HasGeolocation;
use Dotswan\MapPicker\Fields\Map;
use Livewire\Attributes\On;

class PresensiPage extends Page
{
    use HasGeolocation;
    
    protected static ?string $navigationIcon = 'heroicon-o-clock';
    
    protected static string $view = 'filament.paramedis.pages.presensi-page';
    
    protected static ?string $navigationLabel = 'Presensi';
    
    protected static ?string $title = 'Presensi Masuk & Pulang';
    
    public static function canAccess(): bool
    {
        return auth()->check() && auth()->user()->role?->name === 'paramedis';
    }

    // Location detection properties
    public $latitude = null;
    public $longitude = null;
    public $accuracy = null;
    public $locationDetected = false;
    public $withinRadius = false;
    public $distanceToClinic = null;
    public $browserInfo = null;
    public $deviceInfo = null;
    public $ipAddress = null;
    
    // Clinic coordinates - same as LocationDetectionWidget
    private const CLINIC_LAT = -6.2088;
    private const CLINIC_LNG = 106.8456;
    private const CLINIC_RADIUS = 100; // meters
    
    public function mount()
    {
        $this->detectDeviceInfo();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('autoDetectLocation')
                ->label('🎯 Auto Detect GPS')
                ->icon('heroicon-o-map-pin')
                ->color('success')
                ->action(function () {
                    $this->dispatch('auto-locate-start');
                })
                ->tooltip('Aktifkan deteksi lokasi otomatis dengan GPS'),
            Action::make('manualLocation')
                ->label('📍 Manual')
                ->icon('heroicon-o-cursor-arrow-rays')
                ->color('primary')
                ->action(function () {
                    $this->dispatch('request-location-manual');
                })
                ->tooltip('Deteksi lokasi manual'),
            Action::make('refreshStatus')
                ->label('🔄 Reset')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->action(function () {
                    $this->resetLocation();
                    $this->dispatch('reset-location-control');
                    Notification::make()
                        ->title('✅ Lokasi direset')
                        ->body('Silakan deteksi ulang dengan tombol Auto Detect GPS')
                        ->success()
                        ->send();
                }),
        ];
    }
    
    #[On('location-received')]
    public function handleLocationReceived($latitude, $longitude, $accuracy = null)
    {
        $this->latitude = $latitude;
        $this->longitude = $longitude;
        $this->accuracy = $accuracy;
        $this->locationDetected = true;
        
        // Validate GPS accuracy first
        if ($accuracy && !$this->validateLocationAccuracy($accuracy)) {
            // Location detected but accuracy is poor, still allow but warn
        }
        
        // Calculate distance to clinic
        $this->distanceToClinic = $this->calculateDistance(
            $latitude, 
            $longitude, 
            self::CLINIC_LAT, 
            self::CLINIC_LNG
        );
        
        $this->withinRadius = $this->distanceToClinic <= self::CLINIC_RADIUS;
        
        // Enhanced notification with accuracy info
        $accuracyText = $accuracy ? " (±{$accuracy}m)" : "";
        $notification = Notification::make()
            ->title('📍 Lokasi Terdeteksi' . $accuracyText)
            ->body("Jarak ke klinik: " . round($this->distanceToClinic) . " meter" . 
                   ($this->withinRadius ? " ✅ Dalam radius klinik" : " ❌ Di luar radius klinik"));
        
        if ($this->withinRadius) {
            $notification->success()->send();
        } else {
            $notification->warning()
                ->body("⚠️ Anda berada " . round($this->distanceToClinic - self::CLINIC_RADIUS) . 
                       "m di luar radius. Mendekatlah ke klinik untuk presensi.")
                ->persistent()
                ->send();
        }
        
        // Dispatch map update
        $this->dispatch('update-map-location', [
            'lat' => $latitude,
            'lng' => $longitude,
            'accuracy' => $accuracy,
            'withinRadius' => $this->withinRadius,
            'distance' => $this->distanceToClinic
        ]);
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

    public function checkin()
    {
        $user = auth()->user();
        $today = Carbon::now('Asia/Jakarta')->startOfDay();
        
        // Check if location is detected and within radius
        if (!$this->locationDetected) {
            Notification::make()
                ->title('❌ Lokasi Belum Terdeteksi')
                ->body('Silakan deteksi lokasi Anda terlebih dahulu')
                ->danger()
                ->send();
            return;
        }
        
        if (!$this->withinRadius) {
            Notification::make()
                ->title('❌ Di Luar Radius Klinik')
                ->body('Anda harus berada dalam radius ' . self::CLINIC_RADIUS . ' meter dari klinik')
                ->danger()
                ->send();
            return;
        }
        
        $existing = Attendance::where('user_id', $user->id)
            ->whereDate('date', $today)
            ->first();
            
        if (!$existing) {
            Attendance::create([
                'user_id' => $user->id,
                'date' => $today,
                'time_in' => Carbon::now('Asia/Jakarta'),
                'status' => 'present',
                'latlon_in' => $this->latitude . ',' . $this->longitude,
                'device_info' => json_encode([
                    'browser' => $this->browserInfo,
                    'device' => $this->deviceInfo,
                    'ip' => $this->ipAddress,
                    'accuracy' => $this->accuracy,
                ]),
            ]);
            
            Notification::make()
                ->title('✅ Check In Berhasil')
                ->body('Selamat bekerja! Jam masuk: ' . Carbon::now('Asia/Jakarta')->format('H:i') . 
                      ' | Lokasi: ' . round($this->distanceToClinic) . 'm dari klinik')
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('⚠️ Sudah Check In')
                ->body('Anda sudah melakukan check in hari ini')
                ->warning()
                ->send();
        }
    }
    
    public function checkout()
    {
        $user = auth()->user();
        $today = Carbon::now('Asia/Jakarta')->startOfDay();
        
        // Check if location is detected and within radius
        if (!$this->locationDetected) {
            Notification::make()
                ->title('❌ Lokasi Belum Terdeteksi')
                ->body('Silakan deteksi lokasi Anda terlebih dahulu')
                ->danger()
                ->send();
            return;
        }
        
        if (!$this->withinRadius) {
            Notification::make()
                ->title('❌ Di Luar Radius Klinik')
                ->body('Anda harus berada dalam radius ' . self::CLINIC_RADIUS . ' meter dari klinik')
                ->danger()
                ->send();
            return;
        }
        
        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('date', $today)
            ->first();
            
        if ($attendance && !$attendance->time_out) {
            $attendance->update([
                'time_out' => Carbon::now('Asia/Jakarta'),
                'latlon_out' => $this->latitude . ',' . $this->longitude,
            ]);
            
            Notification::make()
                ->title('🏁 Check Out Berhasil')
                ->body('Terima kasih! Jam pulang: ' . Carbon::now('Asia/Jakarta')->format('H:i') . 
                      ' | Lokasi: ' . round($this->distanceToClinic) . 'm dari klinik')
                ->success()
                ->send();
        } elseif (!$attendance) {
            Notification::make()
                ->title('❌ Belum Check In')
                ->body('Anda harus check in terlebih dahulu')
                ->danger()
                ->send();
        } else {
            Notification::make()
                ->title('⚠️ Sudah Check Out')
                ->body('Anda sudah melakukan check out hari ini')
                ->warning()
                ->send();
        }
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
    
    public function resetLocation()
    {
        $this->latitude = null;
        $this->longitude = null;
        $this->accuracy = null;
        $this->locationDetected = false;
        $this->withinRadius = false;
        $this->distanceToClinic = null;
    }

    /**
     * Get simple location display (removed complex Google Maps field due to compatibility issues)
     */
    public function getLocationDisplay(): array
    {
        if (!$this->locationDetected) {
            return [
                'status' => 'not_detected',
                'message' => 'Lokasi belum terdeteksi'
            ];
        }
        
        return [
            'status' => 'detected',
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'accuracy' => $this->accuracy,
            'distance' => $this->distanceToClinic,
            'within_radius' => $this->withinRadius,
            'google_maps_url' => "https://maps.google.com/maps?q={$this->latitude},{$this->longitude}",
            'directions_url' => "https://www.google.com/maps/dir/{$this->latitude},{$this->longitude}/{self::CLINIC_LAT},{self::CLINIC_LNG}"
        ];
    }

    /**
     * Get Filament Map field for enhanced location picking (Dotswan)
     */
    public function getFilamentMapField(): Map
    {
        $mapField = Map::make('current_location')
            ->label('📍 Peta Lokasi Interaktif')
            ->showMyLocationButton()
            ->zoom(17) // High zoom for accuracy
            ->extraStyles(['height: 350px'])
            ->afterStateUpdated(function (callable $get, callable $set, ?array $state): void {
                if ($state && isset($state['lat'], $state['lng'])) {
                    $this->handleLocationReceived($state['lat'], $state['lng'], null);
                }
            })
            ->reactive()
            ->columnSpanFull();
            
        // Set default location if detected
        if ($this->locationDetected && $this->latitude && $this->longitude) {
            $mapField->default([
                'lat' => $this->latitude,
                'lng' => $this->longitude
            ]);
        }
        
        return $mapField;
    }
    
    /**
     * Enhanced location validation with better accuracy checking
     */
    public function validateLocationAccuracy($accuracy): bool
    {
        if ($accuracy === null) return true; // Allow if accuracy not available
        
        if ($accuracy > 50) {
            Notification::make()
                ->title('⚠️ GPS Akurasi Rendah')
                ->body("Akurasi GPS: {$accuracy}m. Pindah ke area terbuka untuk hasil lebih akurat.")
                ->warning()
                ->persistent()
                ->send();
            return false;
        }
        
        if ($accuracy > 20) {
            Notification::make()
                ->title('📍 GPS Akurasi Sedang')
                ->body("Akurasi GPS: {$accuracy}m. Masih dapat digunakan untuk presensi.")
                ->info()
                ->send();
        }
        
        return true;
    }

    public function getViewData(): array
    {
        $user = auth()->user();
        $today = Carbon::now('Asia/Jakarta')->startOfDay();
        
        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('date', $today)
            ->first();
            
        $hasCheckedIn = $attendance && $attendance->time_in;
        $hasCheckedOut = $attendance && $attendance->time_out;
        
        return [
            'attendance' => $attendance,
            'hasCheckedIn' => $hasCheckedIn,
            'hasCheckedOut' => $hasCheckedOut,
            'checkinTime' => $hasCheckedIn ? Carbon::parse($attendance->time_in)->format('H:i') : null,
            'checkoutTime' => $hasCheckedOut ? Carbon::parse($attendance->time_out)->format('H:i') : null,
            'currentTime' => Carbon::now('Asia/Jakarta')->format('H:i:s'),
            'currentDate' => Carbon::now('Asia/Jakarta')->format('l, d F Y'),
            // Location data
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'accuracy' => $this->accuracy,
            'locationDetected' => $this->locationDetected,
            'withinRadius' => $this->withinRadius,
            'distanceToClinic' => $this->distanceToClinic,
            'browserInfo' => $this->browserInfo ?? 'Unknown',
            'deviceInfo' => $this->deviceInfo ?? 'Unknown',
            'ipAddress' => $this->ipAddress ?? 'Unknown',
            'clinicLat' => self::CLINIC_LAT,
            'clinicLng' => self::CLINIC_LNG,
            'clinicRadius' => self::CLINIC_RADIUS,
            // Enhanced map data
            'mapField' => $this->getFilamentMapField(),
            'locationDisplay' => $this->getLocationDisplay(),
            'accuracyStatus' => $this->getAccuracyStatus(),
        ];
    }
    
    private function getAccuracyStatus(): array
    {
        if (!$this->accuracy) {
            return ['status' => 'unknown', 'color' => 'gray', 'message' => 'Belum terdeteksi'];
        }
        
        if ($this->accuracy <= 10) {
            return ['status' => 'excellent', 'color' => 'green', 'message' => 'Sangat Akurat'];
        } elseif ($this->accuracy <= 20) {
            return ['status' => 'good', 'color' => 'blue', 'message' => 'Akurat'];
        } elseif ($this->accuracy <= 50) {
            return ['status' => 'fair', 'color' => 'yellow', 'message' => 'Cukup Akurat'];
        } else {
            return ['status' => 'poor', 'color' => 'red', 'message' => 'Kurang Akurat'];
        }
    }
}
