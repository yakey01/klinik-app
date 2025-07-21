<?php

namespace App\Filament\Bendahara\Resources\ValidasiJumlahPasienResource\Pages;

use App\Filament\Bendahara\Resources\ValidasiJumlahPasienResource;
use Filament\Resources\Pages\ListRecords;

class ListValidasiJumlahPasien extends ListRecords
{
    protected static string $resource = ValidasiJumlahPasienResource::class;

    protected static ?string $title = '👥 Validasi Jumlah Pasien';
}