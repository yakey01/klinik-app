<?php

namespace App\Filament\Resources\JenisTindakanResource\Pages;

use App\Filament\Resources\JenisTindakanResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListJenisTindakan extends ListRecords
{
    protected static string $resource = JenisTindakanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Jenis Tindakan'),
        ];
    }
}