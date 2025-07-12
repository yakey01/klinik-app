<?php

namespace App\Filament\Petugas\Resources\PendapatanHarianResource\Pages;

use App\Filament\Petugas\Resources\PendapatanHarianResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class CreatePendapatanHarian extends CreateRecord
{
    protected static string $resource = PendapatanHarianResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = Auth::id();
        
        return $data;
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('✅ Pendapatan berhasil disimpan')
            ->body('Data pendapatan harian telah berhasil ditambahkan.');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
