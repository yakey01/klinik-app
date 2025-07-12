<?php

namespace App\Filament\Dokter\Resources\DokterPresensiResource\Pages;

use App\Filament\Dokter\Resources\DokterPresensiResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDokterPresensi extends EditRecord
{
    protected static string $resource = DokterPresensiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    public function getTitle(): string
    {
        return 'Edit Presensi';
    }
}