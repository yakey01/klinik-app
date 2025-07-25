<?php

namespace App\Filament\Bendahara\Resources\JaspelManagementResource\Pages;

use App\Filament\Bendahara\Resources\JaspelManagementResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditJaspelManagement extends EditRecord
{
    protected static string $resource = JaspelManagementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}