<?php

namespace App\Filament\Dokter\Pages;

use Filament\Pages\Page;
use App\Models\DokterPresensi;
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
    protected static string $view = 'filament.dokter.pages.presensi-mobile';
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

    public function mount(): void
    {
        $this->user = Auth::user();
        $this->currentTime = Carbon::now('Asia/Jakarta');
        
        // Get today's attendance
        $this->todayAttendance = DokterPresensi::where('dokter_id', Auth::id())
            ->whereDate('tanggal', Carbon::today())
            ->first();
            
        // Check if user can check in/out
        $this->canCheckin = !$this->todayAttendance;
        $this->canCheckout = $this->todayAttendance && !$this->todayAttendance->jam_pulang;
    }

    // 🚀 New 2024 method: Check-in with location data
    public function checkinWithLocation($locationData)
    {
        return $this->processAttendance('checkin', $locationData);
    }
    
    // 🚀 New 2024 method: Check-out with location data  
    public function checkoutWithLocation($locationData)
    {
        return $this->processAttendance('checkout', $locationData);
    }
    
    // 🎯 Unified attendance processing method
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

            // Validate geofencing (server-side)
            $adminLat = -6.1754;
            $adminLng = 106.8272;
            $allowedRadius = 100; // meters
            
            $distance = $this->calculateDistance($userLat, $userLng, $adminLat, $adminLng);
            
            if ($distance > $allowedRadius) {
                Notification::make()
                    ->title('❌ Di Luar Area Presensi')
                    ->body("Jarak: {$distance}m. Maksimal: {$allowedRadius}m dari klinik.")
                    ->danger()
                    ->send();
                return;
            }

            $now = Carbon::now('Asia/Jakarta');
            
            if ($action === 'checkin') {
                // Create new attendance record
                DokterPresensi::create([
                    'dokter_id' => Auth::id(),
                    'tanggal' => $now->toDateString(),
                    'jam_masuk' => $now->toTimeString(),
                    'status' => $now->hour < 8 ? 'tepat_waktu' : 'terlambat',
                    'keterangan' => "Mobile GPS - Lat: {$userLat}, Lng: {$userLng}, Jarak: {$distance}m, Akurasi: ±{$accuracy}m"
                ]);

                Notification::make()
                    ->title('✅ Check In Berhasil')
                    ->body("Masuk tercatat {$now->format('H:i')} - {$distance}m dari klinik")
                    ->success()
                    ->send();
                    
            } elseif ($action === 'checkout') {
                if ($this->todayAttendance) {
                    $currentKeterangan = $this->todayAttendance->keterangan ?? '';
                    $checkoutInfo = "Checkout: Lat: {$userLat}, Lng: {$userLng}, Jarak: {$distance}m, Akurasi: ±{$accuracy}m";
                    
                    $this->todayAttendance->update([
                        'jam_pulang' => $now->toTimeString(),
                        'durasi' => $now->diff(Carbon::parse($this->todayAttendance->jam_masuk))->format('%H:%I:%S'),
                        'keterangan' => $currentKeterangan . ' | ' . $checkoutInfo
                    ]);

                    Notification::make()
                        ->title('✅ Check Out Berhasil')
                        ->body("Pulang tercatat {$now->format('H:i')} - {$distance}m dari klinik")
                        ->success()
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