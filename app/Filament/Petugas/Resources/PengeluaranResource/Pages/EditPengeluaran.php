<?php

namespace App\Filament\Petugas\Resources\PengeluaranResource\Pages;

use App\Filament\Petugas\Resources\PengeluaranResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPengeluaran extends EditRecord
{
    protected static string $resource = PengeluaranResource::class;

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