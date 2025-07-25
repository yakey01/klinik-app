<?php

namespace App\Filament\Bendahara\Resources\ValidasiTindakanResource\Pages;

use App\Filament\Bendahara\Resources\ValidasiTindakanResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditValidasiTindakan extends EditRecord
{
    protected static string $resource = ValidasiTindakanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}