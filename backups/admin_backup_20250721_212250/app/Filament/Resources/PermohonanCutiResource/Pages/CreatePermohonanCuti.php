<?php

namespace App\Filament\Resources\PermohonanCutiResource\Pages;

use App\Filament\Resources\PermohonanCutiResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePermohonanCuti extends CreateRecord
{
    protected static string $resource = PermohonanCutiResource::class;
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
