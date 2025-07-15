<?php

namespace App\Filament\Resources\DokterUmumJaspelResource\Pages;

use App\Filament\Resources\DokterUmumJaspelResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDokterUmumJaspel extends EditRecord
{
    protected static string $resource = DokterUmumJaspelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }}
