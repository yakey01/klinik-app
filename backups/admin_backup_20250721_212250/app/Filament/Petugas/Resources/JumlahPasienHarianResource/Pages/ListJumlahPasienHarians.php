<?php

namespace App\Filament\Petugas\Resources\JumlahPasienHarianResource\Pages;

use App\Filament\Petugas\Resources\JumlahPasienHarianResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListJumlahPasienHarians extends ListRecords
{
    protected static string $resource = JumlahPasienHarianResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Data Pasien')
                ->icon('heroicon-o-plus'),
        ];
    }
}