<?php

namespace App\Filament\Paramedis\Pages;

use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use App\Models\Attendance;
use Carbon\Carbon;

class PresensiPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-clock';
    
    protected static string $view = 'filament.paramedis.pages.presensi-page';
    
    protected static ?string $navigationLabel = 'Presensi';
    
    protected static ?string $title = 'Presensi Masuk & Pulang';
    
    public static function canAccess(): bool
    {
        return auth()->check() && auth()->user()->role?->name === 'paramedis';
    }
    
    protected function getHeaderActions(): array
    {
        return [
            Action::make('refreshStatus')
                ->label('Refresh Status')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->action(function () {
                    Notification::make()
                        ->title('Status diperbarui')
                        ->success()
                        ->send();
                }),
        ];
    }
    
    public function checkin()
    {
        $user = auth()->user();
        $today = Carbon::now('Asia/Jakarta')->startOfDay();
        
        $existing = Attendance::where('user_id', $user->id)
            ->whereDate('date', $today)
            ->first();
            
        if (!$existing) {
            Attendance::create([
                'user_id' => $user->id,
                'date' => $today,
                'check_in' => Carbon::now('Asia/Jakarta'),
                'status' => 'present'
            ]);
            
            Notification::make()
                ->title('✅ Check In Berhasil')
                ->body('Selamat bekerja! Jam masuk: ' . Carbon::now('Asia/Jakarta')->format('H:i'))
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
        
        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('date', $today)
            ->first();
            
        if ($attendance && !$attendance->check_out) {
            $attendance->update([
                'check_out' => Carbon::now('Asia/Jakarta')
            ]);
            
            Notification::make()
                ->title('🏁 Check Out Berhasil')
                ->body('Terima kasih! Jam pulang: ' . Carbon::now('Asia/Jakarta')->format('H:i'))
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
    
    public function getViewData(): array
    {
        $user = auth()->user();
        $today = Carbon::now('Asia/Jakarta')->startOfDay();
        
        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('date', $today)
            ->first();
            
        $hasCheckedIn = $attendance && $attendance->check_in;
        $hasCheckedOut = $attendance && $attendance->check_out;
        
        return [
            'attendance' => $attendance,
            'hasCheckedIn' => $hasCheckedIn,
            'hasCheckedOut' => $hasCheckedOut,
            'checkinTime' => $hasCheckedIn ? $attendance->check_in->format('H:i') : null,
            'checkoutTime' => $hasCheckedOut ? $attendance->check_out->format('H:i') : null,
            'currentTime' => Carbon::now('Asia/Jakarta')->format('H:i:s'),
            'currentDate' => Carbon::now('Asia/Jakarta')->format('l, d F Y'),
        ];
    }
}
