<?php

namespace App\Filament\Resources\ValidasiLokasiResource\Pages;

use App\Filament\Resources\ValidasiLokasiResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditValidasiLokasi extends EditRecord
{
    protected static string $resource = ValidasiLokasiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
