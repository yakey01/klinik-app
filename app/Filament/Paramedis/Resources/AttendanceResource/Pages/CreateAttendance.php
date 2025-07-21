<?php

namespace App\Filament\Paramedis\Resources\AttendanceResource\Pages;

use App\Filament\Paramedis\Resources\AttendanceResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use App\Models\Attendance;
use Carbon\Carbon;

class CreateAttendance extends CreateRecord
{
    protected static string $resource = AttendanceResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Ensure user_id is set to current user if not super_admin
        if (!auth()->user()->hasRole('super_admin')) {
            $data['user_id'] = auth()->id();
        }

        // Set default values if not provided
        if (!isset($data['date'])) {
            $data['date'] = today();
        }

        // Convert GPS coordinates to latlon format for backward compatibility
        if (isset($data['latitude']) && isset($data['longitude'])) {
            $data['latlon_in'] = $data['latitude'] . ',' . $data['longitude'];
        }

        if (isset($data['checkout_latitude']) && isset($data['checkout_longitude'])) {
            $data['latlon_out'] = $data['checkout_latitude'] . ',' . $data['checkout_longitude'];
        }

        return $data;
    }

    protected function beforeCreate(): void
    {
        // Check if user already has attendance for this date
        $userId = $this->data['user_id'] ?? auth()->id();
        $date = $this->data['date'] ?? today();
        
        $existingAttendance = Attendance::where('user_id', $userId)
            ->where('date', $date)
            ->first();

        if ($existingAttendance) {
            Notification::make()
                ->title('Error')
                ->body('Sudah ada data presensi untuk tanggal ini')
                ->danger()
                ->send();
                
            $this->halt();
        }
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Data presensi berhasil ditambahkan';
    }

    public function getTitle(): string
    {
        return 'Tambah Data Presensi';
    }
}