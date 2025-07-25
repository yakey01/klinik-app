<?php

namespace App\Filament\Resources\WorkLocationResource\Pages;

use App\Filament\Resources\WorkLocationResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CreateWorkLocation extends CreateRecord
{
    protected static string $resource = WorkLocationResource::class;

    public function getTitle(): string
    {
        return '➕ Tambah Lokasi Kerja';
    }

    public function getHeading(): string
    {
        return '➕ Tambah Lokasi Kerja';
    }

    public function getSubheading(): ?string
    {
        return 'Konfigurasi lokasi kerja baru dengan geofencing GPS';
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('✅ Lokasi Kerja Berhasil Ditambahkan!')
            ->body('Lokasi kerja telah dikonfigurasi dan siap digunakan untuk validasi absensi.')
            ->duration(5000);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    /**
     * Handle after create to clear general caches for new location
     */
    protected function afterCreate(): void
    {
        $workLocation = $this->record;
        
        Log::info('New WorkLocation created via Filament Admin', [
            'id' => $workLocation->id,
            'name' => $workLocation->name,
            'admin_user' => auth()->user()?->name,
            'timestamp' => now()
        ]);

        // Clear general location caches so new location appears immediately
        $this->clearGeneralLocationCaches();
        
        // Send additional notification
        Notification::make()
            ->success()
            ->title('🔄 Lokasi Baru Tersedia!')
            ->body('Lokasi kerja baru telah tersedia untuk semua pengguna.')
            ->duration(3000)
            ->send();
    }

    /**
     * Clear general location caches
     */
    private function clearGeneralLocationCaches(): void
    {
        $generalCacheKeys = [
            'work_locations_active',
            'work_locations_all',
            'geofence_locations',
            'attendance_locations',
        ];

        foreach ($generalCacheKeys as $key) {
            Cache::forget($key);
        }

        Log::info('General location caches cleared after new location creation');
    }
}