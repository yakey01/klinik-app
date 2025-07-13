<?php

namespace App\Filament\Dokter\Resources\TindakanDokterResource\Pages;

use App\Filament\Dokter\Resources\TindakanDokterResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTindakanDokters extends ListRecords
{
    protected static string $resource = TindakanDokterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No create action since dokter cannot create tindakan
        ];
    }
}