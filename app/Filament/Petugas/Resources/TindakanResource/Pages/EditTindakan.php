<?php

namespace App\Filament\Petugas\Resources\TindakanResource\Pages;

use App\Filament\Petugas\Resources\TindakanResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTindakan extends EditRecord
{
    protected static string $resource = TindakanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}