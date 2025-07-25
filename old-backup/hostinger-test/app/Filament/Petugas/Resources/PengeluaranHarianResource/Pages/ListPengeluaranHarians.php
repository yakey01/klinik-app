<?php

namespace App\Filament\Petugas\Resources\PengeluaranHarianResource\Pages;

use App\Filament\Petugas\Resources\PengeluaranHarianResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPengeluaranHarians extends ListRecords
{
    protected static string $resource = PengeluaranHarianResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('➕ Tambah Pengeluaran')
                ->icon('heroicon-o-plus-circle')
                ->color('danger'),
        ];
    }
}