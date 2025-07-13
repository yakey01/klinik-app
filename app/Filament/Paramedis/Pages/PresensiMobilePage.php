<?php

namespace App\Filament\Paramedis\Pages;

use Filament\Pages\Page;
use App\Models\Attendance;
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
    }

    public function checkin()
    {
        try {
            $now = Carbon::now('Asia/Jakarta');
            
            // Create new attendance record
            Attendance::create([
                'user_id' => Auth::id(),
                'date' => $now->toDateString(),
                'time_in' => $now->toTimeString(),
                'status' => $now->hour < 8 ? 'present' : 'late',
                'notes' => 'Check-in melalui Mobile App'
            ]);

            // Refresh data
            $this->mount();

            Notification::make()
                ->title('✅ Check In Berhasil')
                ->body("Presensi masuk tercatat pada {$now->format('H:i')}")
                ->success()
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('❌ Check In Gagal')
                ->body('Terjadi kesalahan saat melakukan presensi')
                ->danger()
                ->send();
        }
    }

    public function checkout()
    {
        try {
            if ($this->todayAttendance) {
                $now = Carbon::now('Asia/Jakarta');
                
                // Update attendance record
                $this->todayAttendance->update([
                    'time_out' => $now->toTimeString()
                ]);

                // Refresh data
                $this->mount();

                Notification::make()
                    ->title('✅ Check Out Berhasil')
                    ->body("Presensi pulang tercatat pada {$now->format('H:i')}")
                    ->success()
                    ->send();
            }

        } catch (\Exception $e) {
            Notification::make()
                ->title('❌ Check Out Gagal')
                ->body('Terjadi kesalahan saat melakukan presensi pulang')
                ->danger()
                ->send();
        }
    }
}