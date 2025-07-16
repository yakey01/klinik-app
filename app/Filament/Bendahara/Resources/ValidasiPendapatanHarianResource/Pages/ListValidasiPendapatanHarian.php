<?php

namespace App\Filament\Bendahara\Resources\ValidasiPendapatanHarianResource\Pages;

use App\Filament\Bendahara\Resources\ValidasiPendapatanHarianResource;
use Filament\Resources\Pages\ListRecords;

class ListValidasiPendapatanHarian extends ListRecords
{
    protected static string $resource = ValidasiPendapatanHarianResource::class;

    protected static ?string $title = '📈 Validasi Pendapatan Harian';
}