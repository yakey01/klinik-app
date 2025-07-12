<?php

namespace App\Filament\Petugas\Resources\PendapatanResource\Pages;

use App\Filament\Petugas\Resources\PendapatanResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPendapatans extends ListRecords
{
    protected static string $resource = PendapatanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Input Pendapatan')
                ->icon('heroicon-o-plus-circle'),
        ];
    }
}