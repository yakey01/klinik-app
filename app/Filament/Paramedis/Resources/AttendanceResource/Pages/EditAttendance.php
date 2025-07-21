<?php

namespace App\Filament\Paramedis\Resources\AttendanceResource\Pages;

use App\Filament\Paramedis\Resources\AttendanceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditAttendance extends EditRecord
{
    protected static string $resource = AttendanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make()
                ->visible(fn () => auth()->user()->hasRole(['super_admin', 'admin'])),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Convert latlon format back to separate coordinates for form display
        if (isset($data['latlon_in']) && $data['latlon_in']) {
            $coords = explode(',', $data['latlon_in']);
            if (count($coords) === 2) {
                $data['latitude'] = (float) $coords[0];
                $data['longitude'] = (float) $coords[1];
            }
        }

        if (isset($data['latlon_out']) && $data['latlon_out']) {
            $coords = explode(',', $data['latlon_out']);
            if (count($coords) === 2) {
                $data['checkout_latitude'] = (float) $coords[0];
                $data['checkout_longitude'] = (float) $coords[1];
            }
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Ensure user_id cannot be changed by non-admin users
        if (!auth()->user()->hasRole('super_admin') && $this->record->user_id !== auth()->id()) {
            Notification::make()
                ->title('Error')
                ->body('Anda tidak memiliki izin untuk mengedit data presensi ini')
                ->danger()
                ->send();
                
            $this->halt();
        }

        // Update latlon format for backward compatibility
        if (isset($data['latitude']) && isset($data['longitude'])) {
            $data['latlon_in'] = $data['latitude'] . ',' . $data['longitude'];
        }

        if (isset($data['checkout_latitude']) && isset($data['checkout_longitude'])) {
            $data['latlon_out'] = $data['checkout_latitude'] . ',' . $data['checkout_longitude'];
        }

        return $data;
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Data presensi berhasil diperbarui';
    }

    public function getTitle(): string
    {
        return 'Edit Data Presensi';
    }
}