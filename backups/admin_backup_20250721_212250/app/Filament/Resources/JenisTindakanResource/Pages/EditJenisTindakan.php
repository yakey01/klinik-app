<?php

namespace App\Filament\Resources\JenisTindakanResource\Pages;

use App\Filament\Resources\JenisTindakanResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditJenisTindakan extends EditRecord
{
    protected static string $resource = JenisTindakanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->label('Lihat'),
            Actions\DeleteAction::make()
                ->label('Hapus'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['kode'] = strtoupper($data['kode']);
        
        return $data;
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Jenis tindakan berhasil diperbarui!';
    }
}