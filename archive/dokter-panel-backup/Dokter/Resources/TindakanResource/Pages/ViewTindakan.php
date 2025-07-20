<?php

namespace App\Filament\Dokter\Resources\TindakanResource\Pages;

use App\Filament\Dokter\Resources\TindakanResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewTindakan extends ViewRecord
{
    protected static string $resource = TindakanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label('Kembali')
                ->icon('heroicon-o-arrow-left')
                ->url(fn (): string => TindakanResource::getUrl('index'))
                ->color('gray'),
        ];
    }
}