<?php

namespace App\Filament\Bendahara\Resources\ValidasiPengeluaranHarianResource\Pages;

use App\Filament\Bendahara\Resources\ValidasiPengeluaranHarianResource;
use Filament\Resources\Pages\ListRecords;

class ListValidasiPengeluaranHarian extends ListRecords
{
    protected static string $resource = ValidasiPengeluaranHarianResource::class;

    protected static ?string $title = '📉 Validasi Pengeluaran Harian';
}