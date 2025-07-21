<?php

namespace App\Filament\Petugas\Resources\ValidasiPendapatanResource\Pages;

use App\Filament\Petugas\Resources\ValidasiPendapatanResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPendapatans extends ListRecords
{
    protected static string $resource = ValidasiPendapatanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Input Pendapatan')
                ->icon('heroicon-o-plus-circle'),
        ];
    }
}