<?php

namespace App\Filament\Resources\CutiPegawaiResource\Pages;

use App\Filament\Resources\CutiPegawaiResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCutiPegawai extends EditRecord
{
    protected static string $resource = CutiPegawaiResource::class;

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
