<?php

namespace App\Filament\Dokter\Pages;

use Filament\Pages\Page;
use App\Models\DokterPresensi;
use App\Models\User;
use App\Models\WorkLocation;
use App\Models\LocationValidation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\Support\Htmlable;
use Filament\Notifications\Notification;
use Carbon\Carbon;

class PresensiMobilePage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-map-pin';
    protected static ?string $navigationLabel = 'Presensi';
    protected static ?int $navigationSort = 2;
    protected static string $view = 'filament.dokter.pages.presensi-mobile-premium';
    protected static string $routePath = '/presensi-mobile';
    
    public function getTitle(): string|Htmlable
    {
        return 'Beranda';
    }
    
    public function getHeading(): string|Htmlable
    {
        return '';
    }
    
    public function getDokterInfo(): array
    {
        $user = Auth::user();
        return [
            'name' => 'dr. ' . $user->name,
            'specialty' => 'Dokter Umum',
            'avatar' => 'https://ui-avatars.com/api/?name=' . urlencode($user->name) . '&background=3b82f6&color=fff&size=80'
        ];
    }
    
    public function getMeritInfo(): array
    {
        return [
            'target' => 0,
            'deficit' => 0
        ];
    }

    public $user;
    public $todayAttendance;
    public $canCheckin;
    public $canCheckout;
    public $currentTime;
    public $workLocations;
    public $primaryLocation;
    
    // Livewire properties for location data
    public $userLatitude;
    public $userLongitude; 
    public $userAccuracy;

    public function mount(): void
    {
        $this->user = Auth::user();
        $this->currentTime = Carbon::now('Asia/Jakarta');
        
        // Get active work locations from admin geofencing
        $this->workLocations = WorkLocation::where('is_active', true)->get();
        $this->primaryLocation = $this->workLocations->where('location_type', 'main_office')->first() 
                                ?? $this->workLocations->first();
        
        // Get today's attendance
        $this->todayAttendance = DokterPresensi::where('dokter_id', Auth::id())
            ->whereDate('tanggal', Carbon::today())
            ->first();
            
        // Check if user can check in/out
        $this->canCheckin = !$this->todayAttendance;
        $this->canCheckout = $this->todayAttendance && !$this->todayAttendance->jam_pulang;
    }

    // 🚀 New 2024 method: Check-in with location data
    public function checkinWithLocation()
    {
        // Validate location data exists
        if (!$this->userLatitude || !$this->userLongitude) {
            Notification::make()
                ->title('❌ Lokasi Diperlukan')
                ->body('GPS tidak terdeteksi. Aktifkan GPS dan coba lagi.')
                ->danger()
                ->send();
            return;
        }
        
        $locationData = [
            'latitude' => $this->userLatitude,
            'longitude' => $this->userLongitude,
            'accuracy' => $this->userAccuracy ?? 0
        ];
        
        return $this->processAttendance('checkin', $locationData);
    }
    
    // 🚀 New 2024 method: Check-out with location data  
    public function checkoutWithLocation()
    {
        // Validate location data exists
        if (!$this->userLatitude || !$this->userLongitude) {
            Notification::make()
                ->title('❌ Lokasi Diperlukan')
                ->body('GPS tidak terdeteksi. Aktifkan GPS dan coba lagi.')
                ->danger()
                ->send();
            return;
        }
        
        $locationData = [
            'latitude' => $this->userLatitude,
            'longitude' => $this->userLongitude,
            'accuracy' => $this->userAccuracy ?? 0
        ];
        
        return $this->processAttendance('checkout', $locationData);
    }
    
    // 🎯 Unified attendance processing method with advanced geofencing
    private function processAttendance($action, $locationData)
    {
        try {
            $userLat = $locationData['latitude'] ?? null;
            $userLng = $locationData['longitude'] ?? null;
            $accuracy = $locationData['accuracy'] ?? 0;
            
            \Log::info("${action} location received", [
                'latitude' => $userLat,
                'longitude' => $userLng,
                'accuracy' => $accuracy,
                'user_id' => Auth::id()
            ]);
            
            if (!$userLat || !$userLng) {
                Notification::make()
                    ->title('❌ Lokasi Diperlukan')
                    ->body('GPS tidak terdeteksi. Aktifkan GPS dan coba lagi.')
                    ->danger()
                    ->send();
                return;
            }
            
            // Convert to float for calculation
            $userLat = floatval($userLat);
            $userLng = floatval($userLng);

            // 🏢 Advanced geofencing validation using WorkLocation model
            $validLocation = null;
            $validationResults = [];
            
            foreach ($this->workLocations as $location) {
                $isWithinGeofence = $location->isWithinGeofence($userLat, $userLng, $accuracy);
                $distance = $location->calculateDistance($userLat, $userLng);
                
                // Log validation attempt
                $validation = LocationValidation::create([
                    'user_id' => Auth::id(),
                    'work_location_id' => $location->id,
                    'latitude' => $userLat,
                    'longitude' => $userLng,
                    'accuracy' => $accuracy,
                    'is_within_zone' => $isWithinGeofence,
                    'distance_meters' => $distance,
                    'validation_type' => $action,
                    'validation_time' => Carbon::now('Asia/Jakarta'),
                    'additional_data' => [
                        'user_agent' => request()->userAgent(),
                        'ip_address' => request()->ip(),
                        'location_name' => $location->name,
                        'radius_meters' => $location->radius_meters,
                        'strict_geofence' => $location->strict_geofence
                    ]
                ]);
                
                $validationResults[] = [
                    'location' => $location,
                    'distance' => $distance,
                    'isValid' => $isWithinGeofence,
                    'validation' => $validation
                ];
                
                if ($isWithinGeofence) {
                    $validLocation = $location;
                    break; // Found valid location, stop checking
                }
            }
            
            if (!$validLocation) {
                // Find closest location for better error message
                $closestResult = collect($validationResults)->sortBy('distance')->first();
                $closestLocation = $closestResult['location'];
                $closestDistance = $closestResult['distance'];
                
                Notification::make()
                    ->title('❌ Di Luar Area Presensi')
                    ->body("Terdekat: {$closestLocation->name} ({$closestDistance}m). Maks: {$closestLocation->radius_meters}m")
                    ->danger()
                    ->duration(8000)
                    ->send();
                return;
            }

            $now = Carbon::now('Asia/Jakarta');
            $validDistance = $validLocation->calculateDistance($userLat, $userLng);
            
            if ($action === 'checkin') {
                // Create new attendance record with WorkLocation reference
                DokterPresensi::create([
                    'dokter_id' => Auth::id(),
                    'tanggal' => $now->toDateString(),
                    'jam_masuk' => $now->toTimeString(),
                    'status' => $now->hour < 8 ? 'tepat_waktu' : 'terlambat',
                    'keterangan' => "📍 {$validLocation->name} - GPS: {$userLat}, {$userLng} | Jarak: {$validDistance}m | Akurasi: ±{$accuracy}m | Tipe: {$validLocation->location_type}"
                ]);

                Notification::make()
                    ->title('✅ Check In Berhasil')
                    ->body("Masuk tercatat {$now->format('H:i')} di {$validLocation->name} ({$validDistance}m)")
                    ->success()
                    ->duration(6000)
                    ->send();
                    
            } elseif ($action === 'checkout') {
                if ($this->todayAttendance) {
                    $currentKeterangan = $this->todayAttendance->keterangan ?? '';
                    $checkoutInfo = "📤 Checkout: {$validLocation->name} - GPS: {$userLat}, {$userLng} | Jarak: {$validDistance}m | Akurasi: ±{$accuracy}m";
                    
                    $this->todayAttendance->update([
                        'jam_pulang' => $now->toTimeString(),
                        'durasi' => $now->diff(Carbon::parse($this->todayAttendance->jam_masuk))->format('%H:%I:%S'),
                        'keterangan' => $currentKeterangan . ' | ' . $checkoutInfo
                    ]);

                    Notification::make()
                        ->title('✅ Check Out Berhasil')
                        ->body("Pulang tercatat {$now->format('H:i')} dari {$validLocation->name} ({$validDistance}m)")
                        ->success()
                        ->duration(6000)
                        ->send();
                }
            }

            // Refresh data
            $this->mount();

        } catch (\Exception $e) {
            \Log::error("${action} error", ['error' => $e->getMessage(), 'user_id' => Auth::id()]);
            
            Notification::make()
                ->title("❌ ${action} Gagal")
                ->body('Terjadi kesalahan sistem. Coba lagi.')
                ->danger()
                ->send();
        }
    }
    
    // Keep old methods for backward compatibility
    public function checkin()
    {
        Notification::make()
            ->title('⚠️ Gunakan Tombol Lokasi')
            ->body('Pastikan GPS aktif dan tekan tombol presensi setelah lokasi terdeteksi.')
            ->warning()
            ->send();
    }

    public function checkout()
    {
        Notification::make()
            ->title('⚠️ Gunakan Tombol Lokasi')
            ->body('Pastikan GPS aktif dan tekan tombol presensi setelah lokasi terdeteksi.')
            ->warning()
            ->send();
    }

    private function calculateDistance($lat1, $lng1, $lat2, $lng2)
    {
        // Haversine formula
        $earthRadius = 6371000; // Earth's radius in meters
        
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        
        $a = sin($dLat/2) * sin($dLat/2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLng/2) * sin($dLng/2);
             
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        
        $distance = $earthRadius * $c;
        
        return round($distance, 1); // Return distance in meters
    }
}