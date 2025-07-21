<?php

namespace App\Filament\Bendahara\Resources\ValidasiJaspelResource\Pages;

use App\Filament\Bendahara\Resources\ValidasiJaspelResource;
use Filament\Resources\Pages\ListRecords;

class ListValidasiJaspel extends ListRecords
{
    protected static string $resource = ValidasiJaspelResource::class;

    protected static ?string $title = '✅ Validasi Jaspel';
}