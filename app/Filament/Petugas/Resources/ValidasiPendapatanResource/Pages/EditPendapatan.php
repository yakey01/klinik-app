<?php

namespace App\Filament\Petugas\Resources\ValidasiPendapatanResource\Pages;

use App\Filament\Petugas\Resources\ValidasiPendapatanResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPendapatan extends EditRecord
{
    protected static string $resource = ValidasiPendapatanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}