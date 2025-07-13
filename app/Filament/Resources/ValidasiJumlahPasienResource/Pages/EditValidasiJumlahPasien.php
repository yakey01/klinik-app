<?php

namespace App\Filament\Resources\ValidasiJumlahPasienResource\Pages;

use App\Filament\Resources\ValidasiJumlahPasienResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditValidasiJumlahPasien extends EditRecord
{
    protected static string $resource = ValidasiJumlahPasienResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
