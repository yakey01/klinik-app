<?php

namespace App\Filament\Petugas\Resources\PasienResource\Pages;

use App\Filament\Petugas\Resources\PasienResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPasiens extends ListRecords
{
    protected static string $resource = PasienResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Input Pasien')
                ->icon('heroicon-o-plus-circle'),
        ];
    }
}