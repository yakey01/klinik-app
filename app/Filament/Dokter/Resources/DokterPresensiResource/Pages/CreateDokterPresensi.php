<?php

namespace App\Filament\Dokter\Resources\DokterPresensiResource\Pages;

use App\Filament\Dokter\Resources\DokterPresensiResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDokterPresensi extends CreateRecord
{
    protected static string $resource = DokterPresensiResource::class;

    public function getTitle(): string
    {
        return 'Buat Presensi Baru';
    }
}