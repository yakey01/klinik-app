<?php

namespace App\Filament\Bendahara\Resources\ValidasiPendapatanResource\Pages;

use App\Filament\Bendahara\Resources\ValidasiPendapatanResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditValidasiPendapatan extends EditRecord
{
    protected static string $resource = ValidasiPendapatanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if ($data['status_validasi'] !== 'pending') {
            $data['validasi_by'] = Auth::id();
            $data['validasi_at'] = now();
        }

        return $data;
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Validasi pendapatan berhasil disimpan!';
    }
}