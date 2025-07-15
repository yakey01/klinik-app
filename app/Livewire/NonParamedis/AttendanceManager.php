<?php

namespace App\Livewire\NonParamedis;

use App\Models\NonParamedisAttendance;
use App\Models\WorkLocation;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class AttendanceManager extends Component
{
    public $latitude;
    public $longitude;
    public $accuracy;
    public $locationStatus = 'detecting';
    public $errorMessage = '';
    public $attendance;
    public $workLocations;
    public $selectedLocationId;
    public $isLoading = false;
    public $gpsEnabled = false;
    public $showManualInput = false;
    
    public function mount()
    {
        $this->loadTodayAttendance();
        $this->loadWorkLocations();
    }

    public function loadTodayAttendance()
    {
        $this->attendance = NonParamedisAttendance::getTodayAttendance(Auth::user());
    }

    public function loadWorkLocations()
    {
        $this->workLocations = WorkLocation::active()->get();
        if ($this->workLocations->count() === 1) {
            $this->selectedLocationId = $this->workLocations->first()->id;
        }
    }

    public function checkinWithLocation($latitude, $longitude, $accuracy)
    {
        try {
            $this->isLoading = true;
            $this->errorMessage = '';

            // Validate GPS coordinates
            if (!$latitude || !$longitude) {
                throw new \Exception('Koordinat GPS tidak valid');
            }

            // Get or create today's attendance
            $this->attendance = NonParamedisAttendance::getOrCreateTodayAttendance(Auth::user());

            // Check if already checked in
            if ($this->attendance->isCheckedIn()) {
                throw new \Exception('Anda sudah check-in hari ini');
            }

            // Find nearest work location
            $nearestLocation = $this->findNearestLocation($latitude, $longitude);
            
            if (!$nearestLocation) {
                throw new \Exception('Tidak ada lokasi kerja yang tersedia');
            }

            // Validate location distance
            $distance = $nearestLocation->calculateDistance($latitude, $longitude);
            $isValidLocation = $nearestLocation->isWithinGeofence($latitude, $longitude, $accuracy);

            // Get address (placeholder - you can integrate with reverse geocoding API)
            $address = "Lat: {$latitude}, Lng: {$longitude}";

            // Update attendance record
            $this->attendance->update([
                'work_location_id' => $nearestLocation->id,
                'check_in_time' => now(),
                'check_in_latitude' => $latitude,
                'check_in_longitude' => $longitude,
                'check_in_accuracy' => $accuracy,
                'check_in_address' => $address,
                'check_in_distance' => $distance,
                'check_in_valid_location' => $isValidLocation,
                'status' => 'checked_in',
                'device_info' => request()->userAgent(),
                'browser_info' => request()->header('User-Agent'),
                'ip_address' => request()->ip(),
                'gps_metadata' => [
                    'timestamp' => now()->toISOString(),
                    'accuracy' => $accuracy,
                    'provider' => 'HTML5 Geolocation',
                ],
            ]);

            $this->locationStatus = 'success';
            $this->dispatch('attendance-updated');
            
            session()->flash('success', 'Check-in berhasil! ' . 
                ($isValidLocation ? '✅ Lokasi valid' : '⚠️ Lokasi di luar radius'));

        } catch (\Exception $e) {
            $this->errorMessage = $e->getMessage();
            Log::error('NonParamedis Check-in Error: ' . $e->getMessage());
        } finally {
            $this->isLoading = false;
        }
    }

    public function checkoutWithLocation($latitude, $longitude, $accuracy)
    {
        try {
            $this->isLoading = true;
            $this->errorMessage = '';

            // Validate GPS coordinates
            if (!$latitude || !$longitude) {
                throw new \Exception('Koordinat GPS tidak valid');
            }

            // Check if user has checked in
            if (!$this->attendance || !$this->attendance->isCheckedIn()) {
                throw new \Exception('Anda belum check-in hari ini');
            }

            // Find work location (use same as check-in)
            $workLocation = $this->attendance->workLocation;
            if (!$workLocation) {
                throw new \Exception('Lokasi kerja tidak ditemukan');
            }

            // Validate location distance
            $distance = $workLocation->calculateDistance($latitude, $longitude);
            $isValidLocation = $workLocation->isWithinGeofence($latitude, $longitude, $accuracy);

            // Get address (placeholder)
            $address = "Lat: {$latitude}, Lng: {$longitude}";

            // Update attendance record
            $this->attendance->update([
                'check_out_time' => now(),
                'check_out_latitude' => $latitude,
                'check_out_longitude' => $longitude,
                'check_out_accuracy' => $accuracy,
                'check_out_address' => $address,
                'check_out_distance' => $distance,
                'check_out_valid_location' => $isValidLocation,
                'status' => 'checked_out',
            ]);

            // Calculate and update work duration
            $this->attendance->updateWorkDuration();

            $this->locationStatus = 'success';
            $this->dispatch('attendance-updated');
            
            session()->flash('success', 'Check-out berhasil! ' . 
                ($isValidLocation ? '✅ Lokasi valid' : '⚠️ Lokasi di luar radius') .
                ' - Durasi kerja: ' . $this->attendance->formatted_work_duration);

        } catch (\Exception $e) {
            $this->errorMessage = $e->getMessage();
            Log::error('NonParamedis Check-out Error: ' . $e->getMessage());
        } finally {
            $this->isLoading = false;
        }
    }

    public function refreshLocation()
    {
        $this->locationStatus = 'detecting';
        $this->errorMessage = '';
        $this->dispatch('refresh-gps');
    }

    public function toggleManualInput()
    {
        $this->showManualInput = !$this->showManualInput;
    }

    public function manualCheckin()
    {
        if (!$this->latitude || !$this->longitude) {
            $this->errorMessage = 'Silakan masukkan koordinat GPS';
            return;
        }

        $this->checkinWithLocation($this->latitude, $this->longitude, 100);
    }

    public function manualCheckout()
    {
        if (!$this->latitude || !$this->longitude) {
            $this->errorMessage = 'Silakan masukkan koordinat GPS';
            return;
        }

        $this->checkoutWithLocation($this->latitude, $this->longitude, 100);
    }

    private function findNearestLocation($latitude, $longitude)
    {
        $nearestLocation = null;
        $minDistance = null;

        foreach ($this->workLocations as $location) {
            $distance = $location->calculateDistance($latitude, $longitude);
            
            if ($minDistance === null || $distance < $minDistance) {
                $minDistance = $distance;
                $nearestLocation = $location;
            }
        }

        return $nearestLocation;
    }

    public function getCanCheckinProperty()
    {
        return !$this->attendance || !$this->attendance->isCheckedIn();
    }

    public function getCanCheckoutProperty()
    {
        return $this->attendance && $this->attendance->isCheckedIn() && !$this->attendance->isCheckedOut();
    }

    public function getStatusTextProperty()
    {
        if (!$this->attendance) {
            return 'Belum check-in';
        }

        if ($this->attendance->isCheckedOut()) {
            return 'Sudah check-out - ' . $this->attendance->formatted_work_duration;
        }

        if ($this->attendance->isCheckedIn()) {
            return 'Sudah check-in pada ' . $this->attendance->check_in_time->format('H:i');
        }

        return 'Belum check-in';
    }

    public function render()
    {
        return view('livewire.non-paramedis.attendance-manager');
    }
}
