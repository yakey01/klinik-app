<?php

namespace App\Filament\Resources\PendapatanResource\Pages;

use App\Filament\Resources\PendapatanResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPendapatan extends ViewRecord
{
    protected static string $resource = PendapatanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('Edit Pendapatan')
                ->icon('heroicon-o-pencil'),
        ];
    }
}