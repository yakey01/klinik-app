<?php

namespace App\Filament\Bendahara\Resources\LaporanKeuanganResource\Pages;

use App\Filament\Bendahara\Resources\LaporanKeuanganResource;
use Filament\Resources\Pages\ListRecords;

class ListLaporanKeuangan extends ListRecords
{
    protected static string $resource = LaporanKeuanganResource::class;

    protected static ?string $title = '📊 Laporan Keuangan';
}