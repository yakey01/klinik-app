<?php

namespace App\Filament\Dokter\Resources\JadwalJagaResource\Pages;

use App\Filament\Dokter\Resources\JadwalJagaResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewJadwalJaga extends ViewRecord
{
    protected static string $resource = JadwalJagaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label('Kembali')
                ->icon('heroicon-o-arrow-left')
                ->url(fn (): string => JadwalJagaResource::getUrl('index'))
                ->color('gray'),
        ];
    }
}