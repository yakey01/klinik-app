<?php

namespace App\Filament\Dokter\Resources\JaspelDokterResource\Pages;

use App\Filament\Dokter\Resources\JaspelDokterResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewJaspelDokter extends ViewRecord
{
    protected static string $resource = JaspelDokterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No edit action as jaspel cannot be edited
        ];
    }

    public function getTitle(): string
    {
        return 'Detail Jaspel';
    }
}