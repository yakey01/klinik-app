<?php

namespace App\Filament\Resources\KalenderKerjaResource\Pages;

use App\Filament\Resources\KalenderKerjaResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateKalenderKerja extends CreateRecord
{
    protected static string $resource = KalenderKerjaResource::class;
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
