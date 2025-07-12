<?php

namespace App\Filament\Petugas\Resources\PengeluaranResource\Pages;

use App\Filament\Petugas\Resources\PengeluaranResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPengeluarans extends ListRecords
{
    protected static string $resource = PengeluaranResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Input Pengeluaran')
                ->icon('heroicon-o-plus-circle'),
        ];
    }
}