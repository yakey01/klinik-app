<?php

namespace App\Filament\Resources\TindakanResource\Pages;

use App\Filament\Resources\TindakanResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateTindakan extends CreateRecord
{
    protected static string $resource = TindakanResource::class;
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
