<?php

namespace App\Filament\Dokter\Resources\PasienResource\Pages;

use App\Filament\Dokter\Resources\PasienResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPasien extends ViewRecord
{
    protected static string $resource = PasienResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label('Kembali')
                ->icon('heroicon-o-arrow-left')
                ->url(fn (): string => PasienResource::getUrl('index'))
                ->color('gray'),
        ];
    }
}