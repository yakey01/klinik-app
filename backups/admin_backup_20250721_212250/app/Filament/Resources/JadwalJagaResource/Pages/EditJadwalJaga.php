<?php

namespace App\Filament\Resources\JadwalJagaResource\Pages;

use App\Filament\Resources\JadwalJagaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditJadwalJaga extends EditRecord
{
    protected static string $resource = JadwalJagaResource::class;

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
