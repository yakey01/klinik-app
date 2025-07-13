<?php

namespace App\Filament\Dokter\Resources\TindakanDokterResource\Pages;

use App\Filament\Dokter\Resources\TindakanDokterResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewTindakanDokter extends ViewRecord
{
    protected static string $resource = TindakanDokterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No edit or delete actions
        ];
    }
}