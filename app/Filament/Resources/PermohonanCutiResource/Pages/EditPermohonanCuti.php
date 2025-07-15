<?php

namespace App\Filament\Resources\PermohonanCutiResource\Pages;

use App\Filament\Resources\PermohonanCutiResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPermohonanCuti extends EditRecord
{
    protected static string $resource = PermohonanCutiResource::class;

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
