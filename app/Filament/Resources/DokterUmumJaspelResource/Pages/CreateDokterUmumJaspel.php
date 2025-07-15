<?php

namespace App\Filament\Resources\DokterUmumJaspelResource\Pages;

use App\Filament\Resources\DokterUmumJaspelResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateDokterUmumJaspel extends CreateRecord
{
    protected static string $resource = DokterUmumJaspelResource::class;
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
