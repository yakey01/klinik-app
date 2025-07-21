<?php

namespace App\Filament\Resources\CutiPegawaiResource\Pages;

use App\Filament\Resources\CutiPegawaiResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCutiPegawais extends ListRecords
{
    protected static string $resource = CutiPegawaiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
