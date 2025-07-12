<?php

namespace App\Filament\Bendahara\Resources\ValidasiPendapatanHarianResource\Pages;

use App\Filament\Bendahara\Resources\ValidasiPendapatanHarianResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditValidasiPendapatanHarian extends EditRecord
{
    protected static string $resource = ValidasiPendapatanHarianResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Set validation info when status changes
        if (isset($data['status_validasi']) && $data['status_validasi'] !== 'pending') {
            $data['validasi_by'] = Auth::id();
            $data['validasi_at'] = now();
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}