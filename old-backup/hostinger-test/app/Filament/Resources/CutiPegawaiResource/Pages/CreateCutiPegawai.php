<?php

namespace App\Filament\Resources\CutiPegawaiResource\Pages;

use App\Filament\Resources\CutiPegawaiResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateCutiPegawai extends CreateRecord
{
    protected static string $resource = CutiPegawaiResource::class;
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
