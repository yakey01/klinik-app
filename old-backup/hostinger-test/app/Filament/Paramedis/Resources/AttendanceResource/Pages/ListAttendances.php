<?php

namespace App\Filament\Paramedis\Resources\AttendanceResource\Pages;

use App\Filament\Paramedis\Resources\AttendanceResource;
use App\Models\Attendance;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Notifications\Notification;
use Carbon\Carbon;

class ListAttendances extends ListRecords
{
    protected static string $resource = AttendanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('quickCheckin')
                ->label('Check-in Cepat')
                ->icon('heroicon-o-clock')
                ->color('success')
                ->visible(fn () => !Attendance::hasCheckedInToday(auth()->id()))
                ->action(function () {
                    $today = Carbon::today();
                    $now = Carbon::now();
                    $workStartTime = Carbon::createFromTime(8, 0, 0);
                    $status = $now->gt($workStartTime) ? 'late' : 'present';

                    try {
                        Attendance::create([
                            'user_id' => auth()->id(),
                            'date' => $today,
                            'time_in' => $now->format('H:i:s'),
                            'latlon_in' => '0,0',
                            'location_name_in' => 'Dashboard Check-in Cepat',
                            'status' => $status,
                            'notes' => 'Check-in cepat melalui Dashboard Paramedis',
                            'latitude' => 0,
                            'longitude' => 0,
                            'location_validated' => false,
                        ]);

                        Notification::make()
                            ->title('Check-in Berhasil!')
                            ->body("Anda berhasil check-in pada {$now->format('H:i')} dengan status: " . ($status === 'late' ? 'Terlambat' : 'Hadir'))
                            ->success()
                            ->send();

                        $this->redirect(static::getUrl());
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Check-in Gagal!')
                            ->body('Terjadi kesalahan saat melakukan check-in')
                            ->danger()
                            ->send();
                    }
                }),

            Action::make('quickCheckout')
                ->label('Check-out Cepat')
                ->icon('heroicon-o-arrow-right-circle')
                ->color('warning')
                ->visible(function () {
                    $attendance = Attendance::getTodayAttendance(auth()->id());
                    return $attendance && $attendance->canCheckOut();
                })
                ->action(function () {
                    $attendance = Attendance::getTodayAttendance(auth()->id());
                    
                    if (!$attendance) {
                        Notification::make()
                            ->title('Error')
                            ->body('Anda belum melakukan check-in hari ini')
                            ->danger()
                            ->send();
                        return;
                    }

                    if (!$attendance->canCheckOut()) {
                        Notification::make()
                            ->title('Error')
                            ->body('Anda sudah melakukan check-out hari ini')
                            ->danger()
                            ->send();
                        return;
                    }

                    try {
                        $now = Carbon::now();
                        $attendance->update([
                            'time_out' => $now->format('H:i:s'),
                            'location_name_out' => 'Dashboard Check-out Cepat',
                            'latlon_out' => '0,0',
                            'checkout_latitude' => 0,
                            'checkout_longitude' => 0,
                            'notes' => ($attendance->notes ?: '') . "\nCheck-out cepat melalui Dashboard Paramedis",
                        ]);

                        Notification::make()
                            ->title('Check-out Berhasil!')
                            ->body("Anda berhasil check-out pada {$now->format('H:i')}. Durasi kerja: {$attendance->fresh()->formatted_work_duration}")
                            ->success()
                            ->send();

                        $this->redirect(static::getUrl());
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Check-out Gagal!')
                            ->body('Terjadi kesalahan saat melakukan check-out')
                            ->danger()
                            ->send();
                    }
                }),

            Actions\CreateAction::make()
                ->label('Tambah Presensi')
                ->icon('heroicon-o-plus'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            // You can add summary widgets here if needed
        ];
    }

    public function getTitle(): string
    {
        return 'Data Presensi Saya';
    }

    protected function getTableQuery(): \Illuminate\Database\Eloquent\Builder
    {
        // Only show current user's attendance records
        return parent::getTableQuery()
            ->where('user_id', auth()->id())
            ->latest('date');
    }
}