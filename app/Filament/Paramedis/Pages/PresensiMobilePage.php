<?php

namespace App\Filament\Paramedis\Pages;

use Filament\Pages\Page;
use App\Models\Attendance;
use App\Models\WorkLocation;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\Support\Htmlable;
use Filament\Notifications\Notification;
use Carbon\Carbon;

class PresensiMobilePage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-map-pin';
    protected static ?string $navigationLabel = 'Presensi Mobile';
    protected static ?int $navigationSort = 2;
    protected static string $view = 'filament.paramedis.pages.presensi-mobile';
    protected static string $routePath = '/presensi-mobile';
    
    public function getTitle(): string|Htmlable
    {
        return 'Presensi';
    }
    
    public function getHeading(): string|Htmlable
    {
        return '';
    }

    public $user;
    public $todayAttendance;
    public $canCheckin;
    public $canCheckout;
    public $currentTime;
    public $userLatitude;
    public $userLongitude;
    public $userAccuracy;
    public $workLocations;

    public function mount(): void
    {
        $this->user = Auth::user();
        $this->currentTime = Carbon::now('Asia/Jakarta');
        
        // Get today's attendance
        $this->todayAttendance = Attendance::where('user_id', Auth::id())
            ->whereDate('date', Carbon::today())
            ->first();
            
        // Check if user can check in/out
        $this->canCheckin = !$this->todayAttendance;
        $this->canCheckout = $this->todayAttendance && !$this->todayAttendance->time_out;
        
        // Load active work locations for geofencing
        $this->workLocations = WorkLocation::active()->get();
    }

    public function checkinWithLocation()
    {
        try {
            $now = Carbon::now('Asia/Jakarta');
            
            // Validate GPS location if coordinates provided
            if ($this->userLatitude && $this->userLongitude) {
                $validLocation = $this->validateLocation($this->userLatitude, $this->userLongitude, $this->userAccuracy);
                
                if (!$validLocation['valid']) {
                    Notification::make()
                        ->title('📍 Lokasi Tidak Valid')
                        ->body($validLocation['message'])
                        ->warning()
                        ->send();
                    return;
                }
            }
            
            // Create new attendance record
            Attendance::create([
                'user_id' => Auth::id(),
                'date' => $now->toDateString(),
                'time_in' => $now->toTimeString(),
                'status' => $now->hour < 8 ? 'present' : 'late',
                'notes' => 'Check-in via Mobile GPS App',
                'latitude' => $this->userLatitude,
                'longitude' => $this->userLongitude,
                'accuracy' => $this->userAccuracy,
                'location_validated' => true,
            ]);

            // Refresh data
            $this->mount();

            Notification::make()
                ->title('✅ Check In Berhasil')
                ->body("Presensi masuk tercatat pada {$now->format('H:i')} dengan lokasi valid")
                ->success()
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('❌ Check In Gagal')
                ->body('Terjadi kesalahan saat melakukan presensi: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function checkoutWithLocation()
    {
        try {
            if ($this->todayAttendance) {
                $now = Carbon::now('Asia/Jakarta');
                
                // Validate GPS location if coordinates provided
                if ($this->userLatitude && $this->userLongitude) {
                    $validLocation = $this->validateLocation($this->userLatitude, $this->userLongitude, $this->userAccuracy);
                    
                    if (!$validLocation['valid']) {
                        Notification::make()
                            ->title('📍 Lokasi Tidak Valid')
                            ->body($validLocation['message'])
                            ->warning()
                            ->send();
                        return;
                    }
                }
                
                // Update attendance record
                $this->todayAttendance->update([
                    'time_out' => $now->toTimeString(),
                    'checkout_latitude' => $this->userLatitude,
                    'checkout_longitude' => $this->userLongitude,
                    'checkout_accuracy' => $this->userAccuracy,
                ]);

                // Refresh data
                $this->mount();

                Notification::make()
                    ->title('✅ Check Out Berhasil')
                    ->body("Presensi pulang tercatat pada {$now->format('H:i')} dengan lokasi valid")
                    ->success()
                    ->send();
            }

        } catch (\Exception $e) {
            Notification::make()
                ->title('❌ Check Out Gagal')
                ->body('Terjadi kesalahan saat melakukan presensi pulang: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    private function validateLocation(float $latitude, float $longitude, ?float $accuracy = null): array
    {
        if ($this->workLocations->isEmpty()) {
            return [
                'valid' => true,
                'message' => 'Tidak ada lokasi kerja yang dikonfigurasi - presensi diizinkan'
            ];
        }

        foreach ($this->workLocations as $location) {
            if ($location->isWithinGeofence($latitude, $longitude, $accuracy)) {
                return [
                    'valid' => true,
                    'message' => "Lokasi valid: {$location->name}",
                    'location' => $location
                ];
            }
        }

        $nearestLocation = $this->findNearestLocation($latitude, $longitude);
        $distance = $nearestLocation ? $nearestLocation->calculateDistance($latitude, $longitude) : 0;

        return [
            'valid' => false,
            'message' => $nearestLocation 
                ? "Anda berada " . round($distance) . "m dari {$nearestLocation->name}. Jarak maksimal: {$nearestLocation->radius_meters}m"
                : "Lokasi tidak ditemukan dalam area kerja yang diizinkan",
            'nearest_location' => $nearestLocation,
            'distance' => $distance
        ];
    }

    private function findNearestLocation(float $latitude, float $longitude): ?WorkLocation
    {
        $nearestLocation = null;
        $shortestDistance = null;

        foreach ($this->workLocations as $location) {
            $distance = $location->calculateDistance($latitude, $longitude);
            
            if ($shortestDistance === null || $distance < $shortestDistance) {
                $shortestDistance = $distance;
                $nearestLocation = $location;
            }
        }

        return $nearestLocation;
    }

    // Legacy methods for backward compatibility
    public function checkin()
    {
        $this->checkinWithLocation();
    }

    public function checkout()
    {
        $this->checkoutWithLocation();
    }
}